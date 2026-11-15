<?php

declare(strict_types=1);

namespace App\Http\Controllers\Builder;

use App\Cms\Cms;
use App\Http\Controllers\Controller;
use App\Models\Extension;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modrix\FormBuilder\FormSchema;
use Modrix\FormBuilder\Models\Form;
use Modrix\FormBuilder\Models\Submission;

/**
 * Exposes modrix/form-builder forms to the visual page builder's FormPicker,
 * FormFieldEditor, canvas runtime, and public capture submissions.
 */
class FormApiController extends Controller
{
    public function index(): JsonResponse
    {
        if (! $this->formsAvailable()) {
            return response()->json([
                'data' => [],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 0],
            ]);
        }

        $forms = Form::query()->orderBy('name')->get(['id', 'name', 'slug', 'status', 'fields']);

        return response()->json([
            'data' => $forms->map(fn (Form $form): array => [
                'id' => (string) $form->id,
                'name' => $form->name,
                'slug' => $form->slug,
                'is_active' => $form->status === 'active',
                'fields_count' => count($form->fields ?? []),
            ])->values()->all(),
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'total' => $forms->count(),
            ],
        ]);
    }

    public function show(string $form): JsonResponse
    {
        abort_unless($this->formsAvailable(), 404);

        $record = Form::query()->findOrFail($form);

        return response()->json(['data' => self::serializeForm($record)]);
    }

    public function storeField(Request $request, string $form): JsonResponse
    {
        abort_unless($this->formsAvailable(), 404);

        $record = Form::query()->findOrFail($form);
        $data = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', FormSchema::FIELD_TYPES)],
            'label' => ['required', 'string', 'max:160'],
            'name' => ['nullable', 'string', 'max:60'],
            'placeholder' => ['nullable', 'string', 'max:200'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'is_required' => ['boolean'],
            'width' => ['nullable', 'string', 'in:full,half,third'],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'max:160'],
        ]);

        $newId = Str::lower(Str::random(8));
        $fields = is_array($record->fields) ? $record->fields : [];
        $fields[] = [
            'id' => $newId,
            'type' => $data['type'],
            'key' => $data['name'] ?? $data['label'],
            'label' => $data['label'],
            'placeholder' => $data['placeholder'] ?? '',
            'help' => $data['help_text'] ?? '',
            'required' => (bool) ($data['is_required'] ?? false),
            'width' => $data['width'] ?? 'full',
            'options' => $data['options'] ?? [],
        ];

        $cleaned = FormSchema::cleanFields($fields);
        $record->update(['fields' => $cleaned]);

        $created = collect($cleaned)->firstWhere('id', $newId) ?? end($cleaned);

        return response()->json(['data' => self::serializeField($created)], 201);
    }

    public function updateField(Request $request, string $field): JsonResponse
    {
        abort_unless($this->formsAvailable(), 404);

        $data = $request->validate([
            'label' => ['sometimes', 'required', 'string', 'max:160'],
            'name' => ['sometimes', 'nullable', 'string', 'max:60'],
            'type' => ['sometimes', 'string', 'in:'.implode(',', FormSchema::FIELD_TYPES)],
            'placeholder' => ['sometimes', 'nullable', 'string', 'max:200'],
            'help_text' => ['sometimes', 'nullable', 'string', 'max:500'],
            'is_required' => ['sometimes', 'boolean'],
            'width' => ['sometimes', 'string', 'in:full,half,third'],
            'options' => ['sometimes', 'array'],
            'options.*' => ['string', 'max:160'],
            'move' => ['sometimes', 'string', 'in:up,down'],
        ]);

        [$record, $index] = $this->findFormWithField($field);
        $fields = is_array($record->fields) ? array_values($record->fields) : [];
        $current = $fields[$index];
        $oldKey = (string) ($current['key'] ?? '');

        if (array_key_exists('label', $data)) {
            $current['label'] = $data['label'];
        }
        if (array_key_exists('name', $data) && trim((string) $data['name']) !== '') {
            $current['key'] = $data['name'];
        }
        if (array_key_exists('type', $data)) {
            $current['type'] = $data['type'];
        }
        if (array_key_exists('placeholder', $data)) {
            $current['placeholder'] = $data['placeholder'] ?? '';
        }
        if (array_key_exists('help_text', $data)) {
            $current['help'] = $data['help_text'] ?? '';
        }
        if (array_key_exists('is_required', $data)) {
            $current['required'] = (bool) $data['is_required'];
        }
        if (array_key_exists('width', $data)) {
            $current['width'] = $data['width'];
        }
        if (array_key_exists('options', $data)) {
            $current['options'] = $data['options'];
        }

        $fields[$index] = $current;

        if (($data['move'] ?? null) === 'up' && $index > 0) {
            [$fields[$index - 1], $fields[$index]] = [$fields[$index], $fields[$index - 1]];
        } elseif (($data['move'] ?? null) === 'down' && $index < count($fields) - 1) {
            [$fields[$index + 1], $fields[$index]] = [$fields[$index], $fields[$index + 1]];
        }

        $cleaned = FormSchema::cleanFields($fields);
        $updated = collect($cleaned)->firstWhere('id', $field) ?? $current;
        $newKey = (string) ($updated['key'] ?? $oldKey);

        if ($oldKey !== '' && $newKey !== '' && $oldKey !== $newKey) {
            foreach ($cleaned as $idx => $item) {
                if (($item['condition']['field'] ?? null) === $oldKey) {
                    $cleaned[$idx]['condition']['field'] = $newKey;
                }
            }
        }

        $record->update(['fields' => $cleaned]);

        return response()->json(['data' => self::serializeField($updated)]);
    }

    public function destroyField(string $field): JsonResponse
    {
        abort_unless($this->formsAvailable(), 404);

        [$record, $index] = $this->findFormWithField($field);
        $fields = is_array($record->fields) ? array_values($record->fields) : [];
        array_splice($fields, $index, 1);

        $record->update(['fields' => FormSchema::cleanFields($fields)]);

        return response()->json(null, 204);
    }

    public function submit(Request $request, string $form): JsonResponse
    {
        abort_unless($this->formsAvailable(), 404);

        $record = Form::query()
            ->active()
            ->where('id', $form)
            ->orWhere(fn ($q) => $q->active()->where('slug', $form))
            ->firstOrFail();

        $settings = $record->settings ?? [];

        if (! empty($settings['honeypot']) && ($request->filled('_fb_trap') || $request->filled('_hp'))) {
            return response()->json([
                'message' => $settings['success_message'] ?? 'Thanks — we got it.',
            ]);
        }

        $fields = is_array($record->fields) ? $record->fields : [];
        $input = $request->except(['_token', '_fb_trap', '_hp', '_ts']);

        $fieldKeys = array_column($fields, 'key');
        $hasFormKeys = $fieldKeys !== [] && count(array_intersect(array_keys($input), $fieldKeys)) > 0;

        if ($hasFormKeys) {
            $plan = FormSchema::validationPlan($fields, $input);
            $validator = Validator::make($input, $plan['rules'], [], $plan['attributes']);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()->toArray(),
                ], 422);
            }

            $validated = $validator->validated();
            $payload = [];

            foreach ($plan['visibleKeys'] as $key) {
                if (array_key_exists($key, $validated)) {
                    $payload[$key] = $validated[$key];
                }
            }
        } else {
            $payload = [];

            foreach ($input as $key => $value) {
                if (is_string($key) && preg_match('/^[a-z0-9_]{1,60}$/i', $key) && is_scalar($value)) {
                    $payload[$key] = Str::limit(trim((string) $value), 1000, '');
                }
            }

            if ($payload === []) {
                return response()->json(['message' => 'Please fill in at least one field.'], 422);
            }
        }

        if ($settings['store_submissions'] ?? true) {
            $storeIp = (bool) Cms::setting('modrix/form-builder', 'store_ip', true);

            Submission::query()->create([
                'form_id' => $record->id,
                'data' => $payload,
                'status' => 'new',
                'page_url' => mb_substr((string) $request->headers->get('referer', ''), 0, 500),
                'ip_address' => $storeIp ? $request->ip() : null,
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 300),
            ]);

            $record->increment('submissions_count');
        }

        return response()->json([
            'message' => $settings['success_message'] ?? 'Thanks — we got it.',
            'redirect_url' => $settings['redirect_url'] ?? null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function serializeForm(Form $form): array
    {
        $fields = is_array($form->fields) ? $form->fields : [];

        return [
            'id' => (string) $form->id,
            'name' => $form->name,
            'slug' => $form->slug,
            'is_active' => $form->status === 'active',
            'honeypot' => '_fb_trap',
            'timing' => '_ts',
            'fields' => array_values(array_map([self::class, 'serializeField'], $fields)),
        ];
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    public static function serializeField(array $field): array
    {
        $width = (string) ($field['width'] ?? 'full');
        $columnSpan = match ($width) {
            'half' => 6,
            'third' => 4,
            default => 12,
        };

        return [
            'id' => (string) ($field['id'] ?? ''),
            'type' => (string) ($field['type'] ?? 'text'),
            'name' => (string) ($field['key'] ?? ''),
            'label' => (string) ($field['label'] ?? ''),
            'placeholder' => ($field['placeholder'] ?? '') !== '' ? (string) $field['placeholder'] : null,
            'help_text' => ($field['help'] ?? '') !== '' ? (string) $field['help'] : null,
            'is_required' => (bool) ($field['required'] ?? false),
            'width' => $width,
            'column_span' => $columnSpan,
            'options' => is_array($field['options'] ?? null) ? array_values($field['options']) : [],
            'default_value' => ($field['default_value'] ?? '') !== '' ? (string) $field['default_value'] : null,
            'condition' => $field['condition'] ?? null,
        ];
    }

    public static function isPluginEnabled(): bool
    {
        return class_exists(Form::class)
            && Schema::hasTable('fb_forms')
            && Extension::query()->where('name', 'modrix/form-builder')->where('enabled', true)->exists();
    }

    private function formsAvailable(): bool
    {
        return self::isPluginEnabled();
    }

    /**
     * @return array{0: Form, 1: int}
     */
    private function findFormWithField(string $fieldId): array
    {
        foreach (Form::query()->get() as $record) {
            foreach (array_values($record->fields ?? []) as $index => $field) {
                if ((string) ($field['id'] ?? '') === $fieldId) {
                    return [$record, $index];
                }
            }
        }

        abort(404);
    }
}
