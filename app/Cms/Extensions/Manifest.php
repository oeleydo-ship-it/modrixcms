<?php

namespace App\Cms\Extensions;

use App\Models\Extension;

/**
 * A validated plugin.json or theme.json, plus where the package lives on disk.
 */
final class Manifest
{
    public const NAME = '/^[a-z0-9](?:[a-z0-9-]{0,48}[a-z0-9])?\/[a-z0-9](?:[a-z0-9-]{0,48}[a-z0-9])?$/';

    /** Namespaces a plugin may not claim. */
    private const RESERVED_NAMESPACES = ['App\\', 'Illuminate\\', 'Database\\', 'Tests\\', 'Laravel\\', 'Symfony\\', 'Composer\\'];

    /** @param array<string, mixed> $data */
    private function __construct(public readonly array $data, public readonly string $path) {}

    /**
     * Reads and validates {path}/plugin.json or {path}/theme.json.
     *
     * @throws InvalidPackage
     */
    public static function load(string $path, string $type): self
    {
        $file = $path.DIRECTORY_SEPARATOR.self::fileName($type);

        if (! is_file($file)) {
            throw new InvalidPackage('The package has no '.self::fileName($type).' at its root.');
        }

        $json = file_get_contents($file);
        $data = is_string($json) && strlen($json) <= 256 * 1024 ? json_decode($json, true) : null;

        if (! is_array($data)) {
            throw new InvalidPackage(self::fileName($type).' is not valid JSON.');
        }

        self::validate($data, $type);

        return new self($data, rtrim($path, '\\/'));
    }

    public static function fileName(string $type): string
    {
        return $type === Extension::THEME ? 'theme.json' : 'plugin.json';
    }

    public function name(): string
    {
        return $this->data['name'];
    }

    public function type(): string
    {
        return $this->data['type'];
    }

    public function version(): string
    {
        return $this->data['version'];
    }

    public function displayName(): string
    {
        return $this->data['display_name'] ?? $this->name();
    }

    public function description(): string
    {
        return $this->data['description'] ?? '';
    }

    public function author(): string
    {
        return $this->data['author'] ?? '';
    }

    public function cmsVersion(): string
    {
        return $this->data['cms_version'];
    }

    public function isCompatible(): bool
    {
        return VersionConstraint::satisfies(config('cms.version'), $this->cmsVersion());
    }

    /** "acme/contact-form" → "acme-contact-form", used for view namespaces. */
    public function slug(): string
    {
        return str_replace('/', '-', $this->name());
    }

    /** @return array<int, array<string, mixed>> */
    public function settings(): array
    {
        return $this->data['settings'] ?? [];
    }

    /** @return array<string, string> namespace prefix → absolute directory */
    public function autoload(): array
    {
        $map = [];

        foreach ($this->data['autoload']['psr-4'] ?? [] as $prefix => $dir) {
            $map[$prefix] = $this->path.DIRECTORY_SEPARATOR.trim($dir, '/\\');
        }

        return $map;
    }

    public function provider(): ?string
    {
        return $this->data['provider'] ?? null;
    }

