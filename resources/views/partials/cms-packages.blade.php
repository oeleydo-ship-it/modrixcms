{{-- Compiled scripts of enabled plugins (and, on the public site, the active theme). The core
     imports them before the React app starts, so they can register their components. --}}
@php
    $cmsPackageScripts = [
        'plugins' => app(\App\Cms\PluginLoader::class)->scripts(),
        'theme' => $area === 'site' ? app(\App\Cms\Themes\ThemeManager::class)->scripts() : [],
    ];
@endphp
<script id="cms-packages" type="application/json">@json($cmsPackageScripts, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES)</script>
