<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#007aff">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.webmanifest">
    <title inertia>Northlink Quebar</title>
    @php
        app(\Illuminate\Foundation\Vite::class)->createAssetPathsUsing(
            fn (string $path): string => '/'.ltrim($path, '/'),
        );
    @endphp
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="bg-gray-50">
    @inertia
</body>
</html>
