<?php

namespace Modrix\FormBuilder\Http\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modrix\FormBuilder\FormSchema;
use Modrix\FormBuilder\Models\Form;
use Modrix\FormBuilder\Models\Submission;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionsController
{
    public function index(Request $request): Response
    {
        $formId = $request->query('form');
        $status = $request->query('status');
        $search = $request->query('q');

        $submissions = Submission::query()
            ->with('form:id,name,slug,fields')
            ->when($formId, fn ($q) => $q->where('form_id', $formId))
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->where('data', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(function (Submission $sub): array {
                $preview = collect($sub->data ?? [])
                    ->take(3)
                    ->map(fn ($value, $key) => $key.': '.(is_array($value) ? implode(', ', $value) : (string) $value))
                    ->implode(' · ');

                return [
                    'id' => $sub->id,
                    'form_id' => $sub->form_id,
                    'form_name' => $sub->form?->name ?? 'Deleted form',
                    'status' => $sub->status,
                    'preview' => $preview,
                    'page_url' => $sub->page_url,
                    'created' => $sub->created_at?->diffForHumans(),
                    'created_at' => $sub->created_at?->toDayDateTimeString(),
                ];
            });

        $counts = [
            'all' => Submission::query()->count(),
            'new' => Submission::query()->where('status', 'new')->count(),
            'read' => Submission::query()->where('status', 'read')->count(),
            'archived' => Submission::query()->where('status', 'archived')->count(),
            'spam' => Submission::query()->where('status', 'spam')->count(),
        ];

        return Inertia::render('plugin:modrix/form-builder/SubmissionsIndex', [
            'submissions' => $submissions,
            'forms' => Form::query()->orderBy('name')->get(['id', 'name']),
            'counts' => $counts,
            'filters' => [
                'form' => $formId ? (string) $formId : '',
                'status' => $status ?: 'all',
                'q' => $search ?: '',
            ],
        ]);
    }

    public function show(Submission $submission): Response
    {
        if ($submission->status === 'new') {
            $submission->update(['status' => 'read']);
        }

        $submission->load('form:id,name,slug,fields');
        $fieldMap = collect($submission->form?->fields ?? [])->keyBy('key');

        $entries = collect($submission->data ?? [])->map(function ($value, $key) use ($fieldMap): array {
            $field = $fieldMap->get($key);

            return [
                'key' => $key,
                'label' => $field['label'] ?? $key,
                'type' => $field['type'] ?? 'text',
                'value' => is_array($value) ? implode(', ', $value) : (is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value),
            ];
        })->values()->all();

        return Inertia::render('plugin:modrix/form-builder/SubmissionShow', [
            'submission' => [
                'id' => $submission->id,
                'form_id' => $submission->form_id,
                'form_name' => $submission->form?->name ?? 'Deleted form',
                'status' => $submission->status,
                'notes' => $submission->notes ?? '',
                'page_url' => $submission->page_url,
                'ip_address' => $submission->ip_address,
                'user_agent' => $submission->user_agent,
                'created_at' => $submission->created_at?->toDayDateTimeString(),
                'entries' => $entries,
            ],
        ]);
    }

    public function update(Request $request, Submission $submission): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,read,archived,spam'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $submission->update($data);

        return back()->with('status', 'Submission updated.');
    }

    public function destroy(Submission $submission): RedirectResponse
    {
        $submission->delete();

        return redirect()->route('plugin.modrix.form-builder.submissions.index')
            ->with('status', 'Submission deleted.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
            'action' => ['required', 'in:new,read,archived,spam,delete'],
        ]);

        $query = Submission::query()->whereIn('id', $data['ids']);

        if ($data['action'] === 'delete') {
            $deleted = $query->delete();

            return back()->with('status', "Deleted {$deleted} submission(s).");
        }

        $updated = $query->update(['status' => $data['action']]);

        return back()->with('status', "Updated {$updated} submission(s) to {$data['action']}.");
    }

    public function export(Request $request): StreamedResponse
    {
        $formId = $request->query('form');
        $status = $request->query('status');
        $form = $formId ? Form::query()->find($formId) : null;

        $rows = Submission::query()
            ->with('form:id,name')
            ->when($formId, fn ($q) => $q->where('form_id', $formId))
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->get();

        $keys = [];

        if ($form !== null) {
            foreach ($form->fields ?? [] as $field) {
                if (! in_array($field['type'] ?? '', FormSchema::NON_INPUT_TYPES, true)) {
                    $keys[] = $field['key'];
                }
            }
        } else {
            foreach ($rows as $row) {
                foreach (array_keys($row->data ?? []) as $key) {
                    if (! in_array($key, $keys, true)) {
                        $keys[] = $key;
                    }
                }
            }
        }

        $filename = ($form ? $form->slug : 'all-forms').'-submissions.csv';

        return response()->streamDownload(function () use ($rows, $keys): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, array_merge(['ID', 'Form', 'Status', 'Submitted At', 'Page URL'], $keys));

            foreach ($rows as $row) {
                $data = $row->data ?? [];
                $cells = [
                    $row->id,
                    $this->safeCsvCell($row->form?->name ?? ''),
                    $row->status,
                    $row->created_at?->toIso8601String() ?? '',
                    $this->safeCsvCell((string) ($row->page_url ?? '')),
                ];

                foreach ($keys as $key) {
                    $val = $data[$key] ?? '';
                    $formatted = is_array($val) ? implode('; ', $val) : (is_bool($val) ? ($val ? 'Yes' : 'No') : (string) $val);
                    $cells[] = $this->safeCsvCell($formatted);
                }

                fputcsv($handle, $cells);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safeCsvCell(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
