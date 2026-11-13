{{-- Root HTML of the admin. Screens are React pages (Inertia). --}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="storage-url" content="{{ \Illuminate\Support\Facades\Storage::disk('public')->url('') }}">
    <title inertia>{{ $settings->get('site_name') }} Admin</title>
    <link rel="icon" href="{{ $settings->fileUrl('favicon') ?? asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:600,700|inter:400,500,600|jetbrains-mono:400&display=swap" rel="stylesheet">
    @routes
    @viteReactRefresh
    @vite(['resources/css/admin.css', 'resources/js/app.jsx'])
    @hook('admin.head')
    @include('partials.cms-packages', ['area' => 'admin'])
</head>
<body>
    @inertia
</body>
</html>
