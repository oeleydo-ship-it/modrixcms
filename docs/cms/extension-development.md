# Building themes and plugins

Modrix CMS sites are drawn with **React + Inertia**. Themes change how the public site looks; plugins add features. Both are zip files a site owner uploads in the admin (**Appearance** / **Plugins**), or folders copied to `extensions/themes/{vendor}/{name}` and `extensions/plugins/{vendor}/{name}`.

Packages ship **compiled JavaScript**. The CMS loads it when a page opens, so an upload works immediately: the site never runs `npm` or Composer.

## Start from a scaffold

```bash
php artisan cms:make plugin acme/gallery --author="Acme Ltd"
php artisan cms:make theme acme/harbor --author="Acme Ltd"
```

Both work without changes. Enable or activate them:

```bash
php artisan cms:plugin enable acme/gallery --trust
php artisan cms:theme activate acme/harbor --trust
```

To share one, zip the **contents** of its folder: `plugin.json` / `theme.json` must be at the root of the zip.

## The React runtime: `window.ModrixCMS`

Your scripts are ES modules. Never bundle React or Inertia: take them from `window.ModrixCMS` so there is one copy on the page.

| Key | What |
| --- | --- |
| `React`, `jsx` | React and its JSX runtime (`react/jsx-runtime`) |
| `Inertia` | `{ Head, Link, router, useForm, usePage }` |
| `ui` | Core components: `AdminLayout`, `Field`, `Check`, `FileField`, `Pager`, `Badge`, `DangerButton`, `confirmVisit`, `SiteLayout`, `Nav`, `Footer`, `Brand`, `BlockList`, `PostCard`, `Html`, `Icon`, `ErrorBoundary`, `coreBlocks` |
| `registerTheme(name, { layout, pages, blocks })` | Called by a theme's script |
| `registerPlugin(name, { pages, blocks })` | Called by a plugin's script |

Within 1.x these are only added to, never removed.

The samples (`extensions/themes/modrix/minimal/public/theme.js`, `extensions/plugins/modrix/contact-form/public/contact-form.js`) are written with `React.createElement`, so they need no build. For JSX, compile with Vite in library mode and map the externals to the runtime:

```js
// vite.config.js of your package (not of the site)
export default {
  build: {
    lib: { entry: 'src/index.jsx', formats: ['es'], fileName: () => 'plugin.js' },
    outDir: 'public',
    rollupOptions: { external: ['react', 'react/jsx-runtime', '@inertiajs/react'] },
  },
  plugins: [{
    // Point the externals at window.ModrixCMS.
    name: 'modrix-globals',
    renderChunk: (code) => code
      .replace(/import \{([^}]+)\} from ['"]react\/jsx-runtime['"];?/g, 'const {$1} = window.ModrixCMS.jsx;')
      .replace(/import (\w+)(, \{([^}]+)\})? from ['"]react['"];?/g, (_, def, __, named) => `const ${def} = window.ModrixCMS.React;${named ? ` const {${named}} = ${def};` : ''}`)
      .replace(/import \{([^}]+)\} from ['"]@inertiajs\/react['"];?/g, 'const {$1} = window.ModrixCMS.Inertia;'),
  }],
};
```

If your script throws while loading, the CMS logs it in the browser console and carries on without it. A component that crashes while drawing is skipped (a theme layout falls back to the core layout).

## Themes

```
theme.json
public/theme.js          React components (optional)
public/theme.css         loaded after the core styles (optional)
public/screenshot.png    shown in Appearance
```

```json
{
  "name": "acme/harbor",
  "type": "theme",
  "display_name": "Harbor",
  "description": "…",
  "version": "1.0.0",
  "cms_version": "^1.0",
  "author": "Acme Ltd",
  "license": "MIT",
  "screenshot": "public/screenshot.png",
  "styles": ["public/theme.css"],
  "scripts": ["public/theme.js"],
  "settings": [
    { "name": "brand", "label": "Brand colour", "type": "color", "default": "#0f766e", "css_var": "--brand" },
    { "name": "corners", "label": "Corners", "type": "select", "default": "round",
      "options": { "round": "Rounded", "square": "Square" },
      "css_var": "--radius", "css_values": { "round": "20px", "square": "2px" } },
    { "name": "show_cta", "label": "Show header button", "type": "toggle", "default": true }
  ]
}
```

**What a theme can replace** (anything else falls back to the core):

```js
const { React, ui, registerTheme } = window.ModrixCMS;

registerTheme('acme/harbor', {
  layout: Layout,                       // wraps every public page: ({ children }) => …
  pages: { 'Site/Page': Page, 'Site/BlogIndex': BlogIndex, 'Site/BlogPost': BlogPost },
  blocks: { hero: Hero, cta: Cta },     // any core or plugin block type
});
```

**Settings** appear in **Appearance → Customize**. Each becomes a CSS custom property when it has `css_var`, and is available to components as `usePage().props.theme.settings`.

**Data on every public page** (`usePage().props`): `site` (name, tagline, logo, favicon), `links` (home, blog, github, marketplace), `menu` (pages shown in the menu), `footer` (text, email, socials), `theme` (name, settings, previewing), `meta`, plus the page's own props (`blocks` for `Site/Page`; `posts`, `categories` for `Site/BlogIndex`; `post`, `related` for `Site/BlogPost`).

