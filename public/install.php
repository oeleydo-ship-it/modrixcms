<?php

/*
 * Modrix CMS web installer: for cPanel, Plesk, DirectAdmin and any host without a terminal.
 *
 *   1. Upload the release zip (php deploy/build-release.php) and extract it, with the domain's
 *      document root on the "public" folder (or use the .htaccess in the project root).
 *   2. Open https://your-domain/install (or /install.php), fill in the form, press Install.
 *
 * It checks the server, tests the database, writes .env, runs the migrations, creates the first
 * admin, publishes the theme and plugin assets and locks itself (storage/app/installed.lock).
 * Once installed it refuses to run again; delete this file afterwards if you like.
 *
 * Written for old PHP syntax on purpose, so a host on an old PHP version shows a clear message
 * instead of a blank page.
 */

declare(strict_types=1);

if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>PHP version too old</title>'
        .'<body style="font:16px system-ui,sans-serif;max-width:560px;margin:15vh auto;padding:0 20px">'
        .'<h1>PHP 8.3 or newer is required</h1><p>This server runs PHP '.htmlspecialchars(PHP_VERSION).'. '
        .'In your hosting control panel (cPanel: "MultiPHP Manager", Plesk: "PHP Settings") choose PHP 8.3 or newer for this domain, then reload this page.</p></body>';
    exit;
}

$root = getenv('MODRIX_ROOT') ? rtrim((string) getenv('MODRIX_ROOT'), '/\\') : dirname(__DIR__);
$lockFile = $root.'/storage/app/installed.lock';
$envFile = $root.'/.env';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');

session_name('modrix_install');
session_start();

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Already installed: a lock file, or a .env that already has an application key. */
function isInstalled($root)
{
    if (is_file($root.'/storage/app/installed.lock')) {
        return true;
    }

    $env = $root.'/.env';

    return is_file($env) && preg_match('/^APP_KEY=\S+/m', (string) file_get_contents($env)) === 1;
}

/** @return array<int, array{0: string, 1: bool, 2: string, 3: bool}> label, ok, detail, blocks installation */
function requirements($root)
{
    $rows = [];
    $add = function ($label, $ok, $detail, $fatal) use (&$rows) {
        $rows[] = [$label, (bool) $ok, $detail, (bool) $fatal];
    };

    $add('PHP 8.3 or newer', version_compare(PHP_VERSION, '8.3.0', '>='), 'This server runs PHP '.PHP_VERSION, true);

    foreach (['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'json', 'mbstring', 'openssl', 'pdo', 'session', 'tokenizer', 'xml'] as $extension) {
        $add('PHP extension: '.$extension, extension_loaded($extension), extension_loaded($extension) ? 'Enabled' : 'Missing: enable it in your control panel (cPanel: "Select PHP Version")', true);
    }

    $drivers = array_intersect(['pdo_mysql', 'pdo_pgsql', 'pdo_sqlite'], get_loaded_extensions());
    $add('A database driver', count($drivers) > 0, count($drivers) > 0 ? implode(', ', $drivers) : 'Enable pdo_mysql (or pdo_sqlite, pdo_pgsql)', true);

    foreach (['zip' => 'uploading theme and plugin zips', 'gd' => 'image processing', 'intl' => 'localised formatting'] as $extension => $why) {
        $add('PHP extension: '.$extension.' (optional)', extension_loaded($extension), extension_loaded($extension) ? 'Enabled' : 'Recommended for '.$why, false);
    }

    $add('Application found', is_file($root.'/artisan'), $root, true);
    $add('Dependencies installed (vendor/)', is_file($root.'/vendor/autoload.php'), is_file($root.'/vendor/autoload.php') ? 'Found' : 'Missing: install from the release zip, or run "composer install --no-dev"', true);
    $built = is_file($root.'/public/build/manifest.json') || is_file($root.'/public/build/.vite/manifest.json');
    $add('Front end built (public/build)', $built, $built ? 'Found' : 'Missing: install from the release zip, or run "npm ci && npm run build"', true);

    foreach (['storage', 'storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache', 'database'] as $path) {
        $full = $root.'/'.$path;
        $ok = is_dir($full) && is_writable($full);
        $add('Writable: '.$path, $ok, $ok ? 'Yes' : 'Make this folder writable (permissions 755 or 775)', $path !== 'database');
    }

    $envFile = $root.'/.env';
    $envOk = is_file($envFile) ? is_writable($envFile) : is_writable($root);
    $add('Can create .env', $envOk, $envOk ? 'Yes' : 'The project folder (or .env) must be writable during installation', true);

    $publicOk = is_dir(__DIR__.'/extensions') ? is_writable(__DIR__.'/extensions') : is_writable(__DIR__);
    $add('Writable: public/extensions', $publicOk, $publicOk ? 'Yes' : 'Theme and plugin assets are copied here', true);

    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? (string) realpath((string) $_SERVER['DOCUMENT_ROOT']) : '';
    $publicDir = (string) realpath(__DIR__);
    if ($docRoot !== '' && $docRoot !== $publicDir) {
        $hasRootHtaccess = is_file($root.'/.htaccess');
        $add('Document root', $hasRootHtaccess, $hasRootHtaccess
            ? 'The domain points at the project folder: the .htaccess in it sends visitors to /public.'
            : 'The domain points at the project folder and there is no .htaccess in it. Point the document root to the "public" folder instead.', false);
    }

    return $rows;
}

function baseUrl()
{
    $https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'localhost';
    $dir = str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '/'));
    $dir = preg_replace('#/public$#', '', rtrim($dir, '/'));

    return ($https ? 'https://' : 'http://').$host.$dir;
}

