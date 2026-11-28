<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * Tiruan respons Firebase Identity Toolkit untuk menguji jalur login produksi.
 * Nama fungsi dibedakan dari FirebaseAuthTest agar tidak bentrok.
 */
function fakeDemoFirebaseLookup(array $overrides = []): void
{
    Http::fake([
        'identitytoolkit.googleapis.com/*' => Http::response([
            'users' => [array_merge([
                'localId' => 'demo-firebase-uid',
                'email' => 'juri.pembeli@demo.local',
                'displayName' => 'Alya Ramadhani',
                'emailVerified' => true,
            ], $overrides)],
        ]),
    ]);
}

test('demo login is not found when demo mode is off', function () {
    config(['demo.enabled' => false]);

    $this->get(route('demo.login', ['persona' => 'pembeli']))->assertNotFound();
});

test('demo login rejects a persona outside the configured list', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'penyusup']))->assertNotFound();
    $this->assertGuest();
});

test('demo login signs in the expected persona', function (string $persona, string $role, string $tujuan) {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => $persona]))
        ->assertRedirect($tujuan);

    $user = User::query()->where('email', config("demo.personas.{$persona}"))->sole();

    $this->assertAuthenticatedAs($user);
    expect($user->role)->toBe($role)
        ->and($user->email_verified_at)->not->toBeNull();
})->with([
    ['pembeli', User::ROLE_MAHASISWA, '/'],
    ['penjual', User::ROLE_MAHASISWA, '/'],
    ['admin', User::ROLE_ADMIN, '/admin/dashboard'],
]);

test('demo login writes the profile session used by the navbar', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'pembeli']))->assertRedirect(route('home'));

    $profile = session('user_profile');

    expect($profile)->toBeArray()
        ->and($profile['name'])->toBe(config('demo.names.pembeli'))
        ->and($profile['email'])->toBe(config('demo.personas.pembeli'))
        ->and($profile['kota'])->not->toBeEmpty();
});

test('demo login keeps an internal destination', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'penjual', 'to' => '/chat/1']))
        ->assertRedirect('/chat/1');
});

test('demo login never redirects to an external destination', function (string $destination) {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'pembeli', 'to' => $destination]))
        ->assertRedirect(route('home'));
})->with([
    'absolute url' => 'https://evil.com',
    'protocol relative' => '//evil.com',
    'backslash trick' => '/\\evil.com',
    'javascript scheme' => 'javascript:alert(1)',
]);

test('demo login sends a non-admin persona to home from an admin page', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'pembeli', 'to' => '/admin/dashboard']))
        ->assertRedirect(route('home'));

    $this->get(route('demo.login', ['persona' => 'penjual', 'to' => '/admin/dashboard']))
        ->assertRedirect(route('home'));
});

test('demo login sends a non-admin persona to home from a page it does not own', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'pembeli', 'to' => '/products/abc/edit']))
        ->assertRedirect(route('home'));
});

test('demo login keeps a safe page with its query string', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'pembeli', 'to' => '/products?kategori=olahraga']))
        ->assertRedirect('/products?kategori=olahraga');

    $this->get(route('demo.login', ['persona' => 'penjual', 'to' => '/produk/tumbler-corkcicle-750ml']))
        ->assertRedirect('/produk/tumbler-corkcicle-750ml');
});

test('demo login rejects a destination with traversal segments', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'pembeli', 'to' => '/produk/../admin/dashboard']))
        ->assertRedirect(route('home'));
});

test('demo admin lands on the dashboard from a non-admin page', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'admin', 'to' => '/chat/1']))
        ->assertRedirect('/admin/dashboard');
});

test('demo admin keeps an admin page', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'admin', 'to' => '/admin/reports/abc']))
        ->assertRedirect('/admin/reports/abc');
});

test('demo accounts cannot sign in through the production firebase path', function () {
    $this->seed(DemoSeeder::class);

    fakeDemoFirebaseLookup(['email' => 'juri.pembeli@demo.local']);

    $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    $this->assertGuest();
});

test('demo buyer sees seeded products and a pending offer', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'pembeli']))->assertRedirect(route('home'));

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Tumbler Corkcicle 750ml');

    $this->get(route('chat.index'))
        ->assertOk()
        ->assertSee('Tawaran menunggu keputusan')
        ->assertSee('Monitor LG 24MK430H 24 Inch IPS');
});

