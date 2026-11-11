<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadDebugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_single_file_from_array_works(): void
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
                'images' => [$file], // array (how browser FormData sends multiple files)
            ]);

        $response->assertJsonPath('redirect', route('profile.catalog'));
        $this->assertDatabaseHas('products', ['user_id' => $user->id, 'judul' => 'Test']);
    }

    public function test_array_of_two_files_works(): void
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
    }

    public function test_no_file_fails(): void
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
}
