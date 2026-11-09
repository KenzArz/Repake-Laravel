<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RePake')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/repake-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/repake-logo.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Manrope:wght@400..800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-neutral-100/60 text-neutral-900 antialiased selection:bg-lime-300 selection:text-neutral-900">
    <x-navbar-user />

    <div class="min-h-[calc(100vh-200px)]">
        <main>
            @yield('content')
        </main>
        <x-footer />
    </div>

    {{-- Pop-up Login SSO Google UNSIKA [ALE] --}}
    @includeIf('components.login-modal')

    @stack('scripts')
</body>
</html>