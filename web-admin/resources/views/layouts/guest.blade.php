<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'MYBANK')</title>

    {{-- ✅ Vite (con fallback si no hay build) --}}
    @php
        $manifest = public_path('build/manifest.json');
    @endphp

    @if(file_exists($manifest))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        {{-- Fallback sin Vite build (evita ViteManifestNotFoundException) --}}
        <link rel="stylesheet" href="{{ asset('fallback/app.css') }}">
        <script defer src="{{ asset('fallback/app.js') }}"></script>
    @endif

    {{-- ✅ Para estilos específicos por vista (login) --}}
    @stack('styles')
</head>
<body>
    <main>
        @yield('content')
    </main>

    {{-- ✅ Para scripts específicos por vista --}}
    @stack('scripts')
</body>
</html>




