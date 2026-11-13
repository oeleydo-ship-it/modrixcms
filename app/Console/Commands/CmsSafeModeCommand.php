<?php

namespace App\Console\Commands;

use App\Cms\Cms;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CmsSafeModeCommand extends Command
{
    protected $signature = 'cms:safe-mode {state : on or off}';

    protected $description = 'Safe mode loads no plugins and uses the default theme: the way back when a package breaks the site';

    public function handle(): int
    {
        $file = Cms::safeModeFile();
        File::ensureDirectoryExists(dirname($file));

        match ($this->argument('state')) {
            'on' => File::put($file, 'Safe mode turned on '.now()->toDateTimeString().PHP_EOL),
            'off' => File::delete($file),
            default => null,
        };

        if (! in_array($this->argument('state'), ['on', 'off'], true)) {
            $this->error('Use on or off.');

            return self::FAILURE;
        }

        $this->info('Safe mode is '.(Cms::safeMode() ? 'on' : 'off').'.');

        if ($this->argument('state') === 'off' && config('cms.safe_mode')) {
            $this->warn('CMS_SAFE_MODE=true is still set in .env, so safe mode stays on until you remove it.');
        }

        return self::SUCCESS;
    }
}
