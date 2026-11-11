<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $seller = User::factory()->create();
    $category = Category::factory()->create();

    $this->product = Product::factory()->create([
        'user_id' => $seller->id,
        'category_id' => $category->id,
    ]);
});

test('user can view product detail page', function () {
    $response = $this->get(route('products.show', $this->product));

    $response->assertStatus(200);
    $response->assertSee('Detail Produk');
    $response->assertSee('Chat Penjual');
    $response->assertSee('Laporkan');
});

test('returns 404 for non-existent product', function () {
    $response = $this->get(route('products.show', 99999));

    $response->assertStatus(404);
});

test('authenticated user can submit report for a product', function () {
    Storage::fake('public');

    $reporter = User::factory()->create();
    $response = $this->actingAs($reporter)
        ->post(route('products.report', $this->product), [
            'alasan' => 'penipuan',
            'keterangan' => 'Barang ini terindikasi penipuan dan mencurigakan.',
            'bukti' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('report_success');

    $this->assertDatabaseHas('reports', [
        'product_id' => $this->product->id,
        'reporter_id' => $reporter->id,
        'alasan' => 'penipuan',
        'keterangan' => 'Barang ini terindikasi penipuan dan mencurigakan.',
        'status' => Report::STATUS_PENDING,
    ]);

    expect(Report::first()?->bukti)->not->toBeNull();
});

test('guest cannot submit a report', function () {
    $response = $this->post(route('products.report', $this->product), [
        'alasan' => 'penipuan',
        'keterangan' => 'Barang ini terindikasi penipuan.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseCount('reports', 0);
});

test('product owner cannot report their own product', function () {
    $owner = User::find($this->product->user_id);

    $response = $this->actingAs($owner)
        ->post(route('products.report', $this->product), [
            'alasan' => 'penipuan',
            'keterangan' => 'Produk saya sendiri.',
        ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('reports', 0);
});

test('validates report form input', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('products.report', $this->product), [
            'alasan' => 'invalid_reason',
            'keterangan' => '',
        ]);

    $response->assertSessionHasErrors(['alasan', 'keterangan']);
});
