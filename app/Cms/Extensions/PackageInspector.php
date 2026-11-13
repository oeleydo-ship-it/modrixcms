<?php

namespace App\Cms\Extensions;

use App\Models\Extension;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Checks an uploaded zip and extracts it to a temporary folder. Nothing reaches the
 * extensions folder from here: the installer moves the folder once everything passed.
 */
class PackageInspector
{
    /** Never allowed anywhere in a package. */
    private const BLOCKED = '/(\.(phar|exe|dll|so|dylib|sh|bash|bat|cmd|com|ps1|cgi|pl|py)$)|(^|\/)(\.htaccess|\.htpasswd|\.user\.ini|php\.ini|web\.config|\.env(\..*)?)$/i';

    /** Anything PHP can execute. */
    private const PHP = '/\.(php\d?|phtml|phps|pht|phar|inc)$/i';

    /**
     * @return array{0: Manifest, 1: string} the manifest and the temporary folder holding the package
     *
     * @throws InvalidPackage
     */
    public function extract(string $zipPath, string $type): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new InvalidPackage('The PHP zip extension is needed to install packages.');
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::RDONLY) !== true) {
            throw new InvalidPackage('The file is not a valid zip archive.');
        }

        try {
            $names = $this->checkEntries($zip, $type);

            $temp = config('cms.storage_path').'/tmp/'.Str::lower(Str::random(16));
            File::ensureDirectoryExists($temp);

            foreach ($names as $index => $name) {
                if (str_ends_with($name, '/')) {
                    File::ensureDirectoryExists($temp.'/'.$name);

                    continue;
                }

                $target = $temp.'/'.$name;
                File::ensureDirectoryExists(dirname($target));
                $stream = $zip->getStream($zip->getNameIndex($index));

                if ($stream === false) {
                    throw new InvalidPackage("Could not read {$name} from the zip.");
                }

                $out = fopen($target, 'wb');
                stream_copy_to_stream($stream, $out);
                fclose($out);
                fclose($stream);
            }
        } catch (InvalidPackage $e) {
            if (isset($temp)) {
                File::deleteDirectory($temp);
            }

            throw $e;
        } finally {
            $zip->close();
        }

        try {
            $manifest = Manifest::load($temp, $type);
        } catch (InvalidPackage $e) {
            File::deleteDirectory($temp);

            throw $e;
        }

        return [$manifest, $temp];
    }

    /**
     * @return array<int, string> index → normalised entry name
     *
     * @throws InvalidPackage
     */
    private function checkEntries(ZipArchive $zip, string $type): array
    {
        $limits = config('cms.limits');

        if ($zip->numFiles === 0) {
            throw new InvalidPackage('The zip is empty.');
        }

        if ($zip->numFiles > $limits['files']) {
            throw new InvalidPackage("The zip has more than {$limits['files']} files.");
        }

        $total = 0;
        $names = [];
        $manifestFile = Manifest::fileName($type);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'] ?? '';

            if (! Manifest::isSafeRelativePath(rtrim($name, '/'))) {
                throw new InvalidPackage("Unsafe path in the zip: {$name}");
            }

            $zip->getExternalAttributesIndex($i, $opsys, $attributes);

            if ($opsys === ZipArchive::OPSYS_UNIX && (($attributes >> 16) & 0170000) === 0120000) {
                throw new InvalidPackage("Symbolic links are not allowed: {$name}");
            }

            $total += $stat['size'];

            if ($total > $limits['extracted_mb'] * 1024 * 1024) {
                throw new InvalidPackage("The package is larger than {$limits['extracted_mb']} MB when unpacked.");
            }

            if (! str_ends_with($name, '/')) {
                $this->checkFile($name, $type);
            }

            $names[$i] = $name;
        }

        if (! in_array($manifestFile, $names, true)) {
            $hint = collect($names)->first(fn ($n) => str_ends_with($n, '/'.$manifestFile));
            throw new InvalidPackage("{$manifestFile} must be at the root of the zip".($hint ? " (found {$hint}: zip the folder's contents, not the folder)." : '.'));
        }

        return $names;
    }

    /** @throws InvalidPackage */
    public function checkFile(string $name, string $type): void
    {
        if (preg_match(self::BLOCKED, $name)) {
            throw new InvalidPackage("This file type is not allowed in a package: {$name}");
        }

        if (! preg_match(self::PHP, $name)) {
            return;
        }

        if (str_starts_with($name, 'public/')) {
            throw new InvalidPackage("PHP files can't be inside public/ (it is copied to the web root): {$name}");
        }

        if ($type === Extension::THEME) {
            throw new InvalidPackage("Themes can't contain PHP. Put the theme's React code in compiled .js files under public/: {$name}");
        }
    }

    /**
     * Applies the same file rules to a package already on disk (scaffolds, packages copied by hand).
     *
     * @throws InvalidPackage
     */
    public function checkDirectory(string $root, string $type): void
    {
        foreach (File::allFiles($root, true) as $file) {
            $this->checkFile(str_replace('\\', '/', $file->getRelativePathname()), $type);
        }
    }
}