    public function file(string $relative): string
    {
        return $this->path.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    /** @param array<string, mixed> $data */
    private static function validate(array $data, string $type): void
    {
        $fail = fn (string $message) => throw new InvalidPackage(self::fileName($type).': '.$message);

        if (($data['type'] ?? null) !== $type) {
            $fail("\"type\" must be \"{$type}\".");
        }

        if (! is_string($data['name'] ?? null) || ! preg_match(self::NAME, $data['name'])) {
            $fail('"name" must look like vendor/package (lowercase letters, digits and dashes).');
        }

        if (! is_string($data['version'] ?? null) || ! VersionConstraint::isVersion($data['version'])) {
            $fail('"version" must be a semantic version such as 1.0.0.');
        }

        if (! is_string($data['cms_version'] ?? null) || ! VersionConstraint::isConstraint($data['cms_version'])) {
            $fail('"cms_version" must be a version constraint such as ^1.0.');
        }

        foreach (['display_name' => 120, 'description' => 1000, 'author' => 120, 'license' => 60] as $key => $max) {
            if (isset($data[$key]) && (! is_string($data[$key]) || mb_strlen($data[$key]) > $max)) {
                $fail("\"{$key}\" must be text of at most {$max} characters.");
            }
        }

        self::validateSettings($data['settings'] ?? [], $type, $fail);
        self::validateAssets($data, $fail);

        $type === Extension::PLUGIN
            ? self::validatePlugin($data, $fail)
            : self::validateTheme($data, $fail);
    }

    private static function validatePlugin(array $data, callable $fail): void
    {
        $psr4 = $data['autoload']['psr-4'] ?? [];

        if (! is_array($psr4) || count($psr4) > 5) {
            $fail('"autoload.psr-4" must map up to 5 namespaces to folders.');
        }

        foreach ($psr4 as $prefix => $dir) {
            if (! is_string($prefix) || ! preg_match('/^([A-Z][A-Za-z0-9_]*\\\\)+$/', $prefix)) {
                $fail("autoload namespace \"{$prefix}\" must end with \\\\ and use StudlyCase segments.");
            }

            foreach (self::RESERVED_NAMESPACES as $reserved) {
                if (str_starts_with($prefix, $reserved)) {
                    $fail("autoload namespace \"{$prefix}\" is reserved.");
                }
            }

            if (! is_string($dir) || ! self::isSafeRelativePath($dir)) {
                $fail("autoload folder for \"{$prefix}\" must be a relative path inside the package.");
            }
        }

        $provider = $data['provider'] ?? null;

        if ($provider !== null) {
            $inNamespace = is_string($provider) && collect(array_keys($psr4))->contains(fn ($p) => str_starts_with($provider, $p));

            if (! $inNamespace) {
                $fail('"provider" must be a class inside one of the autoload namespaces.');
            }
        }

        foreach (['web', 'admin'] as $kind) {
            $file = $data['routes'][$kind] ?? null;

            if ($file !== null && (! is_string($file) || ! self::isSafeRelativePath($file) || ! str_ends_with($file, '.php'))) {
                $fail("\"routes.{$kind}\" must be a .php file inside the package.");
            }
        }

        $nav = $data['admin_navigation'] ?? [];

        if (! is_array($nav) || count($nav) > 10) {
            $fail('"admin_navigation" must be a list of up to 10 entries.');
        }

        foreach ($nav as $item) {
            if (! is_string($item['title'] ?? null) || mb_strlen($item['title']) > 60
                || ! is_string($item['route'] ?? null) || ! preg_match('/^[a-z0-9_.-]{1,80}$/', $item['route'])) {
                $fail('each "admin_navigation" entry needs a "title" and a "route" name (relative to the plugin).');
            }
        }
    }

    private static function validateTheme(array $data, callable $fail): void
    {
        // Default design settings (see App\Cms\Themes\DesignTokens); unknown or invalid values are ignored.
        if (isset($data['design']) && (! is_array($data['design']) || array_filter($data['design'], fn ($value) => ! is_scalar($value)) !== [])) {
            $fail('"design" must be an object of setting names and values, e.g. {"primary": "#4f46e5"}.');
        }

        foreach (['screenshot'] as $key) {
            if (isset($data[$key]) && (! is_string($data[$key]) || ! self::isSafeRelativePath($data[$key]) || ! str_starts_with($data[$key], 'public/'))) {
                $fail("\"{$key}\" must be a file inside public/.");
            }
        }

        if (isset($data['demo'])) {
            self::validateDemo($data['demo'], $fail);
        }
    }

    /**
     * Demo content (Appearance → Import demo): visual-builder documents stored as .json files
     * inside demo/, for pages and for the theme's own site header and footer.
     */
    private static function validateDemo(mixed $demo, callable $fail): void
    {
        $isDemoFile = fn (mixed $file): bool => is_string($file) && self::isSafeRelativePath($file)
            && str_starts_with($file, 'demo/') && str_ends_with($file, '.json');

        if (! is_array($demo)) {
            $fail('"demo" must be an object with "pages" and optional "header" and "footer" files.');

            return;
        }

        foreach (['header', 'footer'] as $key) {
            if (isset($demo[$key]) && ! $isDemoFile($demo[$key])) {
                $fail("\"demo.{$key}\" must be a .json file inside demo/.");
            }
        }

        $pages = $demo['pages'] ?? [];

        if (! is_array($pages) || ! array_is_list($pages) || count($pages) > 30) {
            $fail('"demo.pages" must be a list of up to 30 pages.');

            return;
        }

        foreach ($pages as $index => $page) {
            if (! is_array($page)
                || ! is_string($page['title'] ?? null) || mb_strlen($page['title']) > 120
                || ! is_string($page['slug'] ?? null) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $page['slug']) || strlen($page['slug']) > 80
                || ! $isDemoFile($page['file'] ?? null)) {
                $fail("\"demo.pages.{$index}\" needs a \"title\", a \"slug\" (lowercase letters, digits and dashes) and a .json \"file\" inside demo/.");
            }

            foreach (['home', 'menu', 'site_header', 'site_footer'] as $flag) {
                if (isset($page[$flag]) && ! is_bool($page[$flag])) {
                    $fail("\"demo.pages.{$index}.{$flag}\" must be true or false.");
                }
            }
        }
    }

