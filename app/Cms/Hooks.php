<?php

namespace App\Cms;

use Illuminate\Support\HtmlString;
use Throwable;

/**
 * Output hooks and filters for plugins and themes.
 *
 *   Hooks::add('head', fn () => '<meta name="x" content="y">');   // printed by @hook('head')
 *   Hooks::filter('page.blocks', fn ($blocks, $page) => $blocks);  // applied by the core
 *
 * Output from hooks is printed as-is (HTML): escape any user data you include.
 * A callback that throws is reported and skipped, so it can't take the page down.
 */
class Hooks
{
    /** Output points rendered by the core layouts. */
    public const OUTPUTS = ['head', 'body.start', 'body.end', 'admin.head'];

    /** Filters applied by the core. */
    public const FILTERS = ['page.blocks', 'menu.pages', 'block.data'];

    /** @var array<string, array<int, array<int, callable>>> */
    private array $actions = [];

    /** @var array<string, array<int, array<int, callable>>> */
    private array $filters = [];

    public static function add(string $name, callable $callback, int $priority = 10): void
    {
        app(self::class)->actions[$name][$priority][] = $callback;
    }

    public static function filter(string $name, callable $callback, int $priority = 10): void
    {
        app(self::class)->filters[$name][$priority][] = $callback;
    }

    public function render(string $name, mixed ...$args): HtmlString
    {
        $html = '';

        foreach ($this->sorted($this->actions[$name] ?? []) as $callback) {
            try {
                $html .= (string) $callback(...$args);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return new HtmlString($html);
    }

    public function apply(string $name, mixed $value, mixed ...$args): mixed
    {
        foreach ($this->sorted($this->filters[$name] ?? []) as $callback) {
            try {
                $value = $callback($value, ...$args);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $value;
    }

    /** @return array<int, callable> */
    private function sorted(array $byPriority): array
    {
        ksort($byPriority);

        return $byPriority === [] ? [] : array_merge(...array_values($byPriority));
    }
}