function availableDrivers()
{
    $loaded = get_loaded_extensions();
    $drivers = [];

    if (in_array('pdo_mysql', $loaded, true)) {
        $drivers['mysql'] = 'MySQL / MariaDB';
    }
    if (in_array('pdo_pgsql', $loaded, true)) {
        $drivers['pgsql'] = 'PostgreSQL';
    }
    if (in_array('pdo_sqlite', $loaded, true)) {
        $drivers['sqlite'] = 'SQLite (a single file)';
    }

    return $drivers;
}

/** @return array<int, string> */
function validateInput(array $in, array $drivers)
{
    $errors = [];

    if ($in['site_name'] === '' || mb_strlen($in['site_name']) > 100) {
        $errors[] = 'Enter a site name (up to 100 characters).';
    }
    if (! filter_var($in['app_url'], FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $in['app_url'])) {
        $errors[] = 'The site address must be a full URL such as https://example.com.';
    }
    if (! isset($drivers[$in['db_driver']])) {
        $errors[] = 'Choose a database type.';
    }

    if ($in['db_driver'] === 'mysql' || $in['db_driver'] === 'pgsql') {
        if (! preg_match('/^[A-Za-z0-9._:\-]{1,255}$/', $in['db_host'])) {
            $errors[] = 'Enter the database host (usually localhost).';
        }
        if (! ctype_digit($in['db_port']) || (int) $in['db_port'] < 1 || (int) $in['db_port'] > 65535) {
            $errors[] = 'The database port must be a number between 1 and 65535.';
        }
        if (! preg_match('/^[A-Za-z0-9_.$\-]{1,64}$/', $in['db_name'])) {
            $errors[] = 'Enter the database name (letters, numbers, underscore, dash).';
        }
        if (! preg_match('/^[A-Za-z0-9_.@$\-]{1,80}$/', $in['db_user'])) {
            $errors[] = 'Enter the database username.';
        }
        if (strlen($in['db_pass']) > 200) {
            $errors[] = 'The database password is too long.';
        }
    }

    if (! preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $in['admin_email']) || strlen($in['admin_email']) > 190) {
        $errors[] = 'Enter a valid admin email address.';
    }
    if ($in['admin_name'] === '' || mb_strlen($in['admin_name']) > 100) {
        $errors[] = 'Enter the admin name.';
    }
    if (strlen($in['admin_pass']) < 10) {
        $errors[] = 'The admin password needs at least 10 characters.';
    }
    if ($in['admin_pass'] !== $in['admin_pass2']) {
        $errors[] = 'The two admin passwords do not match.';
    }

    return $errors;
}

