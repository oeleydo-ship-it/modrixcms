# Modrix Commerce — website

Laravel 13 website for Modrix Commerce: the promo home page, plus an admin to manage pages, a blog, general settings, SMTP and the logo. Separate from the Modrix Commerce core.

## Features

| Area | What you get |
| --- | --- |
| **Admin auth** | `/admin` sign-in for users with `is_admin`, rate-limited, remember me, change name/email/password |
| **Page builder** | Every page, including the home (landing) page, is a list of blocks: Hero, Page header, Text (Markdown), Image, Logo strip, Feature grid, Marketplace showcase, Steps + code, Comparison, Latest blog posts, Call to action. Add, drag/move, hide, duplicate, delete, edit fields and repeating items, upload images, preview unsaved changes |
| **Page management** | Pages with custom URL, SEO title/description, publish toggle, "use as home page", show in menu with order; "New landing page" starts from a copy of the landing page |
| **Blog management** | Posts with draft / published / scheduled, cover image, excerpt, categories, author, SEO description; public `/blog`, category filter, related posts, latest posts on the home page |
| **General settings** | Site name, tagline, contact email, footer text, GitHub & marketplace links, social links |
| **SMTP** | Host, port, TLS/SSL, username, password (encrypted at rest), sender; applied to Laravel's mailer; send a test email |
| **Logo & favicon** | Upload/remove logo (site header, footer, admin, login) and favicon |
| **Themes** | Admin → Appearance: upload a theme zip, preview it (only you see it), activate, customize its colours and options, delete. Themes are React components + CSS loaded at runtime |
| **Plugins** | Admin → Plugins: upload a plugin zip, review its code scan, enable (with trust + password), configure, disable, uninstall (optionally deleting its data). Plugins add blocks, admin screens, public routes, tables and settings |
| **Safety** | Zip checks, code scan, trust step, recent-password check, automatic disable of a crashing plugin, safe mode, `CMS_ALLOW_UPLOADS=false` |

The public site and the admin are **React + Inertia**. Build the front end with `npm run build` (only the core needs building; theme and plugin zips ship compiled JavaScript).

## Setup

```bash
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

### Database

SQLite works out of the box for local use. For MySQL, set in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=modrix_site
DB_USERNAME=...
DB_PASSWORD=...
```

Then:

```bash
php artisan migrate --seed
php artisan storage:link
```

### First admin

Either set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env` before `db:seed`, or (recommended for production):

```bash
php artisan site:admin you@example.com
```

It asks for a password (10+ characters). Running it again for the same email resets the password.

Sign in at `/admin`.

## Where things are

| Path | What |
| --- | --- |
| `routes/web.php` | Admin routes (`/admin/...`), blog, pages (`/{slug}`, last) |
| `app/Http/Controllers/Admin/*` | Auth, dashboard, pages, posts, categories, settings, account |
| `app/Support/Settings.php` | Settings store (cached), encrypted SMTP password, mail config |
| `app/Models` | `Page`, `Post`, `Category`, `Setting`, `User` (`is_admin`) |
| `app/PageBuilder/PageBuilder.php` | Block types, their fields and default content (the landing page), cleaning of saved blocks |
| `app/Cms` | Themes and plugins: manifests, zip checks, code scan, installer, plugin loader, hooks, theme manager, props for React (`Frontend/`) |
| `config/cms.php` | CMS version, extension paths, upload limits, safe mode |
| `extensions/themes`, `extensions/plugins` | Installed packages (ships with Modrix Default, Minimal and the Contact form plugin) |
| `resources/js/app.jsx`, `resources/js/cms` | React entry, the `window.ModrixCMS` runtime, core site and admin components, page builder |
| `resources/js/Pages` | Inertia pages: `Site/*` (public) and `Admin/*` |
| `resources/views/app.blade.php`, `admin.blade.php` | Root HTML (meta tags, theme CSS, package scripts) |
| `config/site.php` | Fallback GitHub and marketplace links |
| `docs/cms/extension-development.md` | Guide for building themes and plugins |

CLI: `php artisan cms:plugin list|enable|disable|uninstall`, `cms:theme list|activate`, `cms:safe-mode on|off`, `cms:make plugin|theme vendor/name`.

Content is written in Markdown. Raw HTML and `javascript:` links are stripped when it's rendered.

## Tests

```bash
php artisan test
```
