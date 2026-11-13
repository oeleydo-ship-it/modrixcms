<?php

namespace App\Console\Commands;

use App\Cms\Extensions\ExtensionInstaller;
use App\Cms\Extensions\ExtensionRegistry;
use App\Cms\Extensions\InvalidPackage;
use App\Cms\Extensions\Package;
use App\Models\Extension;
use Illuminate\Console\Command;

class CmsPluginCommand extends Command
{
    protected $signature = 'cms:plugin
        {action : list, enable, disable or uninstall}
        {name? : vendor/package}
        {--trust : Confirm you trust the plugin\'s code (needed the first time it is enabled)}
        {--delete-data : With uninstall: also drop the plugin\'s database tables}';

    protected $description = 'List, enable, disable or uninstall plugins';

    public function handle(ExtensionRegistry $registry, ExtensionInstaller $installer): int
    {
        $action = $this->argument('action');

        if ($action === 'list') {
            $this->table(['Plugin', 'Version', 'Status'], $registry->all(Extension::PLUGIN)->map(fn (Package $p) => [
                $p->name, $p->version(), $p->problem ? 'broken' : ($p->record?->error ? 'failed' : ($p->isEnabled() ? 'enabled' : 'disabled')),
            ]));

            return self::SUCCESS;
        }

        $name = (string) $this->argument('name');

        if ($name === '' || $registry->find(Extension::PLUGIN, $name) === null) {
            $this->error("Plugin \"{$name}\" is not installed. Run: php artisan cms:plugin list");

            return self::FAILURE;
        }

        try {
            match ($action) {
                'enable' => $installer->enablePlugin($name, null, (bool) $this->option('trust')),
                'disable' => $installer->disablePlugin($name),
                'uninstall' => $installer->uninstallPlugin($name, (bool) $this->option('delete-data')),
                default => throw new InvalidPackage("Unknown action \"{$action}\". Use list, enable, disable or uninstall."),
            };
        } catch (InvalidPackage $e) {
            $this->error($e->getMessage().($action === 'enable' && ! $this->option('trust') ? ' Add --trust.' : ''));

            return self::FAILURE;
        }

        $this->info("{$name}: {$action} done.");

        return self::SUCCESS;
    }
}
