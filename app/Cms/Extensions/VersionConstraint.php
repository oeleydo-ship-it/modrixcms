<?php

namespace App\Cms\Extensions;

/**
 * Semantic versions and the small set of constraints packages may declare:
 * "*", "1.2.3", "^1.2", "~1.2", ">=1.0", ">1.0", "<=2.0", "<2.0", and several joined by spaces (AND).
 */
class VersionConstraint
{
    private const VERSION = '/^(\d+)\.(\d+)\.(\d+)(?:-[0-9A-Za-z.-]+)?$/';

    public static function isVersion(string $version): bool
    {
        return (bool) preg_match(self::VERSION, $version);
    }

    public static function isConstraint(string $constraint): bool
    {
        $parts = preg_split('/\s+/', trim($constraint)) ?: [];

        foreach ($parts as $part) {
            if ($part !== '*' && ! preg_match('/^(\^|~|>=|<=|>|<|=)?\d+(\.\d+){0,2}$/', $part)) {
                return false;
            }
        }

        return $parts !== [];
    }

    public static function satisfies(string $version, string $constraint): bool
    {
        if (! self::isVersion($version) || ! self::isConstraint($constraint)) {
            return false;
        }

        foreach (preg_split('/\s+/', trim($constraint)) as $part) {
            if (! self::satisfiesOne($version, $part)) {
                return false;
            }
        }

        return true;
    }

    /** -1, 0 or 1, like version_compare, ignoring pre-release tags. */
    public static function compare(string $a, string $b): int
    {
        return version_compare(self::core($a), self::core($b));
    }

    private static function satisfiesOne(string $version, string $part): bool
    {
        if ($part === '*') {
            return true;
        }

        preg_match('/^(\^|~|>=|<=|>|<|=)?(.+)$/', $part, $m);
        $operator = $m[1] ?: '=';
        $numbers = array_map('intval', explode('.', $m[2]));
        $base = implode('.', array_pad($numbers, 3, 0));
        $cmp = self::compare($version, $base);

        return match ($operator) {
            '=' => $cmp === 0,
            '>=' => $cmp >= 0,
            '>' => $cmp > 0,
            '<=' => $cmp <= 0,
            '<' => $cmp < 0,
            '^' => $cmp >= 0 && self::compare($version, self::caretCeiling($numbers)) < 0,
            '~' => $cmp >= 0 && self::compare($version, self::tildeCeiling($numbers)) < 0,
        };
    }

    /** ^1.2 → <2.0.0, ^0.3 → <0.4.0, ^0.0.4 → <0.0.5 */
    private static function caretCeiling(array $n): string
    {
        $n = array_pad($n, 3, 0);

        if ($n[0] > 0) {
            return ($n[0] + 1).'.0.0';
        }

        if ($n[1] > 0) {
            return '0.'.($n[1] + 1).'.0';
        }

        return '0.0.'.($n[2] + 1);
    }

    /** ~1.2 → <2.0.0, ~1.2.3 → <1.3.0 */
    private static function tildeCeiling(array $n): string
    {
        if (count($n) <= 2) {
            return ($n[0] + 1).'.0.0';
        }

        return $n[0].'.'.($n[1] + 1).'.0';
    }

    private static function core(string $version): string
    {
        return preg_replace('/-.*$/', '', $version);
    }
}
