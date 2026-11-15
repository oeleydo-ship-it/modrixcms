<?php

namespace Tests\Feature;

use App\Cms\Builder\PageDocuments;
use App\Cms\Builder\SiteChrome;
use App\Cms\Themes\ThemeManager;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modrix\FormBuilder\Models\Form;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class FormSharingTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    private function makeForm(string $status = 'active'): Form
    {
        $this->confirmedAdmin();
        $this->post(route('admin.plugins.enable', ['modrix', 'form-builder']), ['trust' => 1])->assertSessionHasNoErrors();
        $this->loadPluginRoutes();

        return Form::query()->create([
            'name' => 'Callback Request',
            'slug' => 'callback-request',
            'description' => 'Phone-first callback scheduling.',
            'status' => $status,
            'fields' => [['id' => 'f1', 'type' => 'text', 'key' => 'full_name', 'label' => 'Your name', 'required' => true]],
            'settings' => [],
        ]);
    }

    public function test_a_form_has_its_own_public_page_within_the_site_layout(): void
    {
        $this->makeForm();

        $this->get('/forms/callback-request')->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Site/Page')
                ->where('embed', false)
                ->where('meta.title', 'Callback Request · '.app(Settings::class)->get('site_name'))
                ->where('blocks.0.type', 'modrix/form-builder:form')
                ->where('blocks.0.data.form.slug', 'callback-request')
                ->where('blocks.0.data.form.fields.0.key', 'full_name'));
    }

    public function test_the_form_page_sits_inside_the_active_themes_own_header_and_footer(): void
    {
        $this->makeForm();
        $theme = app(ThemeManager::class)->name();
        $document = PageDocuments::blank();
        $document['sections'] = [[
            'id' => (string) Str::ulid(), 'width' => 'full', 'sticky' => false, 'style' => [],
            'rows' => [['id' => (string) Str::ulid(), 'stackBelow' => 'mobile', 'style' => [], 'columns' => [[
                'id' => (string) Str::ulid(), 'span' => ['desktop' => 12, 'tablet' => 12, 'mobile' => 12], 'style' => [],
                'blocks' => [['id' => (string) Str::ulid(), 'type' => 'heading', 'props' => ['text' => 'Theme header marker', 'level' => 'h2'], 'style' => []]],
            ]]]],
        ]];
        app(SiteChrome::class)->install('header', $theme, $document, app(PageDocuments::class));

        $response = $this->get('/forms/callback-request')->assertOk()->assertViewIs('builder-page');
        $this->assertStringContainsString('Theme header marker', $response->getContent());
        $this->assertStringContainsString('site_form', $response->getContent());
        $this->assertStringContainsString('callback-request', $response->getContent());

        $this->get('/forms/callback-request?embed=1')->assertOk()->assertInertia(fn ($page) => $page->where('embed', true));
    }

    public function test_the_embed_link_marks_the_page_for_the_bare_layout_the_iframe_uses(): void
    {
        $this->makeForm();

        $this->get('/forms/callback-request?embed=1')->assertOk()
            ->assertInertia(fn ($page) => $page->where('embed', true)->where('blocks.0.data.form.slug', 'callback-request'));
    }

    public function test_paused_and_unknown_forms_are_not_found(): void
    {
        $this->makeForm(status: 'paused');

        $this->get('/forms/callback-request')->assertNotFound();
        $this->get('/forms/does-not-exist')->assertNotFound();
    }

    public function test_the_studio_ships_the_share_dialog_and_unsaved_change_tracking(): void
    {
        $this->makeForm();
        $studio = file_get_contents(base_path('extensions/plugins/modrix/form-builder/public/form-builder-studio.js'));

        foreach (['Share & embed', 'Embed code (iframe)', '/forms/${slug}', '?embed=1', 'mfb-save-btn', 'hasUnsavedChanges', 'savedSnapshotRef'] as $needle) {
            $this->assertStringContainsString($needle, $studio, $needle);
        }
        $this->assertStringNotContainsString('form.isDirty', $studio, 'the studio no longer relies on Inertia\'s isDirty, which flags forms that were only loaded');
    }
}
