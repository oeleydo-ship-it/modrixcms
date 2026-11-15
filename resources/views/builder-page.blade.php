{{-- A page published from the visual builder. The design carries its own header and footer,
     so it isn't wrapped in the theme layout; plugin head/body output still applies. --}}
@php
    $siteName = $settings->get('site_name');
    $pageTitle = $page->meta_title ?: ($page->is_home ? $siteName.' — '.$settings->get('tagline') : $page->title.' · '.$siteName);
    $pageDescription = $page->meta_description ?: $settings->get('tagline');
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:type" content="website">
    <link rel="icon" href="{{ $settings->fileUrl('favicon') ?? asset('favicon.svg') }}">
    @viteReactRefresh
    @vite(['resources/js/builder-page.tsx'])
    @siteDesign
    <style>body{margin:0;background:var(--site-background);color:var(--site-body);font-family:var(--site-body-font)}</style>
    @hook('head')
</head>
<body>
    @hook('body.start')
    <div id="public-page"></div>
    <script id="published-document" type="application/json">@json($document, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES)</script>
    @if(!empty($runtime))
    <script id="published-runtime" type="application/json">@json($runtime, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES)</script>
    @endif
    @hook('body.end')
</body>
</html>
