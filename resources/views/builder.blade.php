{{-- The visual page builder (full screen, its own React bundle). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>{{ $page->title }} · Builder · {{ $settings->get('site_name') }}</title>
    <link rel="icon" href="{{ $settings->fileUrl('favicon') ?? asset('favicon.svg') }}">
    @viteReactRefresh
    @vite(['resources/js/builder/main.tsx'])
</head>
<body class="h-full">
    <div id="builder" data-page-id="{{ $page->id }}"></div>
</body>
</html>
