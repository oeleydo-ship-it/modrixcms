<?php

namespace App\Console\Commands;

use App\Cms\Extensions\ExtensionRegistry;
use App\Cms\Extensions\Manifest;
use App\Models\Extension;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/** Scaffolds a plugin or theme that validates and works without changes. */
class CmsMakePackageCommand extends Command
{
    protected $signature = 'cms:make {type : plugin or theme} {name : vendor/package} {--author= : Your name or company}';

    protected $description = 'Create a new plugin or theme in extensions/ to start building from';

    public function handle(ExtensionRegistry $registry): int
    {
        $type = $this->argument('type');
        $name = $this->argument('name');

        if (! in_array($type, [Extension::PLUGIN, Extension::THEME], true)) {
            $this->error('Type must be plugin or theme.');

            return self::FAILURE;
        }

        if (! preg_match(Manifest::NAME, $name)) {
            $this->error('Name must look like vendor/package (lowercase letters, digits and dashes).');

            return self::FAILURE;
        }

        $path = $registry->pathFor($type, $name);

        if (is_dir($path)) {
            $this->error("{$path} already exists.");

            return self::FAILURE;
        }

        [$vendor, $package] = explode('/', $name);
        $author = $this->option('author') ?: Str::headline($vendor);
        $display = Str::headline($package);

        $files = $type === Extension::PLUGIN
            ? $this->pluginFiles($name, $vendor, $package, $display, $author)
            : $this->themeFiles($name, $display, $author);

        foreach ($files as $relative => $contents) {
            $file = $path.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            File::ensureDirectoryExists(dirname($file));
            File::put($file, $contents);
        }

        $this->info("Created {$type} {$name} in {$path}");
        $this->line($type === Extension::PLUGIN
            ? "Enable it in Admin → Plugins, or: php artisan cms:plugin enable {$name} --trust"
            : "Preview and activate it in Admin → Appearance, or: php artisan cms:theme activate {$name} --trust");
        $this->line('To share it, zip the contents of that folder (the json file must be at the root of the zip).');

        return self::SUCCESS;
    }

    /** @return array<string, string> */
    private function pluginFiles(string $name, string $vendor, string $package, string $display, string $author): array
    {
        $namespace = Str::studly($vendor).'\\'.Str::studly($package);
        $provider = Str::studly($package).'ServiceProvider';
        $slug = str_replace('/', '-', $name);

        $manifest = [
            'name' => $name,
            'type' => 'plugin',
            'display_name' => $display,
            'description' => "Describe what {$display} does.",
            'version' => '0.1.0',
            'cms_version' => '^1.0',
            'author' => $author,
            'license' => 'MIT',
            'autoload' => ['psr-4' => [$namespace.'\\' => 'src/']],
            'provider' => $namespace.'\\'.$provider,
            'scripts' => ['public/plugin.js'],
            'settings' => [
                ['name' => 'greeting', 'label' => 'Greeting', 'type' => 'text', 'default' => 'Hello from '.$display],
            ],
        ];

        return [
            'plugin.json' => json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
            "src/{$provider}.php" => <<<PHP
                <?php

                namespace {$namespace};

                use App\Cms\Cms;
                use App\Cms\PluginServiceProvider;

                class {$provider} extends PluginServiceProvider
                {
                    protected function bootPlugin(): void
                    {
                        // A page-builder block: its fields and defaults. public/plugin.js draws it.
                        \$this->block('hello', [
                            'label' => '{$display}',
                            'description' => 'A sample block from the {$display} plugin.',
                            'fields' => [
                                ['name' => 'text', 'label' => 'Text', 'type' => 'textarea'],
                            ],
                            'defaults' => ['text' => 'Edit me in the page builder.'],
                        ]);

                        // Server data for the block: added to its props.
                        \$this->filter('block.data', function (array \$data, string \$type) {
                            if (\$type === '{$name}:hello') {
                                \$data['greeting'] = Cms::setting('{$name}', 'greeting');
                            }

                            return \$data;
                        });
                    }
                }

                PHP,
            'public/plugin.js' => <<<JS
                // React components of {$display}. React comes from window.ModrixCMS: don't bundle your own.
                const { React, registerPlugin } = window.ModrixCMS;
                const h = React.createElement;

                function Hello({ data, anchor }) {
                    return h('section', { className: 'section', id: anchor },
                        h('div', { className: 'wrap' },
                            h('h2', null, data.greeting),
                            h('p', null, data.text),
                        ),
                    );
                }

                registerPlugin('{$name}', {
                    blocks: { '{$name}:hello': Hello },
                });

                JS,
            'README.md' => "# {$display}\n\nA plugin for Modrix CMS. Server code in src/, React components in public/plugin.js (view namespace for Blade emails: `{$slug}::`).\n",
        ];
    }

    /** @return array<string, string> */
    private function themeFiles(string $name, string $display, string $author): array
    {
        $manifest = [
            'name' => $name,
            'type' => 'theme',
            'display_name' => $display,
            'description' => "Describe the look of {$display}.",
            'version' => '0.1.0',
            'cms_version' => '^1.0',
            'author' => $author,
            'license' => 'MIT',
            'styles' => ['public/theme.css'],
            'scripts' => ['public/theme.js'],
            'settings' => [
                ['name' => 'brand', 'label' => 'Brand colour', 'type' => 'color', 'default' => '#0f766e', 'css_var' => '--brand'],
                ['name' => 'background', 'label' => 'Page background', 'type' => 'color', 'default' => '#ffffff', 'css_var' => '--paper'],
            ],
        ];

        return [
            'theme.json' => json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
            'public/theme.css' => "/* Loaded after the core styles. Override what you need. */\n:root { --radius: 12px; }\n",
            'public/theme.js' => <<<JS
                // React components of the {$display} theme. Anything not registered here uses the core
                // component. React and the core UI come from window.ModrixCMS: don't bundle your own.
                const { React, ui, registerTheme } = window.ModrixCMS;
                const h = React.createElement;

                function Layout({ children }) {
                    return h(React.Fragment, null,
                        h(ui.Nav),
                        h('main', null, children),
                        h(ui.Footer),
                    );
                }

                registerTheme('{$name}', {
                    layout: Layout,
                    // pages: { 'Site/Page': MyPage, 'Site/BlogIndex': …, 'Site/BlogPost': … },
                    // blocks: { hero: MyHero, cta: MyCta },
                });

                JS,
            'README.md' => "# {$display}\n\nA theme for Modrix CMS. public/theme.js registers React components that replace the core ones; public/theme.css restyles the site.\n",
        ];
    }
}
