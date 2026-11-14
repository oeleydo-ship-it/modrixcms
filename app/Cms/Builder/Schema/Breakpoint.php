<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema;

/**
 * The three breakpoints the builder, the style model and the renderer agree on.
 *
 * Desktop is the base: every style value must have a desktop value, while
 * tablet and mobile hold optional overrides that cascade downwards
 * (mobile inherits tablet, tablet inherits desktop).
 */
enum Breakpoint: string
{
    case Desktop = 'desktop';
    case Tablet = 'tablet';
    case Mobile = 'mobile';

    public function label(): string
    {
        return match ($this) {
            self::Desktop => 'Desktop',
            self::Tablet => 'Tablet',
            self::Mobile => 'Mobile',
        };
    }

    /**
     * Max-width in pixels this breakpoint applies at. Desktop has no ceiling.
     */
    public function maxWidth(): ?int
    {
        return match ($this) {
            self::Desktop => null,
            self::Tablet => 1024,
            self::Mobile => 640,
        };
    }

    /**
     * The breakpoint a missing value falls back to, if any.
     */
    public function inheritsFrom(): ?self
    {
        return match ($this) {
            self::Desktop => null,
            self::Tablet => self::Desktop,
            self::Mobile => self::Tablet,
        };
    }

    /**
     * Resolution order for a value at this breakpoint, most specific first.
     *
     * @return list<self>
     */
    public function cascade(): array
    {
        $chain = [$this];
        $current = $this;

        while (($current = $current->inheritsFrom()) !== null) {
            $chain[] = $current;
        }

        return $chain;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
