<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormDataArrayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_single_file_via_key_images(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 800, 600)->size(500);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('products.store'), [
                '_token' => csrf_token(),
                'judul' => 'Test',
                'category_id' => Category::factory()->create()->id,
                'harga' => 100000,
                'kondisi' => 'Seperti baru',
                'deskripsi' => 'Deskripsi lengkap.',
                'kota' => 'Test',
                'images' => [$file],
            ]);

        $response->assertJsonPath('redirect', route('profile.catalog'));
        $this->assertDatabaseHas('products', ['user_id' => $user->id, 'judul' => 'Test']);
    }

    public function test_two_files_via_key_images(): void
    {
        $user = User::factory()->create();
        $file1 = UploadedFile::fake()->image('test1.jpg', 800, 600)->size(500);
        $file2 = UploadedFile::fake()->image('test2.jpg', 800, 600)->size(500);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('products.store'), [
                '_token' => csrf_token(),
                'judul' => 'Test',
                'category_id' => Category::factory()->create()->id,
                'harga' => 100000,
                'kondisi' => 'Seperti baru',
                'deskripsi' => 'Deskripsi lengkap.',
                'kota' => 'Test',
                'images' => [$file1, $file2],
            ]);

        $response->assertJsonPath('redirect', route('profile.catalog'));
        $product = $this->assertDatabaseHas('products', ['user_id' => $user->id]);
        $this->assertEquals(2, ProductImage::where('product_id', Product::first()->id)->count());
    }

    public function test_no_images_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('products.store'), [
                '_token' => csrf_token(),
                'judul' => 'Test',
                'category_id' => Category::factory()->create()->id,
                'harga' => 100000,
                'kondisi' => 'Seperti baru',
                'deskripsi' => 'Deskripsi lengkap.',
                'kota' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('images');
    }

    public function test_images_as_string_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('products.store'), [
                '_token' => csrf_token(),
                'judul' => 'Test',
                'category_id' => Category::factory()->create()->id,
                'harga' => 100000,
                'kondisi' => 'Seperti baru',
                'deskripsi' => 'Deskripsi lengkap.',
                'kota' => 'Test',
                'images' => 'not_an_array',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('images');
    }
}
