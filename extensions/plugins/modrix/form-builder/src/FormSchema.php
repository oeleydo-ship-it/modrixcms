<?php

namespace Modrix\FormBuilder;

use App\Cms\Cms;
use Illuminate\Support\Str;

/**
 * Normalizes form builder schemas, evaluates conditional field logic,
 * and builds server-side Laravel validation rules for dynamic forms.
 */
class FormSchema
{
    public const FIELD_TYPES = [
        'text',
        'email',
        'phone',
        'textarea',
        'number',
        'select',
        'radio',
        'checkbox',
        'checkbox_group',
        'consent',
        'date',
        'time',
        'url',
        'rating',
        'hidden',
        'heading',
        'paragraph',
        'divider',
    ];

    public const CONDITION_OPERATORS = [
        'equals',
        'not_equals',
        'contains',
        'not_empty',
        'is_empty',
        'greater_than',
        'less_than',
    ];

    public const NON_INPUT_TYPES = ['heading', 'paragraph', 'divider'];

    /**
     * Normalize and sanitize an array of form field definitions.
     *
     * @param  array<int, mixed>  $fields
     * @return array<int, array<string, mixed>>
     */
    public static function cleanFields(array $fields): array
    {
        $clean = [];
        $usedKeys = [];

        foreach (array_slice(array_values($fields), 0, 80) as $index => $raw) {
            if (! is_array($raw)) {
                continue;
            }

            $type = in_array($raw['type'] ?? '', self::FIELD_TYPES, true) ? $raw['type'] : 'text';
            $defaultLabel = $type === 'divider' ? 'Divider' : 'Field '.($index + 1);
            $label = Str::limit(trim((string) ($raw['label'] ?? 'Untitled field')), 160, '');

            if ($label === '') {
                $label = $defaultLabel;
            }

            $baseKey = Str::slug((string) ($raw['key'] ?? $label), '_');
            $baseKey = preg_replace('/[^a-z0-9_]/', '', $baseKey) ?: 'field_'.($index + 1);

            if (is_numeric($baseKey[0] ?? '')) {
                $baseKey = 'f_'.$baseKey;
            }

            $key = Str::limit($baseKey, 60, '');
            $suffix = 2;

            while (isset($usedKeys[$key])) {
                $key = Str::limit($baseKey, 55, '').'_'.$suffix;
                $suffix++;
            }

            $usedKeys[$key] = true;

            $options = [];

            if (in_array($type, ['select', 'radio', 'checkbox_group'], true)) {
                foreach (array_slice(is_array($raw['options'] ?? null) ? $raw['options'] : [], 0, 50) as $option) {
                    $text = Str::limit(trim((string) $option), 160, '');

                    if ($text !== '') {
                        $options[] = $text;
                    }
                }

                if ($options === []) {
                    $options = ['Option 1', 'Option 2'];
                }
            }

            $condition = null;

            if (is_array($raw['condition'] ?? null) && ! empty($raw['condition']['field'])) {
                $operator = in_array($raw['condition']['operator'] ?? '', self::CONDITION_OPERATORS, true)
                    ? $raw['condition']['operator']
                    : 'equals';

                $condition = [
                    'field' => Str::limit(preg_replace('/[^a-z0-9_]/', '', (string) $raw['condition']['field']), 60, ''),
                    'operator' => $operator,
                    'value' => Str::limit((string) ($raw['condition']['value'] ?? ''), 160, ''),
                ];
            }

            $width = self::pickOption($raw['width'] ?? null, ['full', 'half', 'third'], 'full');

            $minLength = isset($raw['min_length']) && is_numeric($raw['min_length'])
                ? max(0, min(5000, (int) $raw['min_length']))
                : null;
            $maxLength = isset($raw['max_length']) && is_numeric($raw['max_length'])
                ? max(1, min(10000, (int) $raw['max_length']))
                : null;

            if ($minLength !== null && $maxLength !== null && $minLength > $maxLength) {
                $maxLength = $minLength;
            }

            $minDate = isset($raw['min_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $raw['min_date'])
                ? (string) $raw['min_date']
                : '';
            $maxDate = isset($raw['max_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $raw['max_date'])
                ? (string) $raw['max_date']
                : '';

            $rows = isset($raw['rows']) && is_numeric($raw['rows'])
                ? max(2, min(12, (int) $raw['rows']))
                : 4;

            $maxStars = isset($raw['max_stars']) && is_numeric($raw['max_stars'])
                ? max(3, min(10, (int) $raw['max_stars']))
                : 5;

            $clean[] = [
                'id' => preg_match('/^[a-z0-9_-]{1,32}$/i', (string) ($raw['id'] ?? ''))
                    ? (string) $raw['id']
                    : Str::lower(Str::random(8)),
                'type' => $type,
                'key' => $key,
                'label' => $label,
                'placeholder' => Str::limit(trim((string) ($raw['placeholder'] ?? '')), 200, ''),
                'help' => Str::limit(trim((string) ($raw['help'] ?? '')), 500, ''),
                'required' => ! in_array($type, [...self::NON_INPUT_TYPES, 'hidden'], true)
                    && filter_var($raw['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'width' => $width,
                'default_value' => Str::limit((string) ($raw['default_value'] ?? ''), 300, ''),
                'options' => $options,
                'min' => isset($raw['min']) && is_numeric($raw['min']) ? (int) $raw['min'] : null,
                'max' => isset($raw['max']) && is_numeric($raw['max']) ? (int) $raw['max'] : null,
                'min_length' => $minLength,
                'max_length' => $maxLength,
                'min_date' => $minDate,
                'max_date' => $maxDate,
                'rows' => $rows,
                'max_stars' => $maxStars,
                'condition' => $condition,
            ];
        }

        return $clean;
    }

    /**
     * Normalize form settings with plugin defaults.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public static function cleanSettings(array $settings): array
    {
        $redirectUrl = trim((string) ($settings['redirect_url'] ?? ''));

        if ($redirectUrl !== '' && ! preg_match('~^(https?://|/(?!/))~i', $redirectUrl)) {
            $redirectUrl = '';
        }

        $notifyEmail = trim((string) ($settings['notify_email'] ?? ''));

        if ($notifyEmail !== '' && ! filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) {
            $notifyEmail = '';
        }

        // Empty: the form takes the active theme's colours. A colour here is the form's own choice.
        $accentColor = trim((string) ($settings['accent_color'] ?? ''));

        if (! preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $accentColor)) {
            $accentColor = '';
        }

        $cardStyle = self::pickOption($settings['card_style'] ?? null, ['card', 'bordered', 'flat'], 'card');
        $borderRadius = self::pickOption($settings['border_radius'] ?? null, ['sharp', 'rounded', 'pill'], 'rounded');
        $spacing = self::pickOption($settings['spacing'] ?? null, ['compact', 'comfortable', 'spacious'], 'comfortable');
        $buttonAlign = self::pickOption($settings['button_align'] ?? null, ['left', 'full', 'right'], 'left');

        return [
            'submit_label' => Str::limit(trim((string) ($settings['submit_label'] ?? '')), 80, '')
                ?: (string) Cms::setting('modrix/form-builder', 'default_submit_label', 'Submit'),
            'success_message' => Str::limit(trim((string) ($settings['success_message'] ?? '')), 500, '')
                ?: (string) Cms::setting('modrix/form-builder', 'default_success_message', 'Thank you! Your submission has been received.'),
            'redirect_url' => Str::limit($redirectUrl, 500, ''),
            'notify_email' => Str::limit($notifyEmail, 200, ''),
            'store_submissions' => array_key_exists('store_submissions', $settings)
                ? filter_var($settings['store_submissions'], FILTER_VALIDATE_BOOLEAN)
                : true,
            'honeypot' => array_key_exists('honeypot', $settings)
                ? filter_var($settings['honeypot'], FILTER_VALIDATE_BOOLEAN)
                : true,
            'accent_color' => $accentColor,
            'card_style' => $cardStyle,
            'border_radius' => $borderRadius,
            'spacing' => $spacing,
            'button_align' => $buttonAlign,
            'show_required_note' => filter_var($settings['show_required_note'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Determine whether a field should be active given submitted values.
     *
     * @param  array<string, mixed>  $field
     * @param  array<string, mixed>  $input
     */
    public static function isFieldVisible(array $field, array $input): bool
    {
        $condition = $field['condition'] ?? null;

        if (! is_array($condition) || empty($condition['field'])) {
            return true;
        }

        $targetValue = $input[$condition['field']] ?? '';
        $expected = (string) ($condition['value'] ?? '');
        $operator = $condition['operator'] ?? 'equals';

        if (is_array($targetValue)) {
            return match ($operator) {
                'not_empty' => $targetValue !== [],
                'is_empty' => $targetValue === [],
                'not_equals' => ! in_array($expected, $targetValue, true),
                default => in_array($expected, $targetValue, true),
            };
        }

        $actual = is_bool($targetValue) ? ($targetValue ? '1' : '0') : (string) $targetValue;

        return match ($operator) {
            'not_equals' => $actual !== $expected,
            'contains' => $expected !== '' && str_contains(mb_strtolower($actual), mb_strtolower($expected)),
            'not_empty' => trim($actual) !== '',
            'is_empty' => trim($actual) === '',
            'greater_than' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            'less_than' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            default => $actual === $expected,
        };
    }

    /**
     * Build Laravel validation rules and readable attributes for submitted form data.
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $input
     * @return array{rules: array<string, array<int, mixed>>, attributes: array<string, string>, visibleKeys: array<int, string>}
     */
    public static function validationPlan(array $fields, array $input): array
    {
        $rules = [];
        $attributes = [];
        $visibleKeys = [];

        foreach ($fields as $field) {
            if (in_array($field['type'], self::NON_INPUT_TYPES, true)) {
                continue;
            }

            $key = $field['key'];

            if (! self::isFieldVisible($field, $input)) {
                continue;
            }

            $visibleKeys[] = $key;
            $attributes[$key] = $field['label'];
            $required = ! empty($field['required']);

            if ($field['type'] === 'checkbox' || $field['type'] === 'consent') {
                $rules[$key] = $required ? ['accepted'] : ['nullable', 'boolean'];

                continue;
            }

            if ($field['type'] === 'checkbox_group') {
                $rules[$key] = $required ? ['required', 'array', 'min:1'] : ['nullable', 'array'];
                $rules["{$key}.*"] = ['string', 'in:'.implode(',', $field['options'] ?? [])];

                continue;
            }

            $fieldRules = [$required ? 'required' : 'nullable'];
            $minLength = isset($field['min_length']) && is_numeric($field['min_length']) ? (int) $field['min_length'] : null;
            $maxLength = isset($field['max_length']) && is_numeric($field['max_length']) ? (int) $field['max_length'] : null;

            match ($field['type']) {
                'email' => array_push($fieldRules, 'email', 'max:200'),
                'url' => array_push(
                    $fieldRules,
                    'url:http,https',
                    'max:'.min(500, $maxLength ?? 500),
                ),
                'phone' => array_push(
                    $fieldRules,
                    'string',
                    'max:'.min(40, $maxLength ?? 40),
                    'regex:/^[0-9+\-\s().]{5,40}$/',
                    ...($minLength !== null ? ['min:'.min(40, $minLength)] : []),
                ),
                'date' => array_push(
                    $fieldRules,
                    'date',
                    ...(! empty($field['min_date']) ? ['after_or_equal:'.$field['min_date']] : []),
                    ...(! empty($field['max_date']) ? ['before_or_equal:'.$field['max_date']] : []),
                ),
                'time' => array_push($fieldRules, 'regex:/^\d{2}:\d{2}(:\d{2})?$/'),
                'rating' => array_push($fieldRules, 'integer', 'min:1', 'max:'.max(3, min(10, (int) ($field['max_stars'] ?? 5)))),
                'number' => array_push(
                    $fieldRules,
                    'numeric',
                    ...($field['min'] !== null ? ['min:'.$field['min']] : []),
                    ...($field['max'] !== null ? ['max:'.$field['max']] : []),
                ),
                'select', 'radio' => array_push($fieldRules, 'string', 'in:'.implode(',', $field['options'] ?? [])),
                'textarea' => array_push(
                    $fieldRules,
                    'string',
                    'max:'.min(10000, $maxLength ?? 10000),
                    ...($minLength !== null ? ['min:'.$minLength] : []),
                ),
                'hidden' => array_push($fieldRules, 'string', 'max:500'),
                default => array_push(
                    $fieldRules,
                    'string',
                    'max:'.min(500, $maxLength ?? 500),
                    ...($minLength !== null ? ['min:'.$minLength] : []),
                ),
            };

            $rules[$key] = $fieldRules;
        }

        return [
            'rules' => $rules,
            'attributes' => $attributes,
            'visibleKeys' => $visibleKeys,
        ];
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private static function pickOption(mixed $value, array $allowed, string $default): string
    {
        $candidate = is_string($value) ? $value : $default;

        return in_array($candidate, $allowed, true) ? $candidate : $default;
    }
}