/** Connects with the submitted details; creates the SQLite file when needed. */
function connectDatabase(array $in, $root)
{
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8];

    if ($in['db_driver'] === 'sqlite') {
        $file = $root.'/database/database.sqlite';
        if (! is_file($file) && @file_put_contents($file, '') === false) {
            throw new RuntimeException('Could not create database/database.sqlite: make the database folder writable.');
        }

        return new PDO('sqlite:'.$file, null, null, $options);
    }

    $dsn = $in['db_driver'].':host='.$in['db_host'].';port='.$in['db_port'].';dbname='.$in['db_name']
        .($in['db_driver'] === 'mysql' ? ';charset=utf8mb4' : '');

    return new PDO($dsn, $in['db_user'], $in['db_pass'], $options);
}

/** One .env value, quoted when it needs to be (verified to round-trip through phpdotenv). */
function envValue($value)
{
    $value = (string) $value;

    if ($value === '') {
        return '';
    }
    if (preg_match('/^[A-Za-z0-9_.:\/@+=,-]+$/', $value)) {
        return $value;
    }

    return '"'.strtr($value, ['\\' => '\\\\', '"' => '\\"', '$' => '\\$', "\r" => '', "\n" => '\\n']).'"';
}

function setEnvKey($contents, $key, $value)
{
    $line = $key.'='.envValue($value);
    $quoted = preg_quote($key, '/');

    foreach (['/^'.$quoted.'=.*$/m', '/^#\s*'.$quoted.'=.*$/m'] as $pattern) {
        if (preg_match($pattern, $contents)) {
            return preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $contents, 1);
        }
    }

    return rtrim($contents, "\r\n")."\n".$line."\n";
}

function buildEnv(array $in, $root)
{
    $base = is_file($root.'/.env') ? $root.'/.env' : $root.'/.env.example';
    $contents = is_file($base) ? (string) file_get_contents($base) : '';
    $contents = str_replace("\r\n", "\n", $contents);

    $values = [
        'APP_NAME' => $in['site_name'],
        'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
        'APP_DEBUG' => 'false',
        'APP_URL' => rtrim($in['app_url'], '/'),
        'LOG_LEVEL' => 'error',
        'DB_CONNECTION' => $in['db_driver'],
    ];

    if ($in['db_driver'] === 'sqlite') {
        $values['DB_DATABASE'] = $root.'/database/database.sqlite';
    } else {
        $values['DB_HOST'] = $in['db_host'];
        $values['DB_PORT'] = $in['db_port'];
        $values['DB_DATABASE'] = $in['db_name'];
        $values['DB_USERNAME'] = $in['db_user'];
        $values['DB_PASSWORD'] = $in['db_pass'];
    }

    foreach ($values as $key => $value) {
        $contents = setEnvKey($contents, $key, $value);
    }

    return $contents;
}

