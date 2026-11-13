<?php

namespace App\Cms\Extensions;

use App\Models\Extension;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Finds themes and plugins on disk (extensions/{plugins|themes}/{vendor}/{name}) and joins
 * them with their install state in the `extensions` table.
 */
class ExtensionRegistry
{
    public function root(string $type): string
    {
        return rtrim(config('cms.path'), '\\/').DIRECTORY_SEPARATOR.($type === Extension::THEME ? 'themes' : 'plugins');
    }

    public function pathFor(string $type, string $name): string
    {
        return $this->root($type).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $name);
    }

    public function publicPathFor(string $type, string $name): string
    {
        return rtrim(config('cms.public_path'), '\\/').DIRECTORY_SEPARATOR.($type === Extension::THEME ? 'themes' : 'plugins')
            .DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $name);
    }

    public function exists(string $type, string $name): bool
    {
        return preg_match(Manifest::NAME, $name) === 1 && is_dir($this->pathFor($type, $name));
    }

    /** @return Collection<int, Package> sorted by display name */
    public function all(string $type): Collection
    {
        $records = $this->records($type);
        $packages = collect();
        $root = $this->root($type);

        if (! is_dir($root)) {
            return $packages;
        }

        foreach (File::directories($root) as $vendorDir) {
            foreach (File::directories($vendorDir) as $packageDir) {
                $name = basename($vendorDir).'/'.basename($packageDir);

                if (! preg_match(Manifest::NAME, $name)) {
                    continue;
                }

                $packages->push($this->make($type, $name, $records->get($name)));
            }
        }

        return $packages->sortBy(fn (Package $p) => strtolower($p->displayName()))->values();
    }

    public function find(string $type, string $name): ?Package
    {
        if (! $this->exists($type, $name)) {
            return null;
        }

        return $this->make($type, $name, $this->records($type)->get($name));
    }

    private function make(string $type, string $name, ?Extension $record): Package
    {
        $path = $this->pathFor($type, $name);

        try {
            $manifest = Manifest::load($path, $type);

            if ($manifest->name() !== $name) {
                return new Package($type, $name, $path, null, $record, "The manifest says \"{$manifest->name()}\" but the folder is {$name}.");
            }

            return new Package($type, $name, $path, $manifest, $record);
        } catch (InvalidPackage $e) {
            return new Package($type, $name, $path, null, $record, $e->getMessage());
        }
    }

    /** @return Collection<string, Extension> */
    private function records(string $type): Collection
    {
        try {
            return Extension::query()->where('type', $type)->get()->keyBy('name');
        } catch (Throwable) {
            return collect();
        }
    }
}
