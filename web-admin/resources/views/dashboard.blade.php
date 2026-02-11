<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard - MYBANK</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body style="padding:20px;font-family:system-ui;">
    <h2>Dashboard</h2>
    <p>Login OK ✅</p>

    <pre style="background:#f6f6f6;padding:12px;border-radius:8px;overflow:auto;">{{ json_encode(session('mybank_user'), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
</body>
</html>