/** @return array<int, string> what was done */
function runInstall(array $in, $root, $lockFile)
{
    $log = [];
    $envFile = $root.'/.env';
    $originalEnv = is_file($envFile) ? (string) file_get_contents($envFile) : null;

    @set_time_limit(0);
    @ignore_user_abort(true);

    connectDatabase($in, $root);
    $log[] = 'The database connection works.';

    if (@file_put_contents($envFile, buildEnv($in, $root), LOCK_EX) === false) {
        throw new RuntimeException('Could not write .env: make the project folder writable.');
    }
    $log[] = 'Wrote .env with a new application key (debug off, production mode).';

    try {
        // The first admin comes from the seeder, which reads these (never written to .env).
        foreach (['ADMIN_EMAIL' => $in['admin_email'], 'ADMIN_PASSWORD' => $in['admin_pass']] as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        foreach (['config.php', 'routes-v7.php', 'events.php'] as $cached) {
            @unlink($root.'/bootstrap/cache/'.$cached);
        }

        chdir($root);
        require_once $root.'/vendor/autoload.php';
        $app = require $root.'/bootstrap/app.php';
        $kernel = $app->make('Illuminate\Contracts\Console\Kernel');

        $call = function ($command, array $params) use ($kernel) {
            $code = $kernel->call($command, $params);
            $output = trim($kernel->output());

            if ($code !== 0) {
                throw new RuntimeException($command.' failed: '.($output !== '' ? $output : 'exit code '.$code));
            }

            return $output;
        };

        $call('migrate', ['--force' => true]);
        $log[] = 'Created the database tables (migrations).';

        if ($in['starter']) {
            $call('db:seed', ['--force' => true]);
            $log[] = 'Added the starter pages and posts.';
        }

        $admin = $app->make('App\Models\User')->newQuery()->firstOrNew(['email' => $in['admin_email']]);
        $admin->name = $in['admin_name'];
        $admin->password = $in['admin_pass'];
        $admin->is_admin = true;
        $admin->save();
        $log[] = 'Created the admin account '.$in['admin_email'].'.';

        $app->make('App\Support\Settings')->set(['site_name' => $in['site_name']]);

        $call('cms:sync', []);
        $log[] = 'Published theme and plugin assets.';

        try {
            $call('storage:link', ['--force' => true]);
            $log[] = 'Linked public/storage.';
        } catch (Throwable $e) {
            $log[] = 'Could not link public/storage (symlinks may be disabled on this host): uploaded images may not show until it is linked.';
        }

        $call('optimize:clear', []);
    } catch (Throwable $e) {
        // Put .env back as it was, so the installer can be run again after fixing the problem.
        if ($originalEnv === null) {
            @unlink($envFile);
        } else {
            @file_put_contents($envFile, $originalEnv);
        }

        throw $e;
    }

    @mkdir(dirname($lockFile), 0775, true);
    @file_put_contents($lockFile, json_encode(['installed_at' => gmdate('c'), 'php' => PHP_VERSION]).PHP_EOL);
    $log[] = 'Locked the installer so it cannot run again.';

    return $log;
}

function page($title, $body)
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        .'<meta name="robots" content="noindex,nofollow"><title>'.h($title).'</title><style>'
        .':root{--ink:#0d1220;--body:#33394a;--muted:#5b6478;--line:#e4e6ee;--brand:#5b3df5;--brand-d:#3a22c9;--ok:#157347;--bad:#b42318;--bg:#f7f6f2}'
        .'*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--body);font:16px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}'
        .'main{max-width:760px;margin:0 auto;padding:40px 20px 80px}h1{color:var(--ink);font-size:30px;line-height:1.15;margin:0 0 6px;letter-spacing:-.02em}'
        .'h2{color:var(--ink);font-size:19px;margin:0 0 14px}p.lead{color:var(--muted);margin:0 0 28px}'
        .'.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:24px;margin-bottom:18px}'
        .'label{display:block;font-weight:600;color:var(--ink);font-size:14px;margin:14px 0 6px}label small{font-weight:400;color:var(--muted)}'
        .'input[type=text],input[type=url],input[type=email],input[type=password],input[type=number],select{width:100%;padding:11px 13px;border:1px solid #cfd3e0;border-radius:10px;font:inherit;color:var(--ink);background:#fff}'
        .'input:focus,select:focus{outline:2px solid var(--brand);outline-offset:1px;border-color:var(--brand)}'
        .'.grid{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}@media(max-width:560px){.grid{grid-template-columns:1fr}}'
        .'table{width:100%;border-collapse:collapse;font-size:14px}td{padding:7px 14px 7px 0;border-bottom:1px solid var(--line);vertical-align:top}td:first-child{width:42%}td:nth-child(2){width:56px}tr:last-child td{border-bottom:0}details.card>summary{cursor:pointer;list-style:none;font-weight:600;color:var(--ink);display:flex;justify-content:space-between;gap:12px}details.card>summary::-webkit-details-marker{display:none}details.card>summary::after{content:"Show details";font-weight:400;color:var(--muted);font-size:14px}details.card[open]>summary::after{content:"Hide details"}details.card>table{margin-top:14px}'
        .'.ok{color:var(--ok);font-weight:600}.bad{color:var(--bad);font-weight:600}.warn{color:#9a6700;font-weight:600}.muted{color:var(--muted)}'
        .'.errors{background:#fef3f2;border:1px solid #fecdca;color:var(--bad);border-radius:12px;padding:14px 18px;margin-bottom:18px}.errors ul{margin:0;padding-left:18px}'
        .'button,.button{display:inline-block;background:var(--brand);color:#fff;border:0;border-radius:999px;padding:13px 28px;font:inherit;font-weight:600;cursor:pointer;text-decoration:none}'
        .'button:hover,.button:hover{background:var(--brand-d)}button[disabled]{background:#b9bcc9;cursor:not-allowed}'
        .'.check{display:flex;gap:10px;align-items:flex-start;margin-top:16px;font-weight:400}.check input{margin-top:5px}'
        .'.log li{margin:6px 0}code{background:#eef0f6;padding:2px 6px;border-radius:6px;font-size:.9em}'
        .'</style></head><body><main>'.$body.'</main></body></html>';
}

