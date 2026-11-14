<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema;

use App\Cms\Builder\Exceptions\InvalidDocumentException;
use Illuminate\Support\Str;

/**
 * Structural gate for anything written into `pages.document`.
 *
 * It checks the Section → Row → Column → Block shape, that every node carries a
 * ULID, that column spans are sane, and — the important one — that every block
 * type exists in the BlockRegistry and every nested block is one its parent is
 * allowed to hold. An unknown block type is a hard rejection: the renderer has
 * no way to draw it, so it must never reach storage.
 */
final class DocumentValidator
{
    private const MAX_DEPTH = 8;

    /** @var list<string> */
    private array $errors = [];

    /**
     * @param  array<string, mixed>  $document
     * @return list<string> Empty when the document is valid.
     */
    public function errorsFor(array $document): array
    {
        $this->errors = [];

        $schemaVersion = $document['schema_version'] ?? $document['schemaVersion'] ?? null;

        if (! is_int($schemaVersion) && ! ctype_digit((string) $schemaVersion)) {
            $this->fail('document', 'schema_version must be an integer.');
        } elseif ((int) $schemaVersion < 1 || (int) $schemaVersion > DocumentUpgrader::CURRENT_VERSION) {
            $this->fail('document', sprintf(
                'schema_version %s is not supported (this build understands 1 to %d).',
                (string) $schemaVersion,
                DocumentUpgrader::CURRENT_VERSION,
            ));
        }

        if (array_key_exists('settings', $document) && ! is_array($document['settings'])) {
            $this->fail('document.settings', 'settings must be an object.');
        }

        if (! array_key_exists('sections', $document) || ! is_array($document['sections'])) {
            $this->fail('document', 'sections must be an array.');

            return $this->errors;
        }

        $seen = [];

        foreach (array_values($document['sections']) as $index => $section) {
            $this->validateSection($section, "sections.{$index}", $seen);
        }

        return $this->errors;
    }

    /**
     * @param  array<string, mixed>  $document
     */
    public function passes(array $document): bool
    {
        return $this->errorsFor($document) === [];
    }

    /**
     * @param  array<string, mixed>  $document
     *
     * @throws InvalidDocumentException
     */
    public function validate(array $document): void
    {
        $errors = $this->errorsFor($document);

        if ($errors !== []) {
            throw new InvalidDocumentException($errors);
        }
    }

    /**
     * @param  array<string, bool>  $seen
     */
    private function validateSection(mixed $section, string $path, array &$seen): void
    {
        if (! is_array($section)) {
            $this->fail($path, 'a section must be an object.');

            return;
        }

        $this->validateId($section, $path, $seen);

        $width = $section['width'] ?? 'boxed';

        if (! in_array($width, ['boxed', 'full'], strict: true)) {
            $this->fail($path.'.width', 'width must be "boxed" or "full".');
        }

        $this->validateVisibility($section['visibility'] ?? null, $path);
        $this->validateStyle($section['style'] ?? null, $path);

        if (! isset($section['rows']) || ! is_array($section['rows'])) {
            $this->fail($path.'.rows', 'rows must be an array.');

            return;
        }

        foreach (array_values($section['rows']) as $index => $row) {
            $this->validateRow($row, $path.".rows.{$index}", $seen);
        }
    }

    /**
     * @param  array<string, bool>  $seen
     */
    private function validateRow(mixed $row, string $path, array &$seen): void
    {
        if (! is_array($row)) {
            $this->fail($path, 'a row must be an object.');

            return;
        }

        $this->validateId($row, $path, $seen);
        $this->validateVisibility($row['visibility'] ?? null, $path);
        $this->validateStyle($row['style'] ?? null, $path);

        if (! isset($row['columns']) || ! is_array($row['columns'])) {
            $this->fail($path.'.columns', 'columns must be an array.');

            return;
        }

        foreach (array_values($row['columns']) as $index => $column) {
            $this->validateColumn($column, $path.".columns.{$index}", $seen);
        }
    }

    /**
     * @param  array<string, bool>  $seen
     */
    private function validateColumn(mixed $column, string $path, array &$seen): void
    {
        if (! is_array($column)) {
            $this->fail($path, 'a column must be an object.');

            return;
        }

        $this->validateId($column, $path, $seen);
        $this->validateVisibility($column['visibility'] ?? null, $path);
        $this->validateStyle($column['style'] ?? null, $path);

        $span = $column['span'] ?? null;

        if ($span !== null) {
            $spans = is_array($span) ? $span : ['desktop' => $span];

            foreach ($spans as $breakpoint => $value) {
                // A null override means "inherit through the cascade", which is
                // exactly what ColumnSpanData serialises for an unset tablet or
                // mobile span. Absent and explicitly-null must mean the same
                // thing, or a document could not be saved back unchanged.
                if ($value === null) {
                    continue;
                }

                if (! is_int($value) || $value < 1 || $value > 12) {
                    $this->fail($path.'.span.'.$breakpoint, 'a span must be an integer from 1 to 12.');
                }
            }
        }

        if (! isset($column['blocks']) || ! is_array($column['blocks'])) {
            $this->fail($path.'.blocks', 'blocks must be an array.');

            return;
        }

        foreach (array_values($column['blocks']) as $index => $block) {
            $this->validateBlock($block, $path.".blocks.{$index}", $seen, null, 1);
        }
    }

