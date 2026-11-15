<?php

/*
 * Builds a ready-to-upload release zip for hosts without Composer or Node (cPanel, Plesk,
 * DirectAdmin, shared hosting): production dependencies and the built front end are inside, so
 * the web installer at /install can run straight away.
 *
 *   php deploy/build-release.php                 clean build (composer install + npm ci + build)
 *   php deploy/build-release.php --skip-npm      reuse the public/build you already built
 *   php deploy/build-release.php --out=C:\path   where to write the zip
 *
 * Environment: COMPOSER_BIN (default "composer"), NPM_BIN (default "npm").
 *
 * The build happens in a temporary copy, so your working folder (and its dev dependencies) is
 * never touched. Files git ignores, your .env and your database are never included.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

$options = getopt('', ['skip-npm', 'out::', 'help']);

if (isset($options['help'])) {
    echo file_get_contents(__FILE__, false, null, 3, 1100)."\n";
    exit(0);
}

/** Left out of the release: tests, tooling and notes that are not needed to run the site. */
const EXCLUDE = [
    'tests/', 'phpunit.xml', 'AGENTS.md', 'CLAUDE.md', 'boost.json', 'docs/superpowers/', '.ai/', '.claude/',
    '.codex/', '.cursor/', '.github/', 'storage/app/releases/', 'deploy/cpanel.yml.example',
];

function say(string $message): void
{
    echo "\033[1;34m==>\033[0m {$message}\n";
}

function run(string $command, string $cwd): void
{
    $previous = getcwd();
    chdir($cwd);
    passthru($command, $code);
    chdir((string) $previous);

    if ($code !== 0) {
        fwrite(STDERR, "\nCommand failed ({$code}): {$command}\n");
        exit(1);
    }
}

function excluded(string $path): bool
{
    foreach (EXCLUDE as $pattern) {
        if (substr($pattern, -1) === '/' ? strpos($path, $pattern) === 0 : $path === $pattern) {
            return true;
        }
    }

    return false;
}

function removeTree(string $path): void
{
    if (! file_exists($path) && ! is_link($path)) {
        return;
    }

    if (is_file($path) || is_link($path)) {
        @unlink($path);

        return;
    }

    foreach (scandir($path) ?: [] as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            removeTree($path.DIRECTORY_SEPARATOR.$entry);
        }
    }

    @rmdir($path);
}

if (! class_exists('ZipArchive')) {
    fwrite(STDERR, "The PHP zip extension is required to build the release.\n");
    exit(1);
}

$composer = getenv('COMPOSER_BIN') ?: 'composer';
$npm = getenv('NPM_BIN') ?: 'npm';

$gitFiles = [];
exec('git ls-files --cached --others --exclude-standard', $gitFiles, $gitCode);
if ($gitCode !== 0 || $gitFiles === []) {
    fwrite(STDERR, "Run this inside the git repository (it uses git to know which files belong to the release).\n");
    exit(1);
}

$stage = sys_get_temp_dir().DIRECTORY_SEPARATOR.'modrix-release-'.bin2hex(random_bytes(4));
mkdir($stage, 0775, true);
register_shutdown_function(static fn () => removeTree($stage));

say('Copying project files');
$copied = 0;
foreach ($gitFiles as $file) {
    if ($file === '' || excluded($file) || ! is_file($file)) {
        continue;
    }

    $target = $stage.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $file);
    if (! is_dir(dirname($target))) {
        mkdir(dirname($target), 0775, true);
    }
    copy($file, $target);
    $copied++;
}
echo "{$copied} files\n";

say('Installing production dependencies');
run($composer.' install --no-dev --no-interaction --prefer-dist --optimize-autoloader', $stage);

if (isset($options['skip-npm'])) {
    say('Using the existing public/build');
    if (! is_dir($root.'/public/build')) {
        fwrite(STDERR, "public/build does not exist: run 'npm run build' first, or drop --skip-npm.\n");
        exit(1);
    }
    run((PHP_OS_FAMILY === 'Windows' ? 'xcopy /E /I /Q /Y ' : 'cp -R ').escapeshellarg($root.'/public/build').' '.escapeshellarg($stage.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'build'), $root);
} else {
    say('Building the front end');
    run($npm.' ci --ignore-scripts --no-audit --no-fund', $stage);
    run($npm.' run build', $stage);
    removeTree($stage.'/node_modules');
}

say('Cleaning the build');
foreach (['.env', 'public/hot', 'public/storage', 'public/extensions', 'node_modules'] as $path) {
    removeTree($stage.'/'.$path);
}
foreach (glob($stage.'/database/*.sqlite') ?: [] as $database) {
    @unlink($database);
}
foreach (glob($stage.'/bootstrap/cache/*.php') ?: [] as $cached) {
    @unlink($cached);
}
foreach (['storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache', 'database'] as $dir) {
    if (! is_dir($stage.'/'.$dir)) {
        mkdir($stage.'/'.$dir, 0775, true);
    }
}

if (! is_file($stage.'/vendor/autoload.php') || ! (is_file($stage.'/public/build/manifest.json') || is_file($stage.'/public/build/.vite/manifest.json'))) {
    fwrite(STDERR, "The build is incomplete (vendor/ or public/build is missing).\n");
    exit(1);
}

$revision = trim((string) shell_exec('git rev-parse --short HEAD 2>'.(PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'))) ?: 'local';
$default = $root.'/storage/app/releases';
$out = isset($options['out']) && is_string($options['out']) && $options['out'] !== '' ? rtrim($options['out'], '/\\') : $default;
if (! is_dir($out)) {
    mkdir($out, 0775, true);
}
$zipPath = $out.DIRECTORY_SEPARATOR.'modrix-'.date('Ymd').'-'.$revision.'.zip';
@unlink($zipPath);

say('Writing '.$zipPath);
$zip = new ZipArchive;
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Could not create the zip.\n");
    exit(1);
}

$base = strlen($stage) + 1;
$added = 0;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS), RecursiveIteratorIterator::SELF_FIRST);
foreach ($iterator as $item) {
    $relative = str_replace('\\', '/', substr($item->getPathname(), $base));

    if ($item->isDir()) {
        $zip->addEmptyDir($relative);
    } elseif ($item->isFile()) {
        $zip->addFile($item->getPathname(), $relative);
        $added++;
    }
}
$zip->close();

printf("\n\033[1;32mDone.\033[0m %d files, %.1f MB\n%s\nsha256 %s\n", $added, filesize($zipPath) / 1048576, $zipPath, hash_file('sha256', $zipPath));
echo "\nNext: upload and extract it into the domain's folder (document root on the \"public\" folder, or\n"
    ."keep everything in public_html: the root .htaccess handles it), then open https://your-domain/install\n";
