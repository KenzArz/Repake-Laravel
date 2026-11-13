<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database seeder fills core data and admin only, without dummy data', function () {
    $this->seed();

    expect(Category::count())->toBe(8)
        ->and(User::where('role', User::ROLE_ADMIN)->count())->toBe(1)
        ->and(Banner::count())->toBe(0)
        ->and(Product::count())->toBe(0)
        ->and(Report::count())->toBe(0);
});

test('database seeder is idempotent when run twice', function () {
    $this->seed();
    $this->seed();

    expect(Category::count())->toBe(8)
        ->and(User::where('role', User::ROLE_ADMIN)->count())->toBe(1)
        ->and(Banner::count())->toBe(0)
        ->and(Product::count())->toBe(0)
        ->and(Report::count())->toBe(0);
});

test('admin account is identified by the admin role', function () {
    $this->seed();

    $admin = User::where('role', User::ROLE_ADMIN)->sole();

    expect($admin->isAdmin())->toBeTrue();
});