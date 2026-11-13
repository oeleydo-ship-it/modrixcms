<?php

namespace App\Console\Commands;

use App\Cms\Extensions\ExtensionInstaller;
use App\Cms\Extensions\ExtensionRegistry;
use App\Cms\Extensions\InvalidPackage;
use App\Cms\Extensions\Package;
use App\Models\Extension;
use App\Support\Settings;
use Illuminate\Console\Command;

class CmsThemeCommand extends Command
{
    protected $signature = 'cms:theme
        {action : list or activate}
        {name? : vendor/package}
        {--trust : Confirm you trust the theme\'s templates (needed the first time)}';

    protected $description = 'List themes or activate one';

    public function handle(ExtensionRegistry $registry, ExtensionInstaller $installer, Settings $settings): int
    {
        if ($this->argument('action') === 'list') {
            $active = $settings->get('active_theme');

            $this->table(['Theme', 'Version', 'Status'], $registry->all(Extension::THEME)->map(fn (Package $t) => [
                $t->name, $t->version(), $t->problem ? 'broken' : ($t->name === $active ? 'active' : ''),
            ]));

            return self::SUCCESS;
        }

        if ($this->argument('action') !== 'activate') {
            $this->error('Use list or activate.');

            return self::FAILURE;
        }

        try {
            $installer->activateTheme((string) $this->argument('name'), null, (bool) $this->option('trust'));
        } catch (InvalidPackage $e) {
            $this->error($e->getMessage().($this->option('trust') ? '' : ' (Add --trust if it asks for it.)'));

            return self::FAILURE;
        }

        $this->info($this->argument('name').' is now the active theme.');

        return self::SUCCESS;
    }
}
