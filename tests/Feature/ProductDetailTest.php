<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

test('user can submit report for a product', function () {
    $response = $this->post(route('products.report', $this->product), [
        'alasan' => 'penipuan',
        'keterangan' => 'Barang ini terindikasi penipuan dan mencurigakan.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('report_success');

    $reports = session('reports');
    expect($reports)->not->toBeEmpty();
    expect($reports[0]['alasan'])->toBe('penipuan');
});

test('validates report form input', function () {
    $response = $this->post(route('products.report', $this->product), [
        'alasan' => 'invalid_reason',
        'keterangan' => '',
    ]);

    $response->assertSessionHasErrors(['alasan', 'keterangan']);
});