    /**
     * @param  array<string, bool>  $seen
     */
    private function validateBlock(
        mixed $block,
        string $path,
        array &$seen,
        ?BlockType $parent,
        int $depth,
    ): void {
        if (! is_array($block)) {
            $this->fail($path, 'a block must be an object.');

            return;
        }

        if ($depth > self::MAX_DEPTH) {
            $this->fail($path, sprintf('blocks may not nest deeper than %d levels.', self::MAX_DEPTH));

            return;
        }

        $this->validateId($block, $path, $seen);

        $rawType = $block['type'] ?? null;

        if (! is_string($rawType) || $rawType === '') {
            $this->fail($path.'.type', 'a block must declare a type.');

            return;
        }

        $type = BlockType::tryFrom($rawType);

        if ($type === null || ! BlockRegistry::has($rawType)) {
            $this->fail($path.'.type', sprintf('"%s" is not a known block type.', $rawType));

            return;
        }

        if ($parent !== null && ! BlockRegistry::accepts($parent, $type)) {
            $this->fail($path, sprintf(
                'a "%s" block may not contain a "%s" block.',
                $parent->value,
                $type->value,
            ));
        }

        if (array_key_exists('props', $block) && ! is_array($block['props'])) {
            $this->fail($path.'.props', 'props must be an object.');
        }

        $this->validateVisibility($block['visibility'] ?? null, $path);
        $this->validateStyle($block['style'] ?? null, $path);

        $children = $block['children'] ?? [];

        if ($children === [] || $children === null) {
            return;
        }

        if (! is_array($children)) {
            $this->fail($path.'.children', 'children must be an array.');

            return;
        }

        if (! BlockRegistry::isContainer($type)) {
            $this->fail($path.'.children', sprintf(
                'a "%s" block is a leaf and cannot hold children.',
                $type->value,
            ));

            return;
        }

        $max = BlockRegistry::maxChildren($type);

        if ($max !== null && count($children) > $max) {
            $this->fail($path.'.children', sprintf(
                'a "%s" block holds at most %d children.',
                $type->value,
                $max,
            ));
        }

        foreach (array_values($children) as $index => $child) {
            $this->validateBlock($child, $path.".children.{$index}", $seen, $type, $depth + 1);
        }
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, bool>  $seen
     */
    private function validateId(array $node, string $path, array &$seen): void
    {
        $id = $node['id'] ?? null;

        if (! is_string($id) || ! Str::isUlid($id)) {
            $this->fail($path.'.id', 'every node needs a ULID id.');

            return;
        }

        if (isset($seen[$id])) {
            $this->fail($path.'.id', sprintf('id "%s" appears more than once in this document.', $id));

            return;
        }

        $seen[$id] = true;
    }

    private function validateVisibility(mixed $visibility, string $path): void
    {
        if ($visibility === null) {
            return;
        }

        if (! is_array($visibility)) {
            $this->fail($path.'.visibility', 'visibility must be an object.');

            return;
        }

        foreach ($visibility as $breakpoint => $value) {
            // Audience rules (§63) sit beside the breakpoints; VisibilityData
            // sanitises them, so only their shape is checked here.
            if ($breakpoint === 'audience') {
                if ($value !== null && (! is_array($value) || ! is_array($value['rules'] ?? null))) {
                    $this->fail($path.'.visibility.audience', 'audience must be null or an object with rules.');
                }

                continue;
            }

            if (! in_array($breakpoint, ['desktop', 'tablet', 'mobile'], strict: true)) {
                $this->fail($path.'.visibility', sprintf('"%s" is not a breakpoint.', (string) $breakpoint));
            }

            if (! is_bool($value)) {
                $this->fail($path.'.visibility.'.$breakpoint, 'visibility must be a boolean.');
            }
        }
    }

    private function validateStyle(mixed $style, string $path): void
    {
        if ($style === null || $style === []) {
            return;
        }

        if (! is_array($style)) {
            $this->fail($path.'.style', 'style must be an object.');

            return;
        }

        foreach ($style as $property => $value) {
            // Null is how StyleData serialises "not set on this node"; it
            // carries no meaning to check.
            if ($value === null) {
                continue;
            }

            if ($property === 'custom') {
                if (! is_array($value)) {
                    $this->fail($path.'.style.custom', 'custom must be an object.');
                }

                continue;
            }

            if (! is_array($value)) {
                // A bare scalar is shorthand for the desktop value.
                continue;
            }

            foreach (array_keys($value) as $key) {
                if (is_string($key) && in_array($key, ['desktop', 'tablet', 'mobile'], strict: true)) {
                    continue 2;
                }
            }
            // Anything else (a spacing box, a border box) is a structured
            // desktop-only value, which is allowed.
        }
    }

    private function fail(string $path, string $message): void
    {
        $this->errors[] = $path.': '.$message;
    }
}
