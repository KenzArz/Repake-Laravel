<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RePake')</title>
    @hasSection('canonical')
        <link rel="canonical" href="@yield('canonical')">
        <meta property="og:url" content="@yield('canonical')">
    @endif
    <link rel="icon" type="image/png" href="{{ asset('images/repake-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/repake-logo.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Manrope:wght@400..800&display=swap"
        rel="stylesheet">

    @if (config('services.google.maps_key'))
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_key') }}&libraries=places&language=id"
            async defer></script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body
    class="min-h-screen bg-neutral-100/60 text-neutral-900 antialiased selection:bg-lime-300 selection:text-neutral-900"
    data-authed="{{ auth()->check() ? '1' : '0' }}">
    <x-navbar-user />

    {{-- Pemicu deteksi lokasi [ALDI]; label kota di navbar tetap milik RAFIS --}}
    @if (auth()->check())
        <span data-location-detect data-location-url="{{ route('location.store') }}" hidden></span>
    @endif

    @if (session('info'))
        <div class="mx-auto mt-3 max-w-6xl px-4 sm:px-6">
            <div class="rounded-xl border border-lime-300 bg-lime-50 p-3 text-xs font-semibold text-neutral-800"
                role="status">
                {{ session('info') }}
            </div>
        </div>
    @endif

    <div class="min-h-[calc(100vh-200px)]">
        <main>
            @yield('content')
        </main>

        @hasSection('hide-footer')
        @else
            <x-footer />
        @endif
    </div>

    <x-flash-toast />

    {{-- Pop-up Login SSO Google UNSIKA [ALE] --}}
    @includeIf('components.login-modal')

    @stack('scripts')
</body>

</html>