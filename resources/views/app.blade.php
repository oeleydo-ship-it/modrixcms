{{-- Root HTML of the public site. The page itself is drawn by React (Inertia); this file only
     carries what has to be in the HTML: meta tags for search engines, theme CSS and plugin head output. --}}
@php
    $meta = $page['props']['meta'] ?? [];
    $siteName = $settings->get('site_name');
    $pageTitle = $meta['title'] ?? $siteName.' — '.$settings->get('tagline');
    $pageDescription = $meta['description'] ?? $settings->get('tagline');
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta property="og:title" content="{{ $meta['ogTitle'] ?? $pageTitle }}">
    <meta property="og:description" content="{{ $meta['ogDescription'] ?? $pageDescription }}">
    <meta name="twitter:card" content="{{ ! empty($meta['image']) ? 'summary_large_image' : 'summary' }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:type" content="{{ $meta['type'] ?? 'website' }}">
    @if (! empty($meta['image']))
        <meta property="og:image" content="{{ url($meta['image']) }}">
    @endif
    @if (! empty($meta['noindex']))
        <meta name="robots" content="noindex">
    @endif
    @if (! empty($meta['canonical']))
        <link rel="canonical" href="{{ $meta['canonical'] }}">
    @endif
    @if (! empty($meta['jsonLd']))
        <script type="application/ld+json">@json($meta['jsonLd'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    @endif
    <link rel="icon" href="{{ $settings->fileUrl('favicon') ?? asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:500,700,800|inter:400,500,600|jetbrains-mono:400,500&display=swap" rel="stylesheet">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @themeStyles
    @siteDesign(true)
    @hook('head')
    @include('partials.cms-packages', ['area' => 'site'])
</head>
<body>
    @hook('body.start')
    @inertia
    @hook('body.end')
</body>
</html>
