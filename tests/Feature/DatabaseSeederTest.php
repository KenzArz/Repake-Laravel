<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database seeder fills categories, products, admin, and demo user', function () {
    $this->seed();

    expect(Category::count())->toBe(8)
        ->and(Product::count())->toBe(12)
        ->and(User::where('role', User::ROLE_ADMIN)->count())->toBe(1)
        ->and(User::where('email', 'test@example.com')->count())->toBe(1);
});

test('database seeder is idempotent when run twice', function () {
    $this->seed();
    $this->seed();

    expect(Category::count())->toBe(8)
        ->and(Product::count())->toBe(12);
});

test('every seeded product has a cover image and a seller', function () {
    $this->seed();

    $products = Product::query()->with(['coverImage', 'seller'])->get();

    expect($products)->toHaveCount(12);

    foreach ($products as $product) {
        expect($product->coverImage)->not->toBeNull()
            ->and($product->seller)->not->toBeNull()
            ->and($product->category_id)->not->toBeNull();
    }
});

test('admin account is identified by the isAdmin helper', function () {
    $this->seed();

    $admin = User::where('role', User::ROLE_ADMIN)->sole();

    expect($admin->isAdmin())->toBeTrue()
        ->and(User::where('email', 'test@example.com')->sole()->isAdmin())->toBeFalse();
});
