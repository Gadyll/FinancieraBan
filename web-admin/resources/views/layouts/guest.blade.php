<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'MYBANK')</title>

    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Extras opcionales para head (si algún día lo ocupas) --}}
    @stack('head')

    {{-- ✅ Estilos específicos por vista (login) --}}
    @stack('styles')
</head>
<body style="min-height:100vh; margin:0;">
    <main>
        @yield('content')
    </main>

    {{-- ✅ Scripts específicos por vista --}}
    @stack('scripts')
</body>
</html>



