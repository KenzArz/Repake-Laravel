<?php

use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->admin = User::factory()->admin()->create();
});

test('admin uploads a banner image', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.banners.store'), [
            'title' => 'Promo Akhir Semester',
            'alt_text' => 'Diskon barang bekas akhir semester',
            'image' => UploadedFile::fake()->image('promo.jpg', 1200, 500),
        ])
        ->assertRedirect()
        ->assertSessionHas('info');

    $banner = Banner::where('title', 'Promo Akhir Semester')->sole();

    expect($banner->is_active)->toBeTrue()
        ->and($banner->sort_order)->toBe(1);

    Storage::disk('public')->assertExists($banner->image_path);
});

test('banner upload requires an image and a title', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.banners.store'), [])
        ->assertSessionHasErrors(['title', 'image']);

    expect(Banner::count())->toBe(0);
});

test('banner image must be a supported image', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.banners.store'), [
            'title' => 'Banner PDF',
            'image' => UploadedFile::fake()->create('banner.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors(['image']);

    expect(Banner::count())->toBe(0);
});

test('admin can deactivate a banner when more than three are active', function () {
    $banners = Banner::factory()->count(4)->sequence(
        ['sort_order' => 1],
        ['sort_order' => 2],
        ['sort_order' => 3],
        ['sort_order' => 4],
    )->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.banners.toggle', $banners->first()))
        ->assertRedirect()
        ->assertSessionHas('info');

    expect($banners->first()->refresh()->is_active)->toBeFalse();
});

test('deactivating a banner is blocked when only three are active', function () {
    $banners = Banner::factory()->count(3)->sequence(
        ['sort_order' => 1],
        ['sort_order' => 2],
        ['sort_order' => 3],
    )->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.banners.toggle', $banners->first()))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($banners->first()->refresh()->is_active)->toBeTrue();
});

test('deleting an active banner is blocked when only three are active', function () {
    $banners = Banner::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.banners.destroy', $banners->first()))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Banner::count())->toBe(3);
});

test('deleting a banner removes its uploaded file', function () {
    Storage::disk('public')->put('banners/hapus.jpg', 'dummy');

    $banner = Banner::factory()->create([
        'image_path' => 'banners/hapus.jpg',
        'is_active' => false,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.banners.destroy', $banner))
        ->assertRedirect()
        ->assertSessionHas('info');

    expect(Banner::count())->toBe(0);
    Storage::disk('public')->assertMissing('banners/hapus.jpg');
});

test('admin moves a banner up and the order is normalized', function () {
    $first = Banner::factory()->create(['title' => 'Satu', 'sort_order' => 1]);
    $second = Banner::factory()->create(['title' => 'Dua', 'sort_order' => 2]);
    $third = Banner::factory()->create(['title' => 'Tiga', 'sort_order' => 3]);

    $this->actingAs($this->admin)
        ->patch(route('admin.banners.move', $third), ['direction' => 'up'])
        ->assertRedirect()
        ->assertSessionHas('info');

    expect($first->refresh()->sort_order)->toBe(1)
        ->and($third->refresh()->sort_order)->toBe(2)
        ->and($second->refresh()->sort_order)->toBe(3);
});

test('the first banner cannot be moved up', function () {
    $first = Banner::factory()->create(['sort_order' => 1]);
    $second = Banner::factory()->create(['sort_order' => 2]);

    $this->actingAs($this->admin)
        ->patch(route('admin.banners.move', $first), ['direction' => 'up'])
        ->assertRedirect();

    expect($first->refresh()->sort_order)->toBe(1)
        ->and($second->refresh()->sort_order)->toBe(2);
});

test('homepage shows at most three active banners in the configured order', function () {
    Banner::factory()->create(['image_path' => 'images/banner-1.webp', 'is_active' => true, 'sort_order' => 1]);
    Banner::factory()->create(['image_path' => 'images/banner-2.webp', 'is_active' => true, 'sort_order' => 2]);
    Banner::factory()->create(['image_path' => 'images/banner-3.webp', 'is_active' => true, 'sort_order' => 3]);
    Banner::factory()->create(['image_path' => 'images/banner-4.webp', 'is_active' => true, 'sort_order' => 4]);
    Banner::factory()->create(['image_path' => 'images/banner-off.webp', 'is_active' => false, 'sort_order' => 0]);

    $response = $this->get(route('home'))->assertOk();

    $response->assertSee('images/banner-1.webp')
        ->assertSee('images/banner-2.webp')
        ->assertSee('images/banner-3.webp')
        ->assertDontSee('images/banner-4.webp')
        ->assertDontSee('images/banner-off.webp');

    $positions = [
        strpos($response->getContent(), 'images/banner-1.webp'),
        strpos($response->getContent(), 'images/banner-2.webp'),
        strpos($response->getContent(), 'images/banner-3.webp'),
    ];

    expect($positions[0])->toBeLessThan($positions[1])
        ->and($positions[1])->toBeLessThan($positions[2]);
});

test('admin reorders banners via drag and drop payload', function () {
    $first = Banner::factory()->create(['title' => 'Satu', 'sort_order' => 1]);
    $second = Banner::factory()->create(['title' => 'Dua', 'sort_order' => 2]);
    $third = Banner::factory()->create(['title' => 'Tiga', 'sort_order' => 3]);

    $this->actingAs($this->admin)
        ->patchJson(route('admin.banners.reorder'), [
            'order' => [$second->id, $first->id, $third->id],
        ])
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect($second->refresh()->sort_order)->toBe(1)
        ->and($first->refresh()->sort_order)->toBe(2)
        ->and($third->refresh()->sort_order)->toBe(3);
});

test('banner reorder rejects an incomplete payload', function () {
    $first = Banner::factory()->create(['sort_order' => 1]);
    Banner::factory()->create(['sort_order' => 2]);

    $this->actingAs($this->admin)
        ->patchJson(route('admin.banners.reorder'), [
            'order' => [$first->id],
        ])
        ->assertStatus(422)
        ->assertJson(['message' => 'Urutan banner tidak lengkap.']);

    expect($first->refresh()->sort_order)->toBe(1);
});

test('non admin cannot reorder banners', function () {
    Banner::factory()->create(['sort_order' => 1]);

    $this->actingAs(User::factory()->create())
        ->patchJson(route('admin.banners.reorder'), ['order' => [1]])
        ->assertForbidden();
});

test('non admin cannot manage banners', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.banners.store'), [])
        ->assertForbidden();
});
