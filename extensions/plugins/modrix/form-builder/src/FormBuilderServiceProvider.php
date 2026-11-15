<?php

namespace Modrix\FormBuilder;

use App\Cms\Cms;
use App\Cms\PluginServiceProvider;
use Illuminate\Support\Facades\Schema;
use Modrix\FormBuilder\Models\Form;
use Throwable;

class FormBuilderServiceProvider extends PluginServiceProvider
{
    protected function bootPlugin(): void
    {
        $formOptions = ['' => '— Select a form —'];

        try {
            if (Schema::hasTable('fb_forms')) {
                $formOptions += Form::query()->orderBy('name')->pluck('name', 'slug')->all();
            }
        } catch (Throwable) {
            // Database may not be ready yet during early install or testing bootstrap.
        }

        $this->block('form', [
            'label' => 'Dynamic Form',
            'description' => 'Embed any custom form created in Admin → Forms.',
            'fields' => [
                ['name' => 'form_slug', 'label' => 'Form', 'type' => 'select', 'options' => $formOptions],
                ['name' => 'title', 'label' => 'Heading override', 'type' => 'text', 'help' => 'Leave empty to use the form name.'],
                ['name' => 'description', 'label' => 'Intro text override', 'type' => 'textarea'],
                ['name' => 'layout', 'label' => 'Layout', 'type' => 'select', 'options' => [
                    'card' => 'Centered card',
                    'split' => 'Split (intro beside form)',
                    'minimal' => 'Minimal inline',
                ]],
            ],
            'defaults' => [
                'anchor' => 'form',
                'form_slug' => array_key_first($formOptions) ?? '',
                'title' => '',
                'description' => '',
                'layout' => 'card',
            ],
        ]);

        $this->filter('block.data', function (array $data, string $type): array {
            if ($type !== $this->name().':form') {
                return $data;
            }

            $slug = (string) ($data['form_slug'] ?? '');
            $form = null;

            try {
                if ($slug !== '' && Schema::hasTable('fb_forms')) {
                    $form = Form::query()->active()->where('slug', $slug)->first();
                }
            } catch (Throwable) {
                $form = null;
            }

            if ($form === null) {
                $data['form'] = null;

                return $data;
            }

            $data['form'] = [
                'id' => $form->id,
                'name' => $form->name,
                'slug' => $form->slug,
                'description' => $form->description,
                'fields' => $form->fields ?? [],
                'settings' => $form->settings ?? [],
                'submitUrl' => route('plugin.modrix.form-builder.submit', $form->slug),
                'submitted' => (bool) session("fb_submitted.{$form->slug}"),
            ];

            return $data;
        });

        $version = '?v='.urlencode((string) ($this->plugin['version'] ?? '1.0.0'));
        $frontendCss = '<link rel="stylesheet" href="'.e(Cms::asset($this->name(), 'form-builder.css').$version).'">';
        $adminCss = '<link rel="stylesheet" href="'.e(Cms::asset($this->name(), 'form-builder-admin.css').$version).'">';
        $this->hook('head', fn (): string => $frontendCss);
        $this->hook('admin.head', fn (): string => $frontendCss.PHP_EOL.$adminCss);
    }
}
