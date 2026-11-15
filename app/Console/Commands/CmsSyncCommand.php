<?php

namespace App\Console\Commands;

use App\Cms\Extensions\ExtensionInstaller;
use Illuminate\Console\Command;

class CmsSyncCommand extends Command
{
    protected $signature = 'cms:sync';

    protected $description = 'Publish theme and plugin assets and rebuild the plugin boot list (run after every deploy)';

    public function handle(ExtensionInstaller $installer): int
    {
        $count = $installer->republishAll();

        $this->info("Published assets for {$count} package".($count === 1 ? '' : 's').' and rebuilt the plugin boot list.');

        return self::SUCCESS;
    }
}
