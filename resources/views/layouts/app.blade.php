<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RePake')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Manrope:wght@400..800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen">
    <x-navbar-user />

    {{-- pb-16 di mobile supaya tidak tertutup bottom nav --}}
    <div class="pb-16 md:pb-0">
        <main>
            @yield('content')
        </main>
        <x-footer />
    </div>

    {{-- Dibuat oleh ALE; aman kalau belum ada --}}
    @guest
        @includeIf('components.login-modal')
    @endguest

    @stack('scripts')
</body>
</html>