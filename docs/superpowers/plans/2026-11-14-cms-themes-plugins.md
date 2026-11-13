# Modrix CMS — themes and plugins

Status: approved to build (2026-11-14). Written from the conversation that asked for it.

## Intent

Turn this Laravel site into a **website CMS that other people download and install** on their own hosting — like Modrix Commerce, but for websites instead of stores. Site owners change the look with **installable themes** and add features with **installable plugins**. Both arrive as **zip uploads** from the admin, so anything built outside (by a developer, or sold elsewhere) can be installed.

## Goals

- A theme system: themes restyle *and* restructure the public site (Blade templates + CSS + design settings), with a fallback to the core templates for anything a theme leaves out.
- A plugin system with a stable, documented API: plugins add page-builder blocks, admin screens, public routes, database tables, settings, and output at fixed points in the page.
- Upload, review, enable/activate, update, disable, uninstall — all from the admin, safely.
- A way back when a package breaks the site (safe mode, automatic disable on crash, CLI commands).
- A developer guide and scaffold commands so third parties can build packages.

## Non-goals (this round)

- **No marketplace connection.** Not to Modrix Cloud (a separate project) and not to any other catalog. Packages are installed by zip upload or by placing them on disk. A marketplace client can be added later behind the same install pipeline.
- No install-from-URL (avoids server-side fetching of arbitrary URLs).
- No licensing, paid packages or signed releases.
- No rebranding of the current Modrix promo content. It stays as the default home page content.
- No multi-site, no per-user roles beyond the existing `is_admin`.

## Anti-goals

- An upload feature that lets a non-admin, or an admin without re-entering their password, put code on the server.
- A broken plugin or theme that locks the owner out of the admin with no way back.
- A theme able to replace or break the admin.

## Constraints