**Blocks** receive `{ block, data, anchor }`. The server has already prepared `data`: links are resolved, and next to each field you get `{field}_html` for Markdown and textarea fields (safe HTML: print it with `ui.Html`), `{field}_svg` for icons (`ui.Icon`) and `{field}_url` for images. `latest_posts` also gets `posts`.

Themes may not contain PHP. Because their scripts run in your visitors' browsers, a theme with `scripts` must be trusted before it can be previewed or activated.

## Plugins

```
plugin.json
src/                       PHP (PSR-4)
routes/web.php             public routes (optional)
routes/admin.php           admin routes (optional)
database/migrations/       run on enable, reset on uninstall with "delete data"
public/plugin.js           React components (optional)
public/*.css, images…      copied to /extensions/plugins/{vendor}/{name}/
resources/views/           Blade, e.g. for emails (namespace "vendor-name::")
```

```json
{
  "name": "acme/gallery",
  "type": "plugin",
  "display_name": "Gallery",
  "version": "1.0.0",
  "cms_version": "^1.0",
  "autoload": { "psr-4": { "Acme\\Gallery\\": "src/" } },
  "provider": "Acme\\Gallery\\GalleryServiceProvider",
  "routes": { "web": "routes/web.php", "admin": "routes/admin.php" },
  "scripts": ["public/plugin.js"],
  "admin_navigation": [{ "title": "Photos", "route": "photos.index" }],
  "settings": [{ "name": "columns", "label": "Columns", "type": "number", "default": 3, "min": 1, "max": 6 }]
}
```

### Server side

```php
namespace Acme\Gallery;

use App\Cms\Cms;
use App\Cms\PluginServiceProvider;

class GalleryServiceProvider extends PluginServiceProvider
{
    protected function bootPlugin(): void
    {
        // A page-builder block: fields and defaults. The React component is registered in plugin.js.
        $this->block('grid', [
            'label' => 'Photo grid',
            'description' => 'Photos from the gallery.',
            'fields' => [['name' => 'title', 'label' => 'Heading', 'type' => 'text']],
            'defaults' => ['title' => 'Our work'],
        ]);

        // Add server data to the block's props.
        $this->filter('block.data', function (array $data, string $type) {
            if ($type === 'acme/gallery:grid') {
                $data['photos'] = Photo::latest()->take(12)->get(['id', 'url']);
                $data['columns'] = Cms::setting('acme/gallery', 'columns');
            }

            return $data;
        });

        // HTML in the <head> of public pages (escape anything dynamic).
        $this->hook('head', fn () => '<link rel="stylesheet" href="'.e(Cms::asset($this->name(), 'gallery.css')).'">');
    }
}
```

- Put your code in `registerPlugin()` / `bootPlugin()`. If either throws, the plugin is disabled and the error shown in **Plugins**.
- Field types for blocks: `text`, `textarea`, `markdown`, `url`, `number`, `select`, `toggle`, `list`, `repeater`, `icon`, `image`.
- Setting types: `text`, `textarea`, `email`, `url`, `number`, `toggle`, `select`, `color`. Read them with `Cms::setting('vendor/name', 'key')`.
- Output points: `head`, `body.start`, `body.end`, `admin.head`. Filters: `page.blocks`, `menu.pages`, `block.data`.
- Route names are prefixed `plugin.{vendor}.{name}.`. Admin routes live under `/admin/p/{vendor}/{name}` and require a signed-in admin.

### Admin pages

Render a page your script registers:

```php
return Inertia::render('plugin:acme/gallery/Photos', ['photos' => $photos]);
```

```js
const { React, ui, registerPlugin } = window.ModrixCMS;
const h = React.createElement;

function Photos({ photos }) {
  return h(ui.AdminLayout, { title: 'Photos' }, h('div', { className: 'panel' }, /* … */));
}

registerPlugin('acme/gallery', {
  pages: { Photos },
  blocks: { 'acme/gallery:grid': Grid },
});
```

In the admin, Ziggy's `route('name', params)` is available for URLs.

## Rules every package must pass

Checked on upload, and again before a package copied by hand is enabled:

- zip ≤ 50 MB, ≤ 200 MB unpacked, ≤ 5,000 files; no absolute paths, `..`, backslashes or symlinks;
- the manifest at the zip root, valid, with a `vendor/package` name, a semantic `version` and a `cms_version` constraint (`*`, `1.2.3`, `^1.0`, `~1.2`, `>=1.0 <2.0`);
- never: `.phar`, executables, shell scripts, `.htaccess`, `.user.ini`, `.env`;
- no PHP anywhere in `public/` (it is copied into the web root); no PHP at all in themes;
- PSR-4 namespaces may not start with `App\`, `Illuminate\`, `Database\`, `Tests\`, `Laravel\`, `Symfony\` or `Composer\`.

The code scan lists risky patterns (eval, shell commands, obfuscation, dynamic includes, `unserialize`, raw SQL, outbound HTTP, environment access) with file and line. It informs the owner; it does not prove a package is safe.

## Install, update, remove

- An upload never overwrites: the same or a lower version is refused. A higher version is an update; the old version is kept in `storage/app/cms/backups`, and enabled plugins run their new migrations (a failed migration restores the old version).
- Enabling a plugin, or previewing/activating a theme with scripts, needs a ticked **I trust this code** the first time, and the admin's password from the last 15 minutes.
- **Safe mode** (`php artisan cms:safe-mode on`, or `CMS_SAFE_MODE=true`) loads no plugins and uses the default theme: the way back when a package breaks the site.
- `CMS_ALLOW_UPLOADS=false` turns off zip uploads.