test('demo panel keeps the current conversation when switching persona', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'pembeli']))->assertRedirect(route('home'));

    $conversation = Conversation::query()->orderBy('id')->firstOrFail();

    $this->get(route('chat.show', ['conversation' => $conversation]))
        ->assertOk()
        ->assertSee('demo/masuk/penjual?to='.urlencode('/chat/'.$conversation->id));
});

test('demo panel only offers the reset button to the admin persona', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    // Tamu: panel tampil, tetapi tombol reset dan catatan kaki tidak.
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Reset Data Demo')
        ->assertDontSee('Reset data demo hanya tersedia untuk persona Admin.');

    // Pembeli: tetap tanpa tombol reset.
    $this->get(route('demo.login', ['persona' => 'pembeli']));

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Reset Data Demo');

    // Admin: tombol reset tersedia di dashboard.
    $this->get(route('demo.login', ['persona' => 'admin', 'to' => '/admin/dashboard']));

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Reset Data Demo');
});

test('demo reset is only reachable by the admin persona in demo mode', function () {
    config(['demo.enabled' => false]);
    $this->post(route('demo.reset'))->assertNotFound();

    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'pembeli']));

    $this->post(route('demo.reset'))->assertForbidden();

    $this->post(route('logout'));

    $this->post(route('demo.reset'))->assertForbidden();
});

test('demo reset restores the seeded dataset without rebuilding the schema', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    User::query()->forceCreate([
        'name' => 'Penyusup',
        'email' => 'penyusup@demo.local',
        'password' => 'password',
    ]);

    expect(User::count())->toBe(4);

    $this->get(route('demo.login', ['persona' => 'admin', 'to' => '/admin/dashboard']));

    $this->post(route('demo.reset'))
        ->assertRedirect(route('demo.login', ['persona' => 'admin', 'to' => '/admin/dashboard']));

    expect(User::count())->toBe(3)
        ->and(Product::count())->toBe(14)
        ->and(Conversation::count())->toBe(3);
});

test('demo reset command refuses to run outside demo mode', function () {
    config(['demo.enabled' => false]);

    $this->artisan('demo:reset', ['--force' => true])
        ->expectsOutputToContain('Mode demo nonaktif')
        ->assertFailed();
});

test('demo panel and persona access only render when demo mode is on', function () {
    config(['demo.enabled' => true]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Tampilan mode demo')
        ->assertSee('Ganti Akun Persona')
        ->assertSee('Masuk dengan Google')
        ->assertSee('Masuk dengan akun persona')
        ->assertSee('#login', false)
        ->assertSee('#persona', false)
        ->assertSee(config('demo.production_url'), false)
        ->assertSee(config('demo.url'), false)
        ->assertDontSee('Pilih akun persona demo')
        ->assertDontSee('Masuk dengan Google @unsika.ac.id');

    config(['demo.enabled' => false]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Tampilan mode demo')
        ->assertDontSee('Ganti Akun Persona')
        ->assertDontSee('Pilih akun persona demo')
        ->assertSee('Masuk dengan Google')
        ->assertSee('Masuk dengan akun persona')
        ->assertSee('#login', false)
        ->assertSee('#persona', false)
        ->assertSee(config('demo.production_url'), false)
        ->assertSee(config('demo.url'), false)
        ->assertDontSee('Continue with Google')
        ->assertDontSee('Buka demo penilaian');
});

test('demo panel shows the seeded persona display names', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(config('demo.names.pembeli'))
        ->assertSee(config('demo.names.penjual'))
        ->assertSee(config('demo.names.admin'));
});

test('demo seeder uses unsplash product photos and local banner assets', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $cover = Product::query()
        ->where('judul', 'Tumbler Corkcicle 750ml')
        ->sole()
        ->images()
        ->where('is_cover', true)
        ->sole();

    expect($cover->path)->toStartWith('https://images.unsplash.com/')
        ->and($cover->path_url)->toBe($cover->path);

    expect(Banner::query()->where('sort_order', 1)->sole()->image_path)
        ->toBe('images/banners/repake-promo.webp')
        ->and(Banner::query()->where('sort_order', 2)->sole()->image_path)
        ->toBe('images/banners/keyboard.webp')
        ->and(Banner::query()->where('sort_order', 3)->sole()->image_path)
        ->toBe('images/banners/earbuds.webp');

    expect(Category::query()->where('slug', 'elektronik')->sole()->icon)
        ->toBe('/images/categories/elektronik.webp');
});
