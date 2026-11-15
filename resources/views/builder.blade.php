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
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:500,600,700|dm-sans:400,500,600,700|inter:400,500,600,700|jetbrains-mono:400,500|plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">
    @viteReactRefresh
    @vite(['resources/js/builder/main.tsx'])
    <style id="admin-theme-vars">{!! $settings->adminCssVariables() !!}</style>
    {{-- The canvas shows the page in the theme's design settings (--site-* variables). --}}
    @siteDesign
</head>
<body class="h-full">
    <div id="builder" data-page-id="{{ $page->id }}"></div>
    {{-- The published site header and footer, shown locked around the page on the canvas. --}}
    <script id="site-chrome" type="application/json">@json(app(\App\Cms\Builder\SiteChrome::class)->forCanvas($page), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES)</script>
    <script id="site-design-colors" type="application/json">@json(\App\Cms\Themes\DesignTokens::swatches(app(\App\Cms\Themes\ThemeManager::class)->design()), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES)</script>
</body>
</html>