    /** "styles" (.css) and "scripts" (compiled ES modules, .js/.mjs) live in public/. */
    private static function validateAssets(array $data, callable $fail): void
    {
        foreach (['styles' => '/\.css$/', 'scripts' => '/\.m?js$/'] as $key => $extension) {
            $files = $data[$key] ?? [];

            if (! is_array($files) || count($files) > 10) {
                $fail("\"{$key}\" must be a list of up to 10 files.");
            }

            foreach ($files as $file) {
                if (! is_string($file) || ! self::isSafeRelativePath($file) || ! str_starts_with($file, 'public/') || ! preg_match($extension, $file)) {
                    $fail("every \"{$key}\" entry must be a ".($key === 'styles' ? '.css' : '.js').' file inside public/.');
                }
            }
        }
    }

    private static function validateSettings(mixed $settings, string $type, callable $fail): void
    {
        if (! is_array($settings) || count($settings) > 40) {
            $fail('"settings" must be a list of up to 40 fields.');
        }

        $types = ['text', 'textarea', 'email', 'url', 'number', 'toggle', 'select', 'color'];
        $names = [];

        foreach ($settings as $field) {
            $name = $field['name'] ?? null;

            if (! is_string($name) || ! preg_match('/^[a-z][a-z0-9_]{0,39}$/', $name) || isset($names[$name])) {
                $fail('every setting needs a unique "name" (lowercase letters, digits, underscores).');
            }

            $names[$name] = true;

            if (! in_array($field['type'] ?? null, $types, true)) {
                $fail("setting \"{$name}\" has an unknown type. Use one of: ".implode(', ', $types).'.');
            }

            if (! is_string($field['label'] ?? null)) {
                $fail("setting \"{$name}\" needs a \"label\".");
            }

            if ($field['type'] === 'select' && (! is_array($field['options'] ?? null) || $field['options'] === [])) {
                $fail("setting \"{$name}\" needs \"options\".");
            }

            if (isset($field['css_var'])) {
                if ($type !== Extension::THEME || ! is_string($field['css_var']) || ! preg_match('/^--[a-z0-9-]{1,40}$/', $field['css_var'])) {
                    $fail("setting \"{$name}\": \"css_var\" is for themes and must look like --brand.");
                }
            }
        }
    }

    public static function isSafeRelativePath(string $path): bool
    {
        return $path !== ''
            && strlen($path) <= 200
            && ! str_contains($path, '\\')
            && ! str_contains($path, "\0")
            && ! str_starts_with($path, '/')
            && ! preg_match('/^[A-Za-z]:/', $path)
            && ! in_array('..', explode('/', $path), true);
    }
}
