<?php

namespace App\Cms\Frontend;

use App\Cms\Hooks;
use App\Models\Post;
use App\PageBuilder\Icons;
use App\PageBuilder\PageBuilder;
use App\Support\Markdown;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Turns saved blocks into props for the React block components. Work that needs the
 * server is done here, by field type, so components (core, theme or plugin) stay simple:
 *
 *   url       → resolved link (@github / @marketplace follow Settings)
 *   markdown  → also "{name}_html" (safe HTML)
 *   textarea  → also "{name}_html" (inline Markdown: **bold**, `code`, links)
 *   icon      → also "{name}_svg" (inner SVG markup from the fixed icon set)
 *   image     → also "{name}_url"
 *
 * Plugins can add more with the "block.data" filter: fn (array $data, string $type) => $data.
 */
class BlockPresenter
{
    public function __construct(private PageBuilder $builder, private Hooks $hooks) {}

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array{id: string, type: string, anchor: ?string, data: array<string, mixed>}>
     */
    public function present(array $blocks): array
    {
        $definitions = PageBuilder::definitions();
        $out = [];

        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';

            if (! isset($definitions[$type]) || ! empty($block['hidden'])) {
                continue;
            }

            $definition = $definitions[$type];
            $data = $this->fields($definition['fields'], array_replace($definition['defaults'], (array) ($block['data'] ?? [])));

            if ($type === 'latest_posts') {
                $data['posts'] = Post::published()->with('category')->latest('published_at')->take((int) $data['count'])->get()
                    ->map(fn (Post $post) => PostPresenter::card($post))->all();
            }

            $out[] = [
                'id' => (string) ($block['id'] ?? Str::random(8)),
                'type' => $type,
                'anchor' => ($data['anchor'] ?? '') !== '' ? Str::slug($data['anchor']) : null,
                'data' => $this->hooks->apply('block.data', $data, $type),
            ];
        }

        return $out;
    }

    /** @param array<int, array<string, mixed>> $fields */
    private function fields(array $fields, array $data): array
    {
        foreach ($fields as $field) {
            $name = $field['name'];
            $value = $data[$name] ?? null;

            switch ($field['type']) {
                case 'url':
                    $data[$name] = $this->builder->url(is_string($value) ? $value : '');
                    break;
                case 'markdown':
                    $data[$name.'_html'] = Markdown::toHtml(is_string($value) ? $value : '');
                    break;
                case 'textarea':
                    $data[$name.'_html'] = is_string($value) && $value !== ''
                        ? Str::inlineMarkdown($value, ['html_input' => 'strip', 'allow_unsafe_links' => false])
                        : '';
                    break;
                case 'icon':
                    $data[$name.'_svg'] = Icons::svg(is_string($value) ? $value : null);
                    break;
                case 'image':
                    $data[$name.'_url'] = is_string($value) && $value !== '' ? Storage::disk('public')->url($value) : null;
                    break;
                case 'repeater':
                    $data[$name] = array_map(
                        fn ($row) => $this->fields($field['fields'], is_array($row) ? $row : []),
                        is_array($value) ? array_values($value) : [],
                    );
                    break;
            }
        }

        return $data;
    }
}
