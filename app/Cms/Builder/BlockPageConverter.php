<?php

namespace App\Cms\Builder;

use App\Cms\Builder\Schema\BlockType;
use App\Cms\Builder\Schema\BuildsDocumentNodes;
use App\Cms\Builder\Schema\DocumentUpgrader;
use App\Cms\Themes\DesignTokens;
use App\PageBuilder\PageBuilder;
use App\Support\Markdown;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Turns a page made with the block editor into a visual-builder document, one or more
 * builder sections per block, keeping its words, links, images and order.
 *
 * Colours come from the site's theme settings (CSS variables), so a converted page
 * follows the theme. Plugin blocks have no builder equivalent and are reported back
 * as skipped. The original blocks stay on the page, so the conversion can be undone.
 */
class BlockPageConverter
{
    use BuildsDocumentNodes;

    private const ICONS = [
        'bolt' => 'zap', 'shield' => 'shield', 'heart' => 'heart', 'star' => 'star', 'users' => 'users',
        'mail' => 'mail', 'globe' => 'globe', 'layout' => 'panels-top-left', 'page' => 'book-open',
        'lines' => 'layers', 'settings' => 'workflow', 'image' => 'video', 'cart' => 'package',
        'receipt' => 'clipboard-check', 'refresh' => 'workflow', 'code' => 'boxes', 'chart' => 'chart-no-axes-combined',
        'sparkle' => 'star',
    ];

    /** @var list<string> */
    private array $skipped = [];

