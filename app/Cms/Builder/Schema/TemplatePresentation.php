<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema;

/** Finishing rules for shipped templates, applied before a customer edits a copy. */
final class TemplatePresentation
{
    /** @param array<string, mixed> $document @return array<string, mixed> */
    public static function document(array $document): array
    {
        $document['sections'] = array_map(self::section(...), $document['sections'] ?? []);

        return $document;
    }

    /** @param array<string, mixed> $section @return array<string, mixed> */
    public static function section(array $section): array
    {
        $section['style'] = self::style($section['style'] ?? []);
        $background = self::desktop($section['style']['backgroundColor'] ?? '#17201b');
        foreach ($section['rows'] ?? [] as $ri => $row) {
            $row['style'] = self::style($row['style'] ?? []);
            $gap = $row['style']['gap'] ?? ['desktop' => '28px'];
            if (is_string($gap) || is_numeric($gap)) {
                $gap = ['desktop' => $gap];
            }
            if (is_array($gap) && ! isset($gap['mobile'])) {
                $gap['mobile'] = '12px';
            }
            $row['style']['gap'] = $gap;
            foreach ($row['columns'] ?? [] as $ci => $column) {
                $column['style'] = self::style($column['style'] ?? []);
                $column['blocks'] = array_map(fn (array $block): array => self::block($block, (string) $background), $column['blocks'] ?? []);
                $row['columns'][$ci] = $column;
            }
            $section['rows'][$ri] = $row;
        }

        return $section;
    }

    /** @param array<string, mixed> $block @return array<string, mixed> */
    private static function block(array $block, string $background): array
    {
        $style = self::style($block['style'] ?? []);
        $custom = $style['custom'] ?? [];
        if (isset($custom['backdrop-filter']) || isset($custom['-webkit-backdrop-filter'])) {
            unset($custom['backdrop-filter'], $custom['-webkit-backdrop-filter']);
            $color = (string) self::desktop($style['backgroundColor'] ?? $background);
            if (preg_match('/rgba\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*([.\d]+)\s*\)/', $color, $parts)) {
                $color = (float) $parts[4] <= 0.25 ? $background : "rgb({$parts[1]},{$parts[2]},{$parts[3]})";
            }
            $style['backgroundColor'] = ['desktop' => $color];
        }
        if (($block['type'] ?? null) === 'heading') {
            $custom['text-wrap'] = 'balance';
            $style['lineHeight'] ??= ['desktop' => '1.12'];
            $style['margin'] ??= ['desktop' => '0'];
        } elseif (($block['type'] ?? null) === 'paragraph') {
            $custom['text-wrap'] = 'pretty';
            $style['lineHeight'] ??= ['desktop' => '1.65'];
            if (! isset($style['maxWidth'])) {
                $style['maxWidth'] = ['desktop' => '66ch'];
                if (self::desktop($style['textAlign'] ?? '') === 'center') {
                    $margin = self::desktop($style['margin'] ?? []);
                    if (is_string($margin) && preg_match('/^(?:0|[\d.]+(?:px|rem|em))$/', $margin)) {
                        $margin = ['top' => $margin, 'bottom' => $margin];
                    }
                    if (is_array($margin)) {
                        $margins = is_array($style['margin'] ?? null) ? $style['margin'] : [];
                        $style['margin'] = [...$margins, 'desktop' => [...$margin, 'left' => 'auto', 'right' => 'auto']];
                    }
                }
            }
        } elseif (($block['type'] ?? null) === 'carousel') {
            $block['props']['autoplay'] = false;
        }
        if ($custom !== []) {
            $style['custom'] = $custom;
        } else {
            unset($style['custom']);
        }
        $block['style'] = $style;
        if (isset($block['children'])) {
            $childBackground = (string) self::desktop($style['backgroundColor'] ?? $background);
            $block['children'] = array_map(fn (array $child): array => self::block($child, $childBackground), $block['children']);
        }

        return $block;
    }

    /** @param array<string, mixed> $style @return array<string, mixed> */
    private static function style(array $style): array
    {
        if (isset($style['custom']['--fnl-effect'])) {
            $effects = preg_split('/\s+/', (string) $style['custom']['--fnl-effect']) ?: [];
            $effects = array_intersect($effects, ['press', 'zoom']);
            if ($effects === []) {
                unset($style['custom']['--fnl-effect']);
            } else {
                $style['custom']['--fnl-effect'] = implode(' ', $effects);
            }
        }
        if (($style['custom'] ?? null) === []) {
            unset($style['custom']);
        }

        return $style;
    }

    private static function desktop(mixed $value): mixed
    {
        return is_array($value) ? ($value['desktop'] ?? null) : $value;
    }
}