- Laravel 13, PHP 8.3+, **React + Inertia** for the public site and the admin (user's decision, 2026-11-14), Vite for the core bundle only. Uploaded packages must work **without** `npm run build` or Composer (shared hosting).
- SQLite locally, MySQL in production.
- Existing behaviour (pages, block builder, blog, settings) keeps working.

## Approaches considered

| Option | Trade-off | Outcome |
| --- | --- | --- |
| Blade themes (templates + CSS + settings) | Works on upload with no build step; but Blade compiles to PHP. | First built, then **replaced**: the user asked for React + Inertia for the site and the dashboard. |
| **React + Inertia, packages ship prebuilt bundles** | The core exposes React, Inertia and its UI kit on `window.ModrixCMS`; a theme or plugin zip contains compiled JS that registers components at runtime. Upload works immediately, no Node on the server. Authors build before zipping. | **Chosen by the user** |
| React + Inertia, rebuild after install | Packages ship JSX source and the site runs `npm run build` after each install — every server needs Node. | Rejected by the user |
| Data-only themes (colours/fonts/variants) | Safest; but themes could never restructure a page, which caps what themes can offer. | Rejected |

### React runtime contract (v1)

- `window.ModrixCMS` = `{ version, React, jsx, Inertia: { Link, Head, router, useForm, usePage }, ui: {…core components…}, registerTheme(name, {layout?, pages?, blocks?}), registerPlugin(name, {pages?, blocks?}) }`.
- Package JS files are ES modules listed in the manifest (`"scripts": ["public/theme.js"]`) and imported by the core before the Inertia app starts, in this order: enabled plugins, then the active theme.
- Page names: core pages `Site/Page`, `Site/BlogIndex`, `Site/BlogPost`; plugin admin pages `plugin:vendor/name/PageName` (rendered with `Inertia::render('plugin:…')`). A theme may replace `layout`, any `Site/*` page and any block; core components are the fallback.
- Server-side Blade remains only for the root HTML document (meta tags, theme CSS variables, `@hook('head')` output) — so titles and descriptions are in the HTML for search engines even though page content renders in the browser (no SSR, which would need Node on the server).
- Theme templates are now JS, not Blade: themes may contain no PHP at all. They still need trust before preview/activation because their JS runs in visitors' and admins' browsers.
| Marketplace: second product in Modrix Cloud / separate marketplace / CMS first | — | User chose **CMS side now**, then said Modrix Cloud is **not** to be connected: this is a separate project. |

## Security model

Plugins are **code that runs on the server**; themes and plugins both ship **JavaScript that runs in visitors' and admins' browsers**. The CMS does not sandbox them; it controls *who* can install them and *what* the owner sees before trusting them.

1. Only signed-in admins reach the extension screens. Upload, enable, activate, update and delete also require **password confirmation within the last 15 minutes** (`cms.confirmed` middleware).
2. Uploads can be switched off: `CMS_ALLOW_UPLOADS=false`.
3. Every zip is inspected before anything lands in the code folders:
   - ≤ 50 MB zip, ≤ 200 MB extracted, ≤ 5,000 entries;
   - no absolute paths, `..`, backslashes, drive letters, null bytes, or symlinks;
   - manifest (`plugin.json` / `theme.json`) at the zip root, valid, with the expected type;
   - blocked files anywhere: `.phar`, `.exe`, `.dll`, `.so`, `.sh`, `.bat`, `.cmd`, `.htaccess`, `.user.ini`, `.env`;
   - **no PHP of any kind inside `public/`** (that folder is copied into the web root);
   - themes: no PHP at all (their components are compiled JS in `public/`).
4. A **code scan** lists risky patterns with file and line (eval, shell execution, obfuscation, dynamic include, `unserialize`, raw SQL, outbound HTTP, `env()` access). It informs; it does not prove safety.
5. Nothing runs until an admin ticks **"I trust this code"** on enable/activate. Who trusted it and when is recorded.
6. An upload never overwrites a package silently: same or lower version is refused; a higher version is an update, and the old version is kept as a backup.
7. **Recovery:** `CMS_SAFE_MODE=true` or `php artisan cms:safe-mode on` loads no plugins and uses the default theme. A plugin whose service provider throws is disabled automatically and the error is shown in the admin.

## Package format

```
extensions/
  plugins/{vendor}/{name}/plugin.json
  themes/{vendor}/{name}/theme.json
```

`extensions/` is a new top-level folder (needed so packages live outside `app/` and can be added/removed independently).

### `plugin.json`

```json
{
  "name": "acme/contact-form",
  "type": "plugin",
  "display_name": "Contact form",
  "description": "A contact form block and an inbox.",
  "version": "1.0.0",
  "cms_version": "^1.0",
  "author": "Acme Ltd",
  "license": "MIT",
  "autoload": { "psr-4": { "Acme\\ContactForm\\": "src/" } },
  "provider": "Acme\\ContactForm\\ContactFormServiceProvider",
  "routes": { "web": "routes/web.php", "admin": "routes/admin.php" },
  "scripts": ["public/contact-form.js"],
  "admin_navigation": [{ "title": "Messages", "route": "messages.index" }],
  "settings": [{ "name": "recipient", "label": "Send to", "type": "email", "default": "" }]
}
```

### `theme.json`

```json
{
  "name": "acme/harbor",
  "type": "theme",
  "display_name": "Harbor",
  "version": "1.0.0",
  "cms_version": "^1.0",
  "author": "Acme Ltd",
  "screenshot": "public/screenshot.png",
  "styles": ["public/theme.css"],
  "scripts": ["public/theme.js"],
  "settings": [{ "name": "brand", "label": "Brand colour", "type": "color", "default": "#5b3df5", "css_var": "--brand" }]
}
```

Rules: `name` is `vendor/package` in lowercase letters, digits and dashes; `version` is semver; `cms_version` supports `*`, exact, `^`, `~`, `>=`, `>`, `<=`, `<`. PSR-4 namespaces may not start with `App\`, `Illuminate\`, `Database\` or `Tests\`, and the provider must live in the declared namespace and extend `App\Cms\PluginServiceProvider`.

## Plugin API

| Extension point | How |
| --- | --- |
| Boot code | Provider extends `App\Cms\PluginServiceProvider`, implements `registerPlugin()` / `bootPlugin()`. Exceptions there are caught; the plugin is disabled and the error recorded. |
| Page-builder blocks | PHP: `$this->block('form', [...definition])` declares type `acme/contact-form:form` (fields, defaults, sanitizer). JS: `registerPlugin('acme/contact-form', { blocks: { 'acme/contact-form:form': Component } })` draws it. Themes can override it. |
| React pages | `Inertia::render('plugin:acme/contact-form/Messages')` + `registerPlugin(…, { pages: { Messages } })`, using `ModrixCMS.ui.AdminLayout`. |
| Blade views | `resources/views` loaded under namespace `acme-contact-form::` (emails and the like). |
| Public routes | `routes/web.php`, `web` middleware, names prefixed `plugin.acme.contact-form.`; registered before the page catch-all. |
| Admin routes | `routes/admin.php`, prefix `/admin/p/acme/contact-form`, `web`+`auth`+`admin` middleware, names prefixed `plugin.acme.contact-form.`. |
| Admin menu | `admin_navigation` entries, shown under "Plugins". |
| Database | `database/migrations` run on enable/update; reset on uninstall when the owner chooses "delete data". |
| Settings | `settings` schema → auto-generated settings screen; read with `Cms::setting('acme/contact-form', 'recipient')`. |
| Output hooks | `Hooks::add('head', fn () => '<meta …>')`; core layouts render `head`, `body.start`, `body.end`, `admin.head`. |
| Filters | `Hooks::filter('page.blocks', fn ($blocks, $page) => …)`; core applies `page.blocks`, `menu.pages` and `block.data` (add server data to a block's props). |
| Public assets | `public/` copied to `public/extensions/plugins/acme/contact-form/`; URL via `Cms::asset('acme/contact-form', 'app.css')`. |

Within 1.x, extension points are only added, never removed.

## Themes

- Active theme stored in the `active_theme` setting; default `modrix/default` (ships with the CMS, contains only a manifest + settings — the core React components are its components).
- A theme's scripts register React components with `registerTheme(name, { layout, pages, blocks })`; anything not registered falls back to the core. A component that crashes falls back too (error boundary). Themes never touch the admin.
- Theme settings are edited in **Appearance → Customize**, emitted as CSS custom properties (`css_var`, with optional `css_values` for selects) and shared with components as `theme.settings`.
- Changing which package scripts are on the page (enable, activate, preview) changes the Inertia asset version, so open pages reload once and pick up the new scripts.
- **Preview** a theme before activating it (admin-only, per session, with an exit bar).
- The default theme can't be deleted; the active theme can't be deleted.

## Admin screens

- **Appearance** (`/admin/themes`): cards with screenshot, version, author, active badge; Upload theme; Preview; Activate (trust tick); Customize; Delete.
- **Plugins** (`/admin/plugins`): list with status (enabled / disabled / failed), version, scan findings; Upload plugin; Enable (trust tick); Disable; Settings; Uninstall (keep or delete data).
- **Confirm password** screen used by the protected actions.
- Safe-mode banner across the admin when it's on.

## CLI

- `cms:plugin list|enable|disable|uninstall {name} [--trust] [--delete-data]`
- `cms:theme list|activate {name} [--trust]`
- `cms:safe-mode on|off`
- `cms:make plugin|theme vendor/name` — scaffolds (PHP + `public/*.js`) that validate and enable without changes.

## Data

- New table `extensions`: `type`, `name` (unique per type), `version`, `enabled`, `trusted_at`, `trusted_by`, `settings` (json), `scan` (json), `error`, timestamps.
- A boot cache `bootstrap/cache/cms-plugins.php` lists enabled plugins so loading needs no database query; rebuilt on every change.

## Build order

1. Config `config/cms.php`, `extensions` migration + model, `Manifest`, `VersionConstraint`.
2. `PackageInspector` (zip checks + extraction) and `CodeScanner`.
3. `ExtensionRegistry` (discover packages on disk + DB state), `PluginLoader` (boot cache, autoload, providers, routes, views, crash isolation), safe mode.
4. `Hooks`, `BlockRegistry`; wire into `PageBuilder`, block rendering and layouts.
5. `ThemeManager` + `ApplyTheme` middleware (view paths, preview, settings → CSS vars, assets).
6. `ExtensionInstaller` (upload, update with backup, enable/activate with trust, migrations, asset publishing, disable, uninstall).
7. Admin controllers + views (Appearance, Plugins, settings/customize, confirm password), nav entries.
8. Sample plugin `modrix/contact-form` (block, public POST route, migration, admin inbox, settings) and sample theme `modrix/minimal` (layout/nav override, CSS, settings).
9. Artisan commands, developer guide `docs/cms/extension-development.md`, README section.
10. React + Inertia rewrite (user's request mid-build): root templates, `window.ModrixCMS` runtime, core site and admin pages, page builder in React, props presenters, samples and scaffolds as JS bundles.
11. Tests: manifest + version rules, zip rejection cases, lifecycle (upload → enable → disable → uninstall, update), crash auto-disable, safe mode, theme override + fallback, admin access + password confirmation, sample plugin end to end.

## Known limitations (accepted for this round)

- Plugins and themes are trusted code; the CMS doesn't sandbox them. The scan and trust step are the guard rails.
- Errors inside a plugin's routes, controllers or shared props at request time aren't auto-isolated — safe mode is the way back.
- No server-side rendering (it would need Node on the server): page content is drawn in the browser. Titles, descriptions and Open Graph tags are still in the HTML for search engines and link previews.
- "I trust this code" for themes on the Appearance cards is a confirm dialog (it needs JavaScript, like the rest of the admin).
- If a plugin is disabled, its blocks disappear from pages; saving such a page in the editor drops those blocks.
- One backup of the previous version is kept on update; restoring it is manual (copy the folder back).
