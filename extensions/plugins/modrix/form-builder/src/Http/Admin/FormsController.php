<?php

namespace Modrix\FormBuilder\Http\Admin;

use App\Cms\Cms;
use App\Support\Slugs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modrix\FormBuilder\FormSchema;
use Modrix\FormBuilder\Models\Form;

class FormsController
{
    public function index(Request $request): Response
    {
        $forms = Form::query()
            ->when($request->query('q'), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->withCount(['submissions as unread_count' => fn ($q) => $q->where('status', 'new')])
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Form $form) => [
                'id' => $form->id,
                'name' => $form->name,
                'slug' => $form->slug,
                'description' => $form->description,
                'status' => $form->status,
                'fields_count' => count($form->fields ?? []),
                'submissions_count' => $form->submissions_count,
                'unread_count' => $form->unread_count,
                'updated' => $form->updated_at?->diffForHumans(),
            ]);

        return Inertia::render('plugin:modrix/form-builder/FormsIndex', [
            'forms' => $forms,
            'filters' => ['q' => $request->query('q')],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('plugin:modrix/form-builder/FormEditor', [
            'formRecord' => [
                'id' => null,
                'name' => '',
                'slug' => '',
                'description' => '',
                'status' => 'active',
                'fields' => FormSchema::cleanFields([
                    [
                        'id' => Str::lower(Str::random(8)),
                        'type' => 'text',
                        'key' => 'full_name',
                        'label' => 'Full name',
                        'placeholder' => 'Jane Doe',
                        'help' => '',
                        'required' => true,
                        'width' => 'half',
                        'default_value' => '',
                        'options' => [],
                        'min' => null,
                        'max' => null,
                        'condition' => null,
                    ],
                    [
                        'id' => Str::lower(Str::random(8)),
                        'type' => 'email',
                        'key' => 'email',
                        'label' => 'Email address',
                        'placeholder' => 'jane@example.com',
                        'help' => '',
                        'required' => true,
                        'width' => 'half',
                        'default_value' => '',
                        'options' => [],
                        'min' => null,
                        'max' => null,
                        'condition' => null,
                    ],
                    [
                        'id' => Str::lower(Str::random(8)),
                        'type' => 'textarea',
                        'key' => 'message',
                        'label' => 'Message',
                        'placeholder' => 'Tell us how we can help…',
                        'help' => '',
                        'required' => true,
                        'width' => 'full',
                        'default_value' => '',
                        'options' => [],
                        'min' => null,
                        'max' => null,
                        'condition' => null,
                    ],
                ]),
                'settings' => FormSchema::cleanSettings([
                    'submit_label' => Cms::setting('modrix/form-builder', 'default_submit_label', 'Submit'),
                    'success_message' => Cms::setting('modrix/form-builder', 'default_success_message', 'Thank you! Your submission has been received.'),
                    'redirect_url' => '',
                    'notify_email' => '',
                    'store_submissions' => true,
                    'honeypot' => true,
                ]),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $form = Form::query()->create($this->validated($request));

        return redirect()->route('plugin.modrix.form-builder.forms.edit', $form)
            ->with('status', 'Form created. Embed it on any page with the “Dynamic Form” block.');
    }

    public function edit(Form $form): Response
    {
        return Inertia::render('plugin:modrix/form-builder/FormEditor', [
            'formRecord' => [
                'id' => $form->id,
                'name' => $form->name,
                'slug' => $form->slug,
                'description' => $form->description ?? '',
                'status' => $form->status,
                'fields' => $form->fields ?? [],
                'settings' => FormSchema::cleanSettings($form->settings ?? []),
            ],
        ]);
    }

    public function update(Request $request, Form $form): RedirectResponse
    {
        $form->update($this->validated($request, $form));

        return redirect()->route('plugin.modrix.form-builder.forms.edit', $form)
            ->with('status', 'Form saved.');
    }

    public function duplicate(Form $form): RedirectResponse
    {
        $baseSlug = Str::limit($form->slug.'-copy', 100, '');
        $slug = $baseSlug;
        $counter = 2;

        while (Form::query()->where('slug', $slug)->exists()) {
            $slug = Str::limit($baseSlug, 95, '').'-'.$counter;
            $counter++;
        }

        $copy = Form::query()->create([
            'name' => $form->name.' (Copy)',
            'slug' => $slug,
            'description' => $form->description,
            'fields' => $form->fields,
            'settings' => $form->settings,
            'status' => 'paused',
            'submissions_count' => 0,
        ]);

        return redirect()->route('plugin.modrix.form-builder.forms.edit', $copy)
            ->with('status', 'Form duplicated.');
    }

    public function toggleStatus(Form $form): RedirectResponse
    {
        $next = $form->status === 'active' ? 'paused' : 'active';
        $form->update(['status' => $next]);

        return back()->with('status', $next === 'active' ? "Form \"{$form->name}\" activated." : "Form \"{$form->name}\" paused.");
    }

    public function destroy(Form $form): RedirectResponse
    {
        $form->delete();

        return redirect()->route('plugin.modrix.form-builder.forms.index')
            ->with('status', 'Form and its submissions deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Form $form = null): array
    {
        $request->merge(['slug' => Slugs::from((string) ($request->input('slug') ?: $request->input('name')))]);

        $data = $request->validate(
            [
                'name' => ['required', 'string', 'max:160'],
                'slug' => ['required', 'string', 'max:120', Rule::unique('fb_forms', 'slug')->ignore($form)],
                'description' => ['nullable', 'string', 'max:1000'],
                'status' => ['required', 'in:active,paused'],
                'fields' => ['required', 'json', 'max:500000'],
                'settings' => ['required', 'json', 'max:50000'],
            ],
            [
                'name.required' => 'Please enter a form name at the top-left of the studio before saving.',
            ],
            [
                'name' => 'form name',
            ],
        );

        $fields = json_decode($data['fields'], true);
        $settings = json_decode($data['settings'], true);

        return [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? '',
            'status' => $data['status'],
            'fields' => FormSchema::cleanFields(is_array($fields) ? $fields : []),
            'settings' => FormSchema::cleanSettings(is_array($settings) ? $settings : []),
        ];
    }
}
