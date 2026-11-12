# Repake - Laravel Marketplace Platform

A marketplace application built with Laravel 13 for buying and selling second-hand goods among UNSIKA students, with Firebase Google authentication, favorites, and product reporting.

> **Status proyek**: sebagian fitur masih dalam pengembangan. Lihat bagian [Status Fitur](#status-fitur) di bawah sebelum menilai klaim pada bagian Features.

## Table of Contents

1. [Project Overview](#project-overview)
2. [Features](#features)
3. [Technology Stack](#technology-stack)
4. [System Requirements](#system-requirements)
5. [Installation](#installation)
6. [Project Structure](#project-structure)
7. [Configuration](#configuration)
8. [Development](#development)
9. [Testing](#testing)
10. [Deployment](#deployment)
11. [Contributing](#contributing)
12. [License](#license)

---

## Status Fitur

| Fitur | Status |
|---|---|
| Login Firebase (Google, domain UNSIKA) | ✅ Selesai |
| Halaman home, detail produk, favorit, profil, edit profil | ✅ Selesai |
| Favorit tersimpan di database | ✅ Selesai |
| Profil dengan data nyata (produk, rating, ulasan) | ✅ Selesai (rating/ulasan dari tabel `reviews`) |
| Jual produk (CRUD + upload 3 foto, konversi WebP) | ✅ Selesai |
| Pencarian & filter (kata kunci, kategori, kota) | ✅ Selesai |
| Chat & tawar harga (polling, order saat tawaran diterima) | ✅ Selesai |
| Notifikasi (pesan baru, tawaran baru, keputusan tawaran) | ✅ Selesai |
| Admin kategori (CRUD) | ✅ Selesai |
| Laporkan produk | 🟡 Sebagian — tersimpan, alur admin/solusi belum |
| API untuk Flutter | ❌ Belum |

> Rencana & catatan pengerjaan tim disimpan di folder `docs/` **di luar repositori ini** (tidak ikut di-commit).

---

## Project Overview

Repake is a marketplace platform specifically designed for the second-hand goods market. The application provides a complete ecosystem for buyers and sellers to interact, communicate, and transact safely. Built on Laravel 13 with modern frontend technologies, it offers a robust, scalable, and maintainable codebase.

### Key Objectives

- Facilitate peer-to-peer transactions for second-hand goods
- Provide communication between buyers and sellers (chat antar pengguna)
- Implement a comprehensive product management system
- Ensure secure and reliable user authentication
- Create an intuitive and responsive user interface

---

## Features

### Core Functionality

#### Product Management
- Complete CRUD operations for products
- Multiple image upload support per product
- Product categorization system
- Location-based product filtering
- Product search and filtering capabilities

#### User System
- User authentication (login, registration, logout)
- User profile management
- User location tracking for proximity-based features
- Activity tracking and history

#### Communication System
- Real-time conversation between users
- Message threading and history
- Conversation management
- Notification system for new messages

#### Favorites System
- Save products to favorites list
- Quick access to saved items
- Favorites management interface

#### Reporting System
- Report problematic products
- Report management for administrators
- Issue tracking and resolution

#### Theme System
- Custom Material Design 3 inspired theme
- 56 predefined colors
- 11 typography styles
- Comprehensive design system
- Converted from Flutter/Dart specifications

---

## Technology Stack

### Backend Technologies

| Technology | Version | Purpose |
|------------|---------|---------|
| Laravel Framework | 13.17 | Core application framework |
| PHP | 8.3+ | Server-side programming language |
| MySQL | 8.0+ | Primary relational database |
| Pest | 5.3 | Testing framework |
| Composer | 2.x | PHP dependency management |

### Frontend Technologies

| Technology | Version | Purpose |
|------------|---------|---------|
| TailwindCSS | 4.0 | Utility-first CSS framework |
| Vite | 8.0 | Frontend build tool and dev server |
| JavaScript (Vanilla) | ES6+ | Client-side interactivity |
| Axios | 1.21.0 | HTTP client library |

### Development Tools

| Tool | Purpose |
|------|---------|
| Laravel Boost | AI-powered development assistance |
| Laravel Pail | Real-time log monitoring |
| Laravel Pao | Performance monitoring |
| Laravel Pint | Code style formatter (PSR-12) |
| Laravel Tinker | Interactive REPL |

---

## System Requirements

### Minimum Requirements

- **Operating System:** Linux, macOS, or Windows
- **PHP:** Version 8.3 or higher
- **Composer:** Version 2.0 or higher
- **Node.js:** Version 18.x or higher
- **NPM:** Version 9.x or higher
- **MySQL:** Version 8.0 or higher, or **MariaDB 10.6+** (proyek ini memakai MariaDB; SQLite tidak didukung)
- **GD PHP Extension** (untuk upload & pemrosesan gambar)
- **Git:** Version 2.x or higher

### Required PHP Extensions

- PDO PHP Extension (driver `pdo_mysql` + `mysqli`)
- GD PHP Extension
- Mbstring PHP Extension
- OpenSSL PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension
- Ctype PHP Extension
- JSON PHP Extension
- BCMath PHP Extension
- Fileinfo PHP Extension

### Server Requirements

- Minimum 512MB RAM (1GB recommended)
- Minimum 1GB free disk space
- Apache 2.4+ or Nginx 1.18+ (for production)

---

## Installation

### Prerequisites (Wajib)

- **PHP 8.3+** dengan ekstensi: `pdo_mysql`, `mysqli`, `gd`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`
- **Composer 2.x**
- **Node.js 18+** dan **NPM 9+**
- **MariaDB 10.6+** atau **MySQL 8.0+** yang **sedang berjalan** — proyek ini **tidak mendukung SQLite**

### Langkah Instalasi (dari awal sampai akhir)

```bash
# 1. Clone repositori dan masuk ke folder
git clone https://github.com/[username]/repake-laravel.git
cd repake-laravel

# 2. Pasang dependensi PHP dan JavaScript
composer install
npm install

# 3. Siapkan environment dan kunci aplikasi
cp .env.example .env
php artisan key:generate

# 4. Buat database aplikasi dan database test (MariaDB/MySQL)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS repake_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS repake_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# Sesuaikan kredensial (DB_DATABASE, DB_USERNAME, DB_PASSWORD) di file .env

# 5. Jalankan migrasi dan seed
php artisan migrate
php artisan db:seed

# 6. Buat symbolic link storage (wajib agar upload gambar tampil)
php artisan storage:link

# 7. Build aset satu kali
npm run build

# 8. Jalankan aplikasi (lihat bagian "Menjalankan Aplikasi" di bawah)
composer dev
```

Akses aplikasi di: **http://localhost:8000**

> **Tips:** skrip `composer setup` di `composer.json` menggabungkan langkah 2–7
> (install → `.env` → key → migrate → npm → build) dalam satu perintah.

### Menjalankan Aplikasi (PENTING)

Gunakan **`composer dev`**, bukan `php artisan serve` sendirian:

```bash
composer dev
```

Perintah ini menjalankan `php artisan dev`, yang memulai **4 proses sekaligus**:

| Proses | Keterangan |
|---|---|
| `php artisan serve` | Server aplikasi di `http://127.0.0.1:8000` |
| `php artisan queue:listen` | Worker antrian (dibutuhkan notifikasi/email) |
| `php artisan pail` | Log real-time di terminal |
| `npm run dev` | Vite dev server dengan hot-reload aset |

> Mengganti dengan `php artisan serve` saja tetap bisa membuka aplikasi, tetapi
> **hot-reload (Live Reload) dan worker antrian tidak berjalan** — hasil edit CSS/JS
> tidak muncul, dan fitur yang bergantung antrian tidak aktif.

Untuk akses dari perangkat lain di jaringan yang sama, **Vite perlu bind ke alamat
antarmuka jaringan** (default-nya `localhost`, sehingga hanya bisa diakses dari
mesin ini). Set kedua variabel berikut di `.env` secara **bersamaan**:

```env
VITE_DEV_HOST=0.0.0.0        # alamat yang di-bind Vite (0.0.0.0 = semua antarmuka)
VITE_HMR_HOST=192.168.1.10   # IP LAN mesin ini — WAJIB diisi bila VITE_DEV_HOST=0.0.0.0
```

Kemudian jalankan ulang `composer dev`, dan perangkat lain membuka
`http://<IP-mesin>:8000`.

> ⚠️ Jangan set `VITE_DEV_HOST=0.0.0.0` tanpa `VITE_HMR_HOST`. `0.0.0.0` adalah
> alamat bind, bukan alamat tujuan: browser (terutama Windows/Chrome) gagal
> memuat aset dari `http://0.0.0.0:5173` dengan `ERR_ADDRESS_INVALID`.

---

## Project Structure

### Directory Organization

```
repake-laravel/
├── app/
│   ├── Console/                    # Console commands
│   ├── Exceptions/                 # Exception handlers
│   ├── Helpers/                    # Helper classes
│   │   └── ThemeHelper.php
│   ├── Http/
│   │   ├── Controllers/            # Application controllers
│   │   ├── Middleware/             # HTTP middleware
│   │   └── Requests/               # Form request validators
│   ├── Models/                     # Eloquent models
│   └── Providers/                  # Service providers
│
├── bootstrap/
│   └── app.php                     # Application bootstrapping
│
├── config/                         # Configuration files
│   ├── app.php
│   ├── database.php
│   ├── theme.php                   # Custom theme configuration
│   └── ...
│
├── database/
│   ├── factories/                  # Model factories
│   ├── migrations/                 # Database migrations
│   └── seeders/                    # Database seeders
│
├── public/                         # Public web root
│   ├── build/                      # Compiled assets
│   └── index.php                   # Application entry point
│
├── resources/
│   ├── css/
│   │   ├── app.css                 # Main CSS entry point
│   │   └── theme/                  # Theme system files
│   │       ├── colors.css
│   │       ├── typography.css
│   │       ├── theme.css
│   │       └── README.md
│   ├── js/
│   │   ├── app.js                  # Main JavaScript entry point
│   │   └── bootstrap.js
│   └── views/                      # Blade templates
│
├── routes/
│   ├── web.php                     # Web routes
│   ├── api.php                     # API routes
│   └── console.php                 # Console routes
│
├── storage/
│   ├── app/                        # Application files
│   ├── framework/                  # Framework files
│   └── logs/                       # Application logs
│
├── tests/
│   ├── Feature/                    # Feature tests
│   └── Unit/                       # Unit tests
│
├── theme/                          # Original Dart theme files
│   ├── app_colors.dart
│   ├── app_text_styles.dart
│   ├── app_theme.dart
│   └── CONVERSION_SUMMARY.md
│
├── .env.example                    # Environment template
├── .gitignore                      # Git ignore rules
├── composer.json                   # PHP dependencies
├── package.json                    # JavaScript dependencies
├── phpunit.xml                     # PHPUnit configuration
├── vite.config.js                  # Vite configuration
├── README.md                       # This file
```

### Key Components

#### Controllers

| Controller | Responsibility |
|------------|----------------|
| `HomeController` | Halaman beranda (kategori, produk, banner) |
| `ProductController` | Listing, pencarian/filter, dan form jual produk |
| `ProductDetailController` | Detail produk & laporan produk |
| `FavoriteController` | Toggle & daftar favorit |
| `ProfileController` | Profil, katalog, edit profil |
| `ChatController` | Chat, tawaran harga, order saat tawaran diterima |
| `NotificationController` | Notifikasi pengguna |
| `LocationController` | Lokasi pengguna dari browser |
| `Admin\CategoryController` | CRUD kategori (admin) |
| `Auth\FirebaseAuthController` | Login/logout via Firebase Google |

#### Models

| Model | Description |
|-------|-------------|
| `User` | User accounts and authentication |
| `Product` | Product listings |
| `ProductImage` | Product images (one-to-many with Product) |
| `Category` | Product categories |
| `Favorite` | User favorites |
| `Conversation` | Chat conversations between users |
| `Message` | Chat messages (text & tawaran) |
| `Report` | Product reports |
| `Order` | Transaksi dari tawaran yang disepakati |
| `Review` | Ulasan pembeli untuk penjual |
| `CodArea` | Wilayah layanan COD |

#### Database Schema

**Core Tables:**
- `users` - User accounts
- `sessions` - Session management
- `password_reset_tokens` - Password reset functionality
- `cache` - Application cache
- `jobs` - Queue system
- `failed_jobs` - Failed queue jobs

**Application Tables:**
- `categories` - Product categories
- `products` - Product listings
- `product_images` - Product images
- `product_cod_areas` - Pivot produk & wilayah COD
- `cod_areas` - Wilayah layanan COD
- `favorites` - User favorites
- `conversations` - User conversations
- `messages` - Chat messages
- `reports` - Product reports
- `orders` - Transaksi dari tawaran yang disepakati
- `reviews` - Ulasan pembeli untuk penjual
- `notifications` - Notifikasi pengguna

---

## Configuration

### Environment Configuration

The `.env` file contains all environment-specific configuration. Key configuration areas include:

#### Application Settings

```env
APP_NAME="Repake Marketplace"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

#### Database Configuration

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=repake_db
DB_USERNAME=root
DB_PASSWORD=
```

#### Session Management

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
```

#### Cache Configuration

```env
CACHE_STORE=database
CACHE_PREFIX=
```

#### Queue Configuration

```env
QUEUE_CONNECTION=database
```

#### Mail Configuration

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

#### Firebase Authentication

Login website memakai **Firebase Authentication (provider Google)**. ID token diverifikasi di server, lalu domain email dibatasi di Laravel.

Aktifkan di Firebase Console:

1. **Build → Authentication → Sign-in method → Google → Enable**.
2. **Authentication → Settings → Authorized domains**: pastikan `localhost` ada.
3. **Project settings → General → Your apps → Web app**: salin `apiKey`, `authDomain`, `projectId`, `appId`.
4. Isi `.env`:

```env
FIREBASE_API_KEY=
FIREBASE_AUTH_DOMAIN=
FIREBASE_PROJECT_ID=
FIREBASE_APP_ID=
# Domain email yang boleh login; subdomain ikut diterima.
FIREBASE_ALLOWED_DOMAINS=unsika.ac.id
```

Setelah mengubah `.env`, jalankan `php artisan config:clear`.

Catatan: `apiKey` Firebase bukan rahasia (terlihat di browser). Jangan membatasinya dengan *HTTP referrer* jika key yang sama dipakai server untuk `accounts:lookup`, karena request server tidak mengirim referrer dan verifikasi login akan gagal.

### Theme Configuration

The theme system is configured in `config/theme.php` and includes:

- Color palette (56 colors)
- Typography system (11 text styles)
- Material Design 3 color scheme
- Semantic color mappings

For detailed theme documentation, see `resources/css/theme/README.md`.

---

## Development

### Local Development Setup

#### Starting Development Servers

Cara utama menjalankan aplikasi (server + queue + log + Vite hot-reload sekaligus):

```bash
composer dev
```

Alternatif manual:

```bash
# Hanya server Laravel (tanpa queue & hot-reload)
php artisan serve

# Hanya Vite dev server (untuk hot module replacement)
npm run dev
```

#### Building Assets

**Development Build:**
```bash
npm run dev
```

**Production Build:**
```bash
npm run build
```

### Code Quality

#### Code Formatting

The project uses Laravel Pint for automated code formatting according to PSR-12 standards.

```bash
# Format all PHP files
./vendor/bin/pint

# Check formatting without making changes
./vendor/bin/pint --test

# Format specific file
./vendor/bin/pint app/Models/User.php
```

#### Code Style Guidelines

- Follow PSR-12 coding standards
- Use meaningful variable and method names
- Write comprehensive PHPDoc comments
- Keep methods focused and concise
- Follow Laravel naming conventions

### Database Management

#### Running Migrations

```bash
# Run all pending migrations
php artisan migrate

# Rollback last migration batch
php artisan migrate:rollback

# Reset and re-run all migrations
php artisan migrate:fresh

# Run migrations with seeding
php artisan migrate:fresh --seed
```

#### Creating Migrations

```bash
# Create a new migration
php artisan make:migration create_products_table

# Create migration with model
php artisan make:model Product -m
```

#### Database Seeding

```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=CategorySeeder
```

### Artisan Commands

#### Common Commands

```bash
# List all available commands
php artisan list

# Clear application cache
php artisan cache:clear

# Clear configuration cache
php artisan config:clear

# Clear view cache
php artisan view:clear

# Clear route cache
php artisan route:clear

# List all routes
php artisan route:list

# Generate application key
php artisan key:generate

# Create storage link
php artisan storage:link
```

#### Creating Application Components

```bash
# Create controller
php artisan make:controller ProductController

# Create model
php artisan make:model Product

# Create model with migration and factory
php artisan make:model Product -mf

# Create middleware
php artisan make:middleware CheckAdmin

# Create request
php artisan make:request StoreProductRequest

# Create seeder
php artisan make:seeder ProductSeeder

# Create factory
php artisan make:factory ProductFactory
```

### Interactive Development

#### Laravel Tinker

Laravel Tinker provides an interactive REPL for the application.

```bash
# Start Tinker session
php artisan tinker

# Example usage
>>> User::count()
>>> User::factory()->create()
>>> Product::all()
>>> cache()->get('key')
```

### Logging and Monitoring

#### Real-time Log Monitoring

```bash
# Monitor logs in real-time
php artisan pail

# Filter by log level
php artisan pail --level=error

# Filter by message
php artisan pail --filter="exception"
```

---

## Testing

### Testing Framework

The project uses Pest PHP as the primary testing framework, providing an elegant and expressive testing syntax.

> **Sebelum menjalankan test**, pastikan database `repake_testing` sudah dibuat
> (lihat langkah 4 di bagian Installation) dan ekstensi PHP `gd` terpasang
> (dibutuhkan test upload gambar).

### Running Tests

```bash
# Run all tests
composer test

# Or using Artisan
php artisan test

# Or using Pest directly
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/ExampleTest.php

# Run with coverage report
./vendor/bin/pest --coverage

# Run tests in parallel
./vendor/bin/pest --parallel
```

### Creating Tests

```bash
# Create feature test
php artisan make:test ProductTest

# Create unit test
php artisan make:test ProductTest --unit

# Create Pest test explicitly
php artisan make:test ProductTest --pest
```

### Test Structure

```
tests/
├── Feature/                    # Integration tests
│   └── ExampleTest.php
├── Unit/                       # Unit tests
│   └── ExampleTest.php
├── Pest.php                    # Pest configuration
└── TestCase.php                # Base test case
```

### Writing Tests

Example feature test:

```php
<?php

test('homepage displays welcome message', function () {
    $response = $this->get('/');
    
    $response->assertStatus(200);
    $response->assertSee('Welcome');
});
```

Example unit test:

```php
<?php

test('user can be created', function () {
    $user = User::factory()->create();
    
    expect($user)->toBeInstanceOf(User::class);
    expect($user->email)->toBeString();
});
```

---

## Deployment

### Production Considerations

#### Environment Configuration

1. Set `APP_ENV=production`
2. Set `APP_DEBUG=false`
3. Configure production database credentials
4. Set up proper mail driver (SMTP, SES, etc.)
5. Configure cache driver (Redis recommended)
6. Configure session driver (Redis or database)
7. Configure queue driver (Redis, SQS, or database)

#### Optimization Commands

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Build production assets
npm run build
```

#### Server Configuration

**Apache Configuration:**
```apache
<VirtualHost *:80>
    DocumentRoot /var/www/repake-laravel/public
    
    <Directory /var/www/repake-laravel/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx Configuration:**
```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/repake-laravel/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### Security Checklist

- [ ] Disable debug mode
- [ ] Configure proper CORS settings
- [ ] Set up SSL/TLS certificates
- [ ] Configure proper file permissions (755 for directories, 644 for files)
- [ ] Secure storage and bootstrap/cache directories
- [ ] Configure rate limiting
- [ ] Set up firewall rules
- [ ] Configure database connection security
- [ ] Enable CSRF protection
- [ ] Implement proper authentication and authorization

---

## Contributing

### Git Workflow

#### Branch Naming Convention

- `feature/feature-name` - New features
- `bugfix/bug-description` - Bug fixes
- `hotfix/critical-issue` - Critical production fixes
- `refactor/component-name` - Code refactoring
- `docs/documentation-update` - Documentation updates

#### Commit Message Convention

Follow the Conventional Commits specification:

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, semicolons, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks
- `perf`: Performance improvements

**Examples:**
```bash
git commit -m "feat(products): add image upload functionality"
git commit -m "fix(auth): resolve session timeout issue"
git commit -m "docs(readme): update installation instructions"
```

#### Pull Request Process

1. Create a feature branch from `main`
2. Make your changes
3. Write or update tests
4. Ensure all tests pass
5. Run code formatter (`./vendor/bin/pint`)
6. Commit changes with conventional commit messages
7. Push to your fork
8. Create a pull request with detailed description
9. Wait for code review
10. Address review comments
11. Merge after approval

### Code Review Guidelines

- Review for functionality, not style (Pint handles style)
- Check for security vulnerabilities
- Verify test coverage
- Ensure documentation is updated
- Check for performance implications
- Verify database migrations are reversible

---

## License

This project is open-sourced software licensed under the [MIT License](https://opensource.org/licenses/MIT).

---

## Documentation

### Additional Resources

- [Installation Guide](#installation) - Step-by-step installation instructions
- [Theme Documentation](resources/css/theme/README.md) - Theme system guide
- [Laravel Documentation](https://laravel.com/docs) - Official Laravel documentation
- [Pest Documentation](https://pestphp.com/docs) - Testing framework documentation
- [TailwindCSS Documentation](https://tailwindcss.com/docs) - CSS framework documentation

### Project Roadmap

#### Phase 1: Foundation ✅
- [x] Project setup and configuration
- [x] Theme system implementation
- [x] Complete database schema
- [x] Authentication system (Firebase Google)
- [x] Basic CRUD operations

#### Phase 2: Core Features ✅
- [x] Product management with image upload
- [x] Category management (admin)
- [x] User profile management
- [x] Search and filtering
- [x] Pagination

#### Phase 3: Advanced Features
- [x] Chat system & tawar harga
- [x] Favorites functionality
- [ ] Reporting system (alur admin/solusi laporan)
- [x] Notifications (pesan & tawaran)
- [x] Location-based features (deteksi lokasi browser)
- [ ] API untuk aplikasi Flutter

#### Phase 4: Optimization
- [ ] Performance optimization
- [ ] Caching implementation
- [ ] Queue system for heavy operations
- [ ] Full test coverage
- [ ] Security audit

#### Phase 5: Production
- [ ] Production deployment
- [ ] Monitoring and logging
- [ ] Backup strategy
- [ ] Documentation completion
- [ ] User training materials

---

## Support

For issues, questions, or contributions, please refer to the project repository or contact the development team.

**Project Repository:** [GitHub Repository URL]

**Documentation:** [Documentation Site URL]

**Issue Tracker:** [Issues URL]

---

**Version:** 1.0.0

**Last Updated:** November 2026

**Maintainer:** Development Team