/* ------------------------------------------------------------------ already installed */

if (isInstalled($root)) {
    http_response_code(403);
    page('Already installed', '<h1>Already installed</h1><p class="lead">This site is set up, so the installer is switched off.</p>'
        .'<div class="card"><p>Sign in to the admin to manage your site. To reinstall, delete <code>.env</code> and <code>storage/app/installed.lock</code> first.</p>'
        .'<p><a class="button" href="'.h(baseUrl()).'/admin">Open the admin</a></p></div>');
    exit;
}

/* ------------------------------------------------------------------ form handling */

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$drivers = availableDrivers();
$requirements = requirements($root);
$blocked = false;
foreach ($requirements as $row) {
    if (! $row[1] && $row[3]) {
        $blocked = true;
    }
}

$field = function ($name, $default = '') {
    return isset($_POST[$name]) ? trim((string) $_POST[$name]) : $default;
};

$defaultDriver = isset($drivers['mysql']) ? 'mysql' : (string) key($drivers);
$in = [
    'site_name' => $field('site_name', 'Modrix CMS'),
    'app_url' => $field('app_url', baseUrl()),
    'db_driver' => $field('db_driver', $defaultDriver),
    'db_host' => $field('db_host', 'localhost'),
    'db_port' => $field('db_port', '3306'),
    'db_name' => $field('db_name'),
    'db_user' => $field('db_user'),
    'db_pass' => isset($_POST['db_pass']) ? (string) $_POST['db_pass'] : '',
    'admin_name' => $field('admin_name', 'Admin'),
    'admin_email' => $field('admin_email'),
    'admin_pass' => isset($_POST['admin_pass']) ? (string) $_POST['admin_pass'] : '',
    'admin_pass2' => isset($_POST['admin_pass2']) ? (string) $_POST['admin_pass2'] : '',
    'starter' => $_SERVER['REQUEST_METHOD'] !== 'POST' || isset($_POST['starter']),
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (! isset($_POST['csrf']) || ! hash_equals($_SESSION['csrf'], (string) $_POST['csrf'])) {
        $errors[] = 'The form expired. Reload the page and try again.';
    } elseif ($blocked) {
        $errors[] = 'Fix the red items under "Server check" first.';
    } else {
        $errors = validateInput($in, $drivers);
    }

    if (! $errors) {
        try {
            $log = runInstall($in, $root, $lockFile);
            $_SESSION = [];
            $items = '';
            foreach ($log as $line) {
                $items .= '<li>'.h($line).'</li>';
            }
            page('Installed', '<h1>Installed</h1><p class="lead">Your site is ready.</p><div class="card"><ul class="log">'.$items.'</ul></div>'
                .'<div class="card"><h2>Next</h2><p><a class="button" href="'.h(rtrim($in['app_url'], '/')).'/admin">Sign in to the admin</a></p>'
                .'<p class="muted">Sign in with <strong>'.h($in['admin_email']).'</strong> and the password you chose. '
                .'For tidiness you can delete <code>public/install.php</code>: it is already locked and will not run again.</p></div>');
            exit;
        } catch (Throwable $e) {
            $message = $e->getMessage();
            foreach ([$in['db_pass'], $in['admin_pass']] as $secret) {
                if ($secret !== '') {
                    $message = str_replace($secret, '***', $message);
                }
            }
            $errors[] = 'Installation stopped: '.$message;
        }
    }
}

/* ------------------------------------------------------------------ the form */

$rows = '';
foreach ($requirements as $row) {
    $class = $row[1] ? 'ok' : ($row[3] ? 'bad' : 'warn');
    $rows .= '<tr><td>'.h($row[0]).'</td><td class="'.$class.'">'.($row[1] ? 'OK' : ($row[3] ? 'Fix' : 'Note')).'</td><td class="muted">'.h($row[2]).'</td></tr>';
}

$passed = 0;
foreach ($requirements as $row) {
    $passed += $row[1] ? 1 : 0;
}
$allPassed = $passed === count($requirements);
$checkCard = '<details class="card"'.($allPassed ? '' : ' open').'><summary><span>Server check: '
    .($allPassed ? '<span class="ok">all '.count($requirements).' checks passed</span>' : '<span class="'.($blocked ? 'bad' : 'warn').'">'.$passed.' of '.count($requirements).' passed</span>')
    .'</span></summary><table>'.$rows.'</table></details>';

