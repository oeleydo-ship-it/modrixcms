<?php

namespace App\Cms;

use InvalidArgumentException;

/**
 * Page-builder blocks added by plugins. Core blocks live in App\PageBuilder\PageBuilder.
 */
class BlockRegistry
{
    public const FIELD_TYPES = ['text', 'textarea', 'markdown', 'url', 'number', 'select', 'toggle', 'list', 'repeater', 'icon', 'image'];

    /** @var array<string, array<string, mixed>> */
    private array $blocks = [];

    /**
     * Declares a block's fields and defaults (used by the editor and to clean saved data).
     * The React component that draws it is registered by the plugin's script under the same type.
     *
     * @param  string  $type  "vendor/package:block"
     * @param  array{label: string, description?: string, fields: array, defaults?: array}  $definition
     */
    public function register(string $type, array $definition): void
    {
        if (! preg_match('/^[a-z0-9-]+\/[a-z0-9-]+:[a-z0-9_-]{1,40}$/', $type)) {
            throw new InvalidArgumentException("Block type \"{$type}\" must look like vendor/package:block.");
        }

        if (! is_string($definition['label'] ?? null) || ! is_array($definition['fields'] ?? null)) {
            throw new InvalidArgumentException("Block \"{$type}\" needs a label and fields.");
        }

        $this->checkFields($type, $definition['fields']);

        $this->blocks[$type] = [
            'label' => $definition['label'],
            'description' => $definition['description'] ?? '',
            'fields' => $definition['fields'],
            'defaults' => $definition['defaults'] ?? [],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return $this->blocks;
    }

    private function checkFields(string $type, array $fields): void
    {
        foreach ($fields as $field) {
            if (! is_string($field['name'] ?? null) || ! in_array($field['type'] ?? null, self::FIELD_TYPES, true)) {
                throw new InvalidArgumentException("Block \"{$type}\": every field needs a name and one of the types ".implode(', ', self::FIELD_TYPES).'.');
            }

            if ($field['type'] === 'select' && ! is_array($field['options'] ?? null)) {
                throw new InvalidArgumentException("Block \"{$type}\": select field \"{$field['name']}\" needs options.");
            }

            if ($field['type'] === 'repeater') {
                $this->checkFields($type, $field['fields'] ?? []);
            }
        }
    }
}
