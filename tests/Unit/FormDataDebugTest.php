<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormDataDebugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_key_images_is_read_correctly(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600)->size(500);

        $response = $this->post(route('products.store'), [
            'judul' => 'Test',
            'category_id' => Category::factory()->create()->id,
            'harga' => 100000,
            'kondisi' => 'Seperti baru',
            'deskripsi' => 'Deskripsi lengkap.',
            'kota' => 'Test',
            'images' => [$file],
        ]);

        $response->assertRedirect(); // non-AJAX → redirect 302
    }

    public function test_multiple_images_as_array(): void
    {
        $file1 = UploadedFile::fake()->image('test1.jpg', 800, 600)->size(500);
        $file2 = UploadedFile::fake()->image('test2.jpg', 800, 600)->size(500);

        $response = $this->post(route('products.store'), [
            'judul' => 'Test',
            'category_id' => Category::factory()->create()->id,
            'harga' => 100000,
            'kondisi' => 'Seperti baru',
            'deskripsi' => 'Deskripsi lengkap.',
            'kota' => 'Test',
            'images' => [$file1, $file2],
        ]);

        $response->assertRedirect();
    }

    public function test_json_wants_with_images(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 800, 600)->size(500);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('products.store'), [
                'judul' => 'Test',
                'category_id' => Category::factory()->create()->id,
                'harga' => 100000,
                'kondisi' => 'Seperti baru',
                'deskripsi' => 'Deskripsi lengkap.',
                'kota' => 'Test',
                'images' => [$file],
            ]);

        $response->assertJsonPath('redirect', route('profile.catalog'));
    }
}
