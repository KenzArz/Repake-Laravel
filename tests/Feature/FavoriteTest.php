<?php

test('user can view favorites page', function () {
    $response = $this->get(route('favorites.index'));

    $response->assertStatus(200);
    $response->assertSee('Produk Favorit');
});

test('user can toggle favorite via ajax', function () {
    $response = $this->postJson(route('favorites.toggle', 1));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'is_favorite' => true,
            'product_id' => 1,
        ]);

    expect(session('favorites'))->toContain(1);

    // Toggle again to remove
    $responseRemove = $this->postJson(route('favorites.toggle', 1));

    $responseRemove->assertStatus(200)
        ->assertJson([
            'success' => true,
            'is_favorite' => false,
            'product_id' => 1,
        ]);

    expect(session('favorites'))->not->toContain(1);
});
