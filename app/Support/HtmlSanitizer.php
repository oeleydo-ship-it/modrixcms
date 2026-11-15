<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Cleans the HTML the rich text editor produces: only a fixed set of formatting tags and
 * attributes survive, scripts, styles, event handlers and unsafe links are dropped.
 */
class HtmlSanitizer
{
    /** @var array<string, list<string>> tag => allowed attributes */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'del' => [],
        'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'code' => [], 'pre' => [], 'sup' => [], 'sub' => [], 'mark' => [],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'figure' => [], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
    ];

    /** Tags removed together with everything inside them. */
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'noscript', 'template', 'svg', 'math'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?><div id="sanitize-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('sanitize-root');

        if (! $root instanceof DOMElement) {
            return '';
        }

        self::cleanChildren($root);

        $out = '';

        foreach ($root->childNodes as $child) {
            $out .= $document->saveHTML($child);
        }

        return trim($out);
    }

    private static function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                if ($child->nodeType !== XML_TEXT_NODE) {
                    $parent->removeChild($child);
                }

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($child);

                continue;
            }

            self::cleanChildren($child);

            if (! array_key_exists($tag, self::ALLOWED)) {
                // An unknown wrapper (div, span, font…) keeps its content but loses the tag.
                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attribute) {
                $name = strtolower($attribute->name);

                if (! in_array($name, self::ALLOWED[$tag], true) || ! self::safeValue($name, $attribute->value)) {
                    $child->removeAttribute($attribute->name);
                }
            }

            if ($tag === 'a') {
                if ($child->getAttribute('target') === '_blank') {
                    $child->setAttribute('rel', 'noopener noreferrer');
                } else {
                    $child->removeAttribute('target');
                }
            }

            if ($tag === 'img' && ! $child->hasAttribute('src')) {
                $parent->removeChild($child);
            }
        }
    }

    private static function safeValue(string $name, string $value): bool
    {
        if ($name !== 'href' && $name !== 'src') {
            return true;
        }

        $value = strtolower(preg_replace('/[\s\x00-\x1f]+/', '', $value) ?? '');

        if ($value === '' || str_starts_with($value, '/') || str_starts_with($value, '#') || ! str_contains(explode('?', $value)[0], ':')) {
            return true;
        }

        $schemes = $name === 'href' ? ['http:', 'https:', 'mailto:', 'tel:'] : ['http:', 'https:'];

        foreach ($schemes as $scheme) {
            if (str_starts_with($value, $scheme)) {
                return true;
            }
        }

        return false;
    }
}
