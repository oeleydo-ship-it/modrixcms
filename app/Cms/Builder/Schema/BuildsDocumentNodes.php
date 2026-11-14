<?php

namespace App\Cms\Builder\Schema;

use Illuminate\Support\Str;

/**
 * Builds visual-builder document nodes (sections, rows, columns, blocks) with fresh ids
 * and every prop the renderer expects. Shared by the design library and the converter
 * that turns block-editor pages into builder pages.
 */
trait BuildsDocumentNodes
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    protected function section(array $rows, array $style = [], string $width = 'boxed', bool $sticky = false): array
    {
        return [
            'id' => (string) Str::ulid(),
            'width' => $width,
            'sticky' => $sticky,
            'style' => $style,
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $columns
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    protected function row(array $columns, array $style = []): array
    {
        return [
            'id' => (string) Str::ulid(),
            // Multi-column rows stack on mobile: three 4-wide columns on a phone would be unreadable.
            'stackBelow' => 'mobile',
            'style' => $style,
            'columns' => $columns,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    protected function column(int $span, array $blocks, array $style = []): array
    {
        return [
            'id' => (string) Str::ulid(),
            'span' => ['desktop' => $span, 'tablet' => $span <= 4 ? 6 : 12, 'mobile' => 12],
            'style' => $style,
            'blocks' => $blocks,
        ];
    }

    /**
     * @param  array<string, mixed>  $props
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    protected function blockNode(BlockType $type, array $props = [], array $style = []): array
    {
        return [
            'id' => (string) Str::ulid(),
            'type' => $type->value,
            // Registry defaults first, so the block has every prop the renderer expects.
            'props' => [...BlockRegistry::defaultProps($type), ...$props],
            'style' => $style,
        ];
    }

    /** @return array<string, mixed> */
    protected function heading(string $text, string $level, array $style = []): array
    {
        return $this->blockNode(BlockType::Heading, ['text' => $text, 'level' => $level], $style);
    }

    /**
     * `text`, not `html`: the paragraph block reads `text` and sanitises a small inline subset.
     *
     * @return array<string, mixed>
     */
    protected function paragraph(string $text, array $style = []): array
    {
        return $this->blockNode(BlockType::Paragraph, ['text' => $text], $style);
    }

    /** @return array<string, mixed> */
    protected function button(string $label, string $size = 'md', string $variant = 'primary', array $style = []): array
    {
        return $this->blockNode(BlockType::Button, [
            'label' => $label,
            // `scroll_to` keeps a template's buttons useful without inventing a URL that would 404.
            'action' => 'scroll_to',
            'size' => $size,
            'variant' => $variant,
        ], $style);
    }

    /** @return array<string, mixed> */
    protected function linkButton(string $label, string $url, string $variant = 'primary', string $size = 'md', array $style = []): array
    {
        return $this->blockNode(BlockType::Button, [
            'label' => $label,
            'action' => 'url',
            'url' => $url,
            'size' => $size,
            'variant' => $variant,
        ], $style);
    }

    /** @return array<string, mixed> */
    protected function image(string $alt, array $style = [], ?string $src = null): array
    {
        return $this->blockNode(BlockType::Image, ['src' => $src, 'alt' => $alt], $style);
    }

    /**
     * Every style value is responsive, so a bare value is wrapped.
     *
     * @return array<string, mixed>
     */
    protected function responsive(mixed $desktop): array
    {
        return ['desktop' => $desktop];
    }

    /**
     * A responsive font size: the desktop scale is nearly always too large on a phone.
     *
     * @return array<string, mixed>
     */
    protected function font(string $desktop, string $mobile): array
    {
        return ['desktop' => $desktop, 'mobile' => $mobile];
    }

    /**
     * Symmetric vertical padding with a tighter mobile tier.
     *
     * @return array<string, mixed>
     */
    protected function pad(string $vertical, string $horizontal, string $mobileVertical): array
    {
        return [
            'desktop' => ['top' => $vertical, 'right' => $horizontal, 'bottom' => $vertical, 'left' => $horizontal],
            'mobile' => ['top' => $mobileVertical, 'right' => '20px', 'bottom' => $mobileVertical, 'left' => '20px'],
        ];
    }
}