    public function __construct(private PageBuilder $builder) {}

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return array{document: array<string, mixed>, skipped: list<string>}
     */
    public function convert(array $blocks): array
    {
        $this->skipped = [];
        $definitions = PageBuilder::definitions();
        $sections = [];

        foreach ($blocks as $block) {
            $type = (string) ($block['type'] ?? '');

            if (! empty($block['hidden'])) {
                continue;
            }

            if (! isset($definitions[$type])) {
                $this->skipped[] = $type;

                continue;
            }

            $data = array_replace($definitions[$type]['defaults'], (array) ($block['data'] ?? []));
            $converted = $this->sectionsFor($type, $data, $definitions[$type]['label']);

            if ($converted === []) {
                continue;
            }

            if (($data['anchor'] ?? '') !== '') {
                $converted[0]['settings'] = [...($converted[0]['settings'] ?? []), 'anchor' => Str::slug($data['anchor'])];
            }

            array_push($sections, ...$converted);
        }

        return [
            'document' => ['schema_version' => DocumentUpgrader::CURRENT_VERSION, 'settings' => [], 'sections' => $sections],
            'skipped' => array_values(array_unique($this->skipped)),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function sectionsFor(string $type, array $data, string $label): array
    {
        return match ($type) {
            'hero' => [$this->hero($data)],
            'page_header' => [$this->band([
                ...$this->eyebrow($data['eyebrow'] ?? ''),
                $this->heading((string) $data['title'], 'h1', $this->center(['fontSize' => $this->font('48px', '34px')])),
                ...$this->text($data['subtitle'] ?? '', true),
            ], DesignTokens::ref('surface'))],
            'text' => $this->filled($data['content'] ?? '') ? [$this->band([
                $this->blockNode(BlockType::Html, ['html' => Markdown::toHtml($data['content'])]),
            ], null, $data['width'] === 'narrow' ? 8 : 12)] : [],
            'image' => $this->filled($data['image'] ?? '') ? [$this->band(array_filter([
                $this->image((string) $data['alt'], [], Storage::disk('public')->url($data['image'])),
                $this->filled($data['caption'] ?? '') ? $this->paragraph((string) $data['caption'], $this->center(['color' => $this->responsive(DesignTokens::ref('muted'))])) : null,
            ]), null, $data['width'] === 'narrow' ? 8 : 12)] : [],
            'stack' => [$this->band([
                ...$this->text($data['label'] ?? '', true),
                $this->paragraph(implode('  ·  ', (array) $data['items']), $this->center(['fontWeight' => $this->responsive('600'), 'color' => $this->responsive(DesignTokens::ref('heading'))])),
            ], DesignTokens::ref('surface'), 12, '40px')],
            'features' => [$this->band([
                ...$this->intro($data),
                $this->blockNode(BlockType::FeatureCards, [
                    'columns' => 3,
                    'items' => array_map(fn ($card) => [
                        'icon' => self::ICONS[$card['icon'] ?? ''] ?? 'star',
                        'title' => trim(($card['title'] ?? '').(($card['badge'] ?? '') !== '' ? ' ('.$card['badge'].')' : '')),
                        'body' => $card['text'] ?? '',
                    ], (array) $data['items']),
                ]),
            ])],
            'marketplace' => [$this->marketplace($data)],
            'steps' => [$this->steps($data)],
            'comparison' => [$this->comparison($data)],
            'latest_posts' => [$this->band([
                ...$this->intro($data),
                $this->linkButton('Read the blog', route('blog.index'), 'secondary', 'md', $this->center()),
            ])],
            'cta' => [$this->band([
                $this->heading((string) $data['title'], 'h2', $this->center(['color' => $this->responsive(DesignTokens::ref('on_dark'))])),
                ...$this->text($data['text'] ?? '', true, DesignTokens::ref('on_dark_muted')),
                $this->buttons($data, 'primary', 'secondary'),
            ], DesignTokens::ref('dark'))],
            default => $this->skip($label),
        };
    }

    /** @return array<string, mixed> */
    private function hero(array $data): array
    {
        $title = trim(implode(' ', array_filter(array_map('trim', preg_split('/\R/', (string) $data['title'])))));
        $facts = array_map(fn ($fact) => '<b>'.e($fact['value']).'</b> '.e($fact['label']), (array) ($data['facts'] ?? []));

        $copy = array_values(array_filter([
            $this->filled($data['badge'] ?? '') ? $this->paragraph((string) $data['badge'], ['color' => $this->responsive(DesignTokens::ref('primary')), 'fontWeight' => $this->responsive('600')]) : null,
            $this->heading(trim($title.' '.($data['highlight'] ?? '')), 'h1', ['fontSize' => $this->font('56px', '36px')]),
            $this->filled($data['text'] ?? '') ? $this->paragraph((string) $data['text'], ['fontSize' => $this->responsive('19px'), 'color' => $this->responsive(DesignTokens::ref('body'))]) : null,
            $this->buttons($data, 'primary', 'secondary', false),
            $facts !== [] ? $this->paragraph(implode('   ·   ', $facts), ['color' => $this->responsive(DesignTokens::ref('muted'))], true) : null,
        ]));

        return $this->section([$this->row([$this->column(12, $copy)])], [
            'padding' => $this->pad('96px', '24px', '64px'),
            'backgroundColor' => $this->responsive(DesignTokens::ref('background')),
        ]);
    }

    /** @return array<string, mixed> */
    private function marketplace(array $data): array
    {
        $tiles = array_values(array_filter([
            ($data['modules'] ?? []) !== [] ? $this->heading('Modules', 'h3') : null,
            ($data['modules'] ?? []) !== [] ? $this->paragraph(implode(' · ', $data['modules'])) : null,
            ($data['themes'] ?? []) !== [] ? $this->heading('Themes', 'h3') : null,
            ($data['themes'] ?? []) !== [] ? $this->paragraph(implode(' · ', $data['themes'])) : null,
        ]));

        $copy = array_values(array_filter([
            ...$this->eyebrow($data['eyebrow'] ?? '', false),
            $this->filled($data['title'] ?? '') ? $this->heading((string) $data['title'], 'h2') : null,
            ...$this->text($data['text'] ?? ''),
            ($data['points'] ?? []) !== [] ? $this->blockNode(BlockType::Html, ['html' => '<ul>'.implode('', array_map(fn ($point) => '<li>'.e($point).'</li>', $data['points'])).'</ul>']) : null,
            $this->filled($data['button_label'] ?? '') ? $this->linkButton((string) $data['button_label'], $this->builder->url($data['button_url'] ?? '')) : null,
        ]));

        return $this->section([$this->row(array_values(array_filter([
            $this->column($tiles === [] ? 12 : 6, $copy),
            $tiles === [] ? null : $this->column(6, $tiles, ['backgroundColor' => $this->responsive(DesignTokens::ref('surface')), 'padding' => $this->responsive(['top' => '28px', 'right' => '28px', 'bottom' => '28px', 'left' => '28px'])]),
        ])))], ['padding' => $this->pad('88px', '24px', '56px')]);
    }

    /** @return array<string, mixed> */
    private function steps(array $data): array
    {
        $columns = array_map(fn ($step, $index) => $this->column(max(3, intdiv(12, max(1, min(4, count($data['items']))))), [
            $this->paragraph(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT), ['color' => $this->responsive(DesignTokens::ref('primary')), 'fontWeight' => $this->responsive('700')]),
            $this->heading((string) ($step['title'] ?? ''), 'h3'),
            $this->paragraph((string) ($step['text'] ?? '')),
        ]), (array) $data['items'], array_keys((array) $data['items']));

        $rows = [$this->row([$this->column(12, $this->intro($data))])];

        if ($columns !== []) {
            $rows[] = $this->row($columns);
        }

        if ($this->filled($data['code'] ?? '')) {
            $rows[] = $this->row([$this->column(12, [
                $this->blockNode(BlockType::Html, ['html' => '<pre style="background:var(--site-dark);color:var(--site-on-dark);padding:20px;border-radius:12px;overflow:auto"><code>'.e($data['code']).'</code></pre>']),
            ])]);
        }

        return $this->section($rows, ['padding' => $this->pad('88px', '24px', '56px')]);
    }

    /** @return array<string, mixed> */
    private function comparison(array $data): array
    {
        $rows = implode('', array_map(fn ($row) => '<tr><th style="text-align:left;padding:12px 8px">'.e($row['label'] ?? '').'</th><td style="padding:12px 8px"><strong>'.e($row['us'] ?? '').'</strong></td><td style="padding:12px 8px;color:var(--site-muted)"><s>'.e($row['them'] ?? '').'</s></td></tr>', (array) $data['rows']));

        return $this->band([
            ...$this->intro($data),
            $this->blockNode(BlockType::Html, ['html' => '<table style="width:100%;border-collapse:collapse">'.$rows.'</table>']),
        ]);
    }

    /**
     * One centred column in a padded section.
     *
     * @param  list<array<string, mixed>|null>  $blocks
     * @return array<string, mixed>
     */
    private function band(array $blocks, ?string $background = null, int $span = 12, string $padding = '88px'): array
    {
        $style = ['padding' => $this->pad($padding, '24px', '56px')];

        if ($background !== null) {
            $style['backgroundColor'] = $this->responsive($background);
        }

        $column = $span === 12
            ? [$this->column(12, array_values(array_filter($blocks)))]
            : [$this->column(2, []), $this->column($span, array_values(array_filter($blocks))), $this->column(2, [])];

        return $this->section([$this->row($column)], $style);
    }

    /** Eyebrow, heading and intro text, centred. @return list<array<string, mixed>> */
    private function intro(array $data): array
    {
        return array_values(array_filter([
            ...$this->eyebrow($data['eyebrow'] ?? ''),
            $this->filled($data['title'] ?? '') ? $this->heading((string) $data['title'], 'h2', $this->center()) : null,
            ...$this->text($data['text'] ?? '', true),
        ]));
    }

    /** @return list<array<string, mixed>> */
    private function eyebrow(string $text, bool $centred = true): array
    {
        $style = ['color' => $this->responsive(DesignTokens::ref('primary')), 'fontWeight' => $this->responsive('600'), 'textTransform' => $this->responsive('uppercase'), 'letterSpacing' => $this->responsive('0.08em'), 'fontSize' => $this->responsive('13px')];

        return $this->filled($text) ? [$this->paragraph($text, $centred ? $this->center($style) : $style)] : [];
    }

    /** @return list<array<string, mixed>> */
    private function text(string $text, bool $centred = false, ?string $color = null): array
    {
        $style = ['color' => $this->responsive($color ?? DesignTokens::ref('body'))];

        return $this->filled($text) ? [$this->paragraph($text, $centred ? $this->center($style) : $style)] : [];
    }

    /** Primary and secondary buttons side by side, as one HTML-free row of button blocks. */
    private function buttons(array $data, string $primaryVariant, string $secondaryVariant, bool $centred = true): ?array
    {
        $buttons = array_values(array_filter([
            $this->filled($data['primary_label'] ?? '') ? $this->linkButton((string) $data['primary_label'], $this->builder->url($data['primary_url'] ?? ''), $primaryVariant, 'lg') : null,
            $this->filled($data['secondary_label'] ?? '') ? $this->linkButton((string) $data['secondary_label'], $this->builder->url($data['secondary_url'] ?? ''), $secondaryVariant, 'lg') : null,
        ]));

        if ($buttons === []) {
            return null;
        }

        return [
            ...$this->blockNode(BlockType::Container, ['layout' => 'row', 'gap' => '12px'], $centred ? ['justifyContent' => $this->responsive('center')] : []),
            'children' => $buttons,
        ];
    }

    /**
     * The paragraph block reads a small HTML subset, so converted plain text is escaped;
     * only markup built here ($html) passes through.
     *
     * @return array<string, mixed>
     */
    protected function paragraph(string $text, array $style = [], bool $html = false): array
    {
        return $this->blockNode(BlockType::Paragraph, ['text' => $html ? $text : e($text)], $style);
    }

    /** Headings read the same inline HTML subset: converted text is escaped. @return array<string, mixed> */
    protected function heading(string $text, string $level, array $style = []): array
    {
        return $this->blockNode(BlockType::Heading, ['text' => e($text), 'level' => $level], $style);
    }

    private function center(array $style = []): array
    {
        return ['textAlign' => $this->responsive('center'), ...$style];
    }

    private function filled(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    /** @return list<array<string, mixed>> */
    private function skip(string $label): array
    {
        $this->skipped[] = $label;

        return [];
    }
}
