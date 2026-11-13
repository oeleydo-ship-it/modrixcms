<?php

namespace App\Cms\Extensions;

use Illuminate\Support\Str;

/**
 * The "settings" list of a manifest: defaults, cleaning of submitted values, and validation rules.
 */
class SettingsSchema
{
    /** @param array<int, array<string, mixed>> $fields */
    public static function defaults(array $fields): array
    {
        $values = [];

        foreach ($fields as $field) {
            $values[$field['name']] = self::clean($field, $field['default'] ?? null);
        }

        return $values;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, array<int, string>>
     */
    public static function rules(array $fields): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $rules[$field['name']] = match ($field['type']) {
                'email' => ['nullable', 'email', 'max:200'],
                'url' => ['nullable', 'url:http,https', 'max:500'],
                'number' => ['nullable', 'numeric'],
                'toggle' => ['nullable', 'boolean'],
                'select' => ['nullable', 'in:'.implode(',', array_map('strval', array_keys($field['options'])))],
                'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'textarea' => ['nullable', 'string', 'max:5000'],
                default => ['nullable', 'string', 'max:300'],
            };
        }

        return $rules;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $input
     */
    public static function cleanAll(array $fields, array $input): array
    {
        $values = [];

        foreach ($fields as $field) {
            $values[$field['name']] = self::clean($field, $input[$field['name']] ?? null);
        }

        return $values;
    }

    /** @param array<string, mixed> $field */
    public static function clean(array $field, mixed $value): mixed
    {
        $string = fn (int $max) => is_scalar($value) ? Str::limit(trim((string) $value), $max, '') : '';

        return match ($field['type']) {
            'toggle' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($value)
                ? max($field['min'] ?? PHP_INT_MIN, min($field['max'] ?? PHP_INT_MAX, $value + 0))
                : ($field['default'] ?? 0),
            'select' => is_scalar($value) && array_key_exists((string) $value, $field['options'])
                ? (string) $value
                : (string) array_key_first($field['options']),
            'color' => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : ($field['default'] ?? '#000000'),
            'url' => preg_match('~^https?://~i', $string(500)) ? $string(500) : '',
            'email' => filter_var($string(200), FILTER_VALIDATE_EMAIL) ? $string(200) : '',
            'textarea' => $string(5000),
            default => $string(300),
        };
    }
}
