<?php

use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->seller = User::factory()->create(['name' => 'Penjual Uji']);
    $this->reporter = User::factory()->create(['name' => 'Pelapor Uji']);

    $this->product = Product::factory()->create([
        'user_id' => $this->seller->id,
        'judul' => 'Produk Yang Dilaporkan',
        'status' => Product::STATUS_DIJUAL,
    ]);

    $this->report = Report::factory()->create([
        'product_id' => $this->product->id,
        'reporter_id' => $this->reporter->id,
        'alasan' => Report::REASON_PENIPUAN,
        'keterangan' => 'Penjual tidak mengirim barang setelah pembayaran.',
        'status' => Report::STATUS_PENDING,
    ]);
});

test('dashboard lists reports with product, seller, and reason', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Produk Yang Dilaporkan')
        ->assertSee('Penjual Uji')
        ->assertSee('Indikasi Penipuan / Scam')
        ->assertSee('Menunggu');
});

test('report detail modal contains the full explanation', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.reports.show', $this->report))
        ->assertOk()
        ->assertSee('Penjual tidak mengirim barang setelah pembayaran.')
        ->assertSee('Pelapor Uji')
        ->assertSee('Suspend Produk')
        ->assertSee('Ban Penjual');
});

test('non admin cannot open report detail', function () {
    $this->actingAs($this->seller)
        ->get(route('admin.reports.show', $this->report))
        ->assertForbidden();
});

test('guest is redirected when opening report detail', function () {
    $this->get(route('admin.reports.show', $this->report))
        ->assertRedirect(route('home'));
});

test('admin marks a report as reviewed and reviewer is recorded', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.reports.moderate', $this->report), ['action' => 'review'])
        ->assertRedirect(route('admin.dashboard', ['tab' => 'laporan', 'report' => $this->report->id]))
        ->assertSessionHas('info');

    $this->report->refresh();

    expect($this->report->status)->toBe(Report::STATUS_REVIEWED)
        ->and($this->report->reviewed_by)->toBe($this->admin->id)
        ->and($this->report->reviewed_at)->not->toBeNull();
});

test('admin rejects a report', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.reports.moderate', $this->report), ['action' => 'reject'])
        ->assertRedirect();

    expect($this->report->refresh()->status)->toBe(Report::STATUS_REJECTED);
});

test('suspending a product hides it from the catalog and resolves the report', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.reports.moderate', $this->report), ['action' => 'suspend_product'])
        ->assertRedirect();

    expect($this->product->refresh()->status)->toBe(Product::STATUS_DITANGGUIHKAN)
        ->and($this->report->refresh()->status)->toBe(Report::STATUS_RESOLVED);

    $this->get(route('home'))->assertOk()->assertDontSee('Produk Yang Dilaporkan');

    $this->app['auth']->forgetGuards();

    $this->get($this->product->detailUrl())->assertNotFound();
});

test('product owner can still see a suspended product', function () {
    $this->product->update(['status' => Product::STATUS_DITANGGUIHKAN]);

    $this->actingAs($this->seller)
        ->get($this->product->detailUrl())
        ->assertOk()
        ->assertSee('Ditangguhkan');
});

test('admin can restore a suspended product', function () {
    $this->product->update(['status' => Product::STATUS_DITANGGUIHKAN]);

    $this->actingAs($this->admin)
        ->patch(route('admin.reports.moderate', $this->report), ['action' => 'restore_product'])
        ->assertRedirect();

    expect($this->product->refresh()->status)->toBe(Product::STATUS_DIJUAL);

    $this->get(route('home'))->assertOk()->assertSee('Produk Yang Dilaporkan');
});

test('banning a seller hides all of their products', function () {
    $otherProduct = Product::factory()->create([
        'user_id' => $this->seller->id,
        'judul' => 'Produk Lain Penjual',
        'status' => Product::STATUS_DIJUAL,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.reports.moderate', $this->report), ['action' => 'ban_seller'])
        ->assertRedirect();

    expect($this->seller->refresh()->is_banned)->toBeTrue()
        ->and($this->report->refresh()->status)->toBe(Report::STATUS_RESOLVED);

    $this->app['auth']->forgetGuards();

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Produk Yang Dilaporkan')
        ->assertDontSee('Produk Lain Penjual');

    $this->get($otherProduct->detailUrl())->assertNotFound();
});

test('unbanning a seller restores their catalog visibility', function () {
    $this->seller->forceFill(['is_banned' => true])->save();

    $this->actingAs($this->admin)
        ->patch(route('admin.reports.moderate', $this->report), ['action' => 'unban_seller'])
        ->assertRedirect();

    expect($this->seller->refresh()->is_banned)->toBeFalse();

    $this->get(route('home'))->assertOk()->assertSee('Produk Yang Dilaporkan');
});

test('admin account cannot be banned from a report', function () {
    $product = Product::factory()->create([
        'user_id' => $this->admin->id,
        'judul' => 'Produk Admin',
    ]);

    $report = Report::factory()->create([
        'product_id' => $product->id,
        'reporter_id' => $this->reporter->id,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.reports.moderate', $report), ['action' => 'ban_seller'])
        ->assertRedirect();

    expect($this->admin->refresh()->is_banned)->toBeFalse();
});

test('soft deleted product cannot be suspended', function () {
    $this->product->delete();

    $this->actingAs($this->admin)
        ->patch(route('admin.reports.moderate', $this->report), ['action' => 'suspend_product'])
        ->assertRedirect()
        ->assertSessionHas('info', 'Produk sudah dihapus, tidak bisa disuspend.');
});