$errorHtml = '';
if ($errors) {
    $errorHtml = '<div class="errors"><strong>Please fix this:</strong><ul>';
    foreach ($errors as $error) {
        $errorHtml .= '<li>'.h($error).'</li>';
    }
    $errorHtml .= '</ul></div>';
}

$driverOptions = '';
foreach ($drivers as $value => $label) {
    $driverOptions .= '<option value="'.h($value).'"'.($in['db_driver'] === $value ? ' selected' : '').'>'.h($label).'</option>';
}

$input = function ($label, $name, $value, $type = 'text', $extra = '') {
    return '<label for="'.h($name).'">'.$label.'</label><input id="'.h($name).'" name="'.h($name).'" type="'.h($type).'" value="'.h($value).'" '.$extra.'>';
};

$body = '<h1>Install Modrix CMS</h1><p class="lead">Five minutes: check the server, connect the database, create your admin.</p>'
    .$errorHtml
    .$checkCard
    .'<form method="post" autocomplete="off"><input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'">'
    .'<div class="card"><h2>Your site</h2>'
    .$input('Site name', 'site_name', $in['site_name'], 'text', 'required maxlength="100"')
    .$input('Site address <small>(where visitors will find it)</small>', 'app_url', $in['app_url'], 'url', 'required')
    .'</div>'
    .'<div class="card"><h2>Database</h2><p class="muted" style="margin-top:0">Use an empty database. In cPanel: "MySQL Databases", create a database and a user, add the user to the database with all privileges.</p>'
    .'<label for="db_driver">Type</label><select id="db_driver" name="db_driver">'.$driverOptions.'</select>'
    .'<div id="db_server"><div class="grid">'
    .'<div>'.$input('Host', 'db_host', $in['db_host']).'</div><div>'.$input('Port', 'db_port', $in['db_port'], 'number', 'min="1" max="65535"').'</div></div>'
    .$input('Database name', 'db_name', $in['db_name'])
    .'<div class="grid"><div>'.$input('Username', 'db_user', $in['db_user']).'</div><div>'.$input('Password', 'db_pass', '', 'password', 'autocomplete="new-password"').'</div></div></div>'
    .'</div>'
    .'<div class="card"><h2>Admin account</h2><div class="grid"><div>'.$input('Name', 'admin_name', $in['admin_name'], 'text', 'required maxlength="100"').'</div>'
    .'<div>'.$input('Email', 'admin_email', $in['admin_email'], 'email', 'required autocomplete="off"').'</div></div>'
    .'<div class="grid"><div>'.$input('Password <small>(10+ characters)</small>', 'admin_pass', '', 'password', 'required minlength="10" autocomplete="new-password"').'</div>'
    .'<div>'.$input('Repeat password', 'admin_pass2', '', 'password', 'required minlength="10" autocomplete="new-password"').'</div></div>'
    .'<label class="check"><input type="checkbox" name="starter" value="1"'.($in['starter'] ? ' checked' : '').'> Add the starter pages and a sample post</label></div>'
    .'<button type="submit"'.($blocked ? ' disabled' : '').' id="go">Install</button> '
    .($blocked ? '<span class="bad">Fix the red items above, then reload.</span>' : '<span class="muted" id="wait"></span>')
    .'</form>'
    .'<script>(function(){var d=document.getElementById("db_driver"),s=document.getElementById("db_server"),p=document.getElementById("db_port");'
    .'function sync(){s.style.display=d.value==="sqlite"?"none":"";var f=s.querySelectorAll("input:not([type=password])");for(var i=0;i<f.length;i++){f[i].required=d.value!=="sqlite"&&f[i].id!=="db_pass"}'
    .'if(d.value==="pgsql"&&p.value==="3306"){p.value="5432"}if(d.value==="mysql"&&p.value==="5432"){p.value="3306"}}'
    .'d.addEventListener("change",sync);sync();'
    .'var f=document.querySelector("form");f.addEventListener("submit",function(){var b=document.getElementById("go");b.disabled=true;b.textContent="Installing...";document.getElementById("wait").textContent="This can take up to a minute."})})();</script>';

page('Install Modrix CMS', $body);
