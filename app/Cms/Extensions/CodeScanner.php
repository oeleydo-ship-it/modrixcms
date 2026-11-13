<?php

namespace App\Cms\Extensions;

use Illuminate\Support\Facades\File;

/**
 * Lists risky patterns in a package's PHP files, with file and line, for the admin to review
 * before trusting it. A clean scan does not prove a package is safe.
 */
class CodeScanner
{
    /** @var array<int, array{0: string, 1: string, 2: string}> severity, pattern, explanation */
    private const RULES = [
        ['critical', '/\beval\s*\(/i', 'Runs text as PHP code (eval).'],
        ['critical', '/\b(shell_exec|exec|system|passthru|proc_open|popen|pcntl_exec)\s*\(/i', 'Runs shell commands on the server.'],
        ['critical', '/`[^`\n]*\$[^`\n]*`/', 'Runs a shell command (backticks).'],
        ['critical', '/\bcreate_function\s*\(|\bassert\s*\(\s*\$/i', 'Builds code from text at run time.'],
        ['high', '/\b(gzinflate|gzuncompress|str_rot13)\s*\(/i', 'Unpacks hidden code or data (often used to hide malware).'],
        ['high', '/base64_decode\s*\(\s*[\'"][A-Za-z0-9+\/=]{200,}/', 'Decodes a long hidden string.'],
        ['high', '/\b(include|require)(_once)?\s*\(?\s*\$/i', 'Loads a PHP file whose path is decided at run time.'],
        ['high', '/\bunserialize\s*\(/i', 'Unserializes data, which can be abused to run code.'],
        ['medium', '/\$[A-Za-z_][A-Za-z0-9_]*\s*\(/', 'Calls a function whose name is held in a variable.'],
        ['medium', '/DB::unprepared|->unprepared\s*\(/', 'Runs raw SQL statements.'],
        ['medium', '/\b(file_put_contents|fwrite|unlink|rmdir|chmod)\s*\(/i', 'Writes, deletes or changes files.'],
        ['info', '/\b(curl_exec|fsockopen|stream_socket_client)\s*\(|Http::|file_get_contents\s*\(\s*[\'"]https?:/i', 'Connects to other servers.'],
        ['info', '/\benv\s*\(|getenv\s*\(|\$_ENV\b/', 'Reads server environment values (may include secrets).'],
    ];

    /** @return array<int, array{severity: string, file: string, line: int, message: string}> */
    public function scan(string $root): array
    {
        $findings = [];

        foreach (File::allFiles($root) as $file) {
            if (! preg_match('/\.(php|phtml|inc)$/i', $file->getFilename())) {
                continue;
            }

            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $lines = preg_split('/\R/', (string) file_get_contents($file->getPathname()));

            foreach ($lines as $index => $line) {
                foreach (self::RULES as [$severity, $pattern, $message]) {
                    if (preg_match($pattern, $line)) {
                        $findings[] = ['severity' => $severity, 'file' => $relative, 'line' => $index + 1, 'message' => $message];

                        if (count($findings) >= 200) {
                            return $findings;
                        }
                    }
                }
            }
        }

        return $findings;
    }
}
