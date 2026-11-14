<?php

namespace Tests\Feature;

use App\Cms\Builder\Schema\DesignBlockLibrary;
use App\Cms\Builder\Schema\DocumentValidator;
use App\Http\Controllers\Builder\TemplateController;
use App\Models\BuilderTemplate;
use App\Models\Page;
use App\Models\User;
use App\PageBuilder\PageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisualBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** A valid document: one ready-made design section. */
    private function document(string $design = 'hero-centred'): array
    {
        return ['schema_version' => 1, 'settings' => [], ...app(DesignBlockLibrary::class)->find($design)['document']];
    }

    private function builderPage(array $attributes = []): Page
    {
        return Page::factory()->create(['editor' => Page::EDITOR_BUILDER, 'blocks' => [], ...$attributes]);
    }

    public function test_only_admins_reach_the_builder_and_its_api(): void
    {
        $page = $this->builderPage();

        $this->get(route('admin.pages.builder', $page))->assertRedirect(route('admin.login'));
        $this->getJson("/api/v1/pages/{$page->id}")->assertUnauthorized();

        $this->actingAs(User::factory()->create());
        $this->get(route('admin.pages.builder', $page))->assertRedirect(route('admin.login'));
    }

    public function test_new_builder_page_opens_the_builder(): void
    {
        $this->actingAs($this->admin());

        $response = $this->post(route('admin.pages.store-builder'));
        $page = Page::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('admin.pages.builder', $page));
        $this->assertSame(Page::EDITOR_BUILDER, $page->editor);
        $this->assertFalse($page->is_published);

        $this->get(route('admin.pages.builder', $page))->assertOk()
            ->assertSee('data-page-id="'.$page->id.'"', false)
            ->assertSee('<div id="builder"', false);

        // From an Inertia screen the builder (its own full-screen app) asks for a full page load.
        $this->get(route('admin.pages.builder', $page), ['X-Inertia' => 'true'])->assertStatus(409);
    }

    public function test_a_page_loads_with_a_blank_draft_then_saves_with_versioning(): void
    {
        $this->actingAs($this->admin());
        $page = $this->builderPage();

        $this->getJson("/api/v1/pages/{$page->id}")->assertOk()
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.document.sections', [])
            ->assertJsonPath('data.funnel', null);

        $this->putJson("/api/v1/pages/{$page->id}", ['version' => 1, 'document' => $this->document()])
            ->assertOk()->assertJsonPath('data.version', 2);

        // Someone saving from an older version gets a conflict, not an overwrite.
        $this->putJson("/api/v1/pages/{$page->id}", ['version' => 1, 'document' => $this->document('feature-trio')])
            ->assertStatus(409)->assertJsonPath('current_version', 2);

        $this->putJson("/api/v1/pages/{$page->id}", ['version' => 2, 'document' => ['schema_version' => 1, 'sections' => [['nope' => true]]]])
            ->assertUnprocessable()->assertJsonValidationErrors('document');

        $this->assertSame('hero', $page->fresh()->document['sections'][0]['name'] ?? 'hero');
    }

    public function test_publishing_puts_the_page_live_and_drafts_stay_private(): void
    {
        $this->actingAs($this->admin());
        $page = $this->builderPage(['slug' => 'launch', 'title' => 'Launch', 'is_published' => false]);

        $this->putJson("/api/v1/pages/{$page->id}", ['version' => 1, 'document' => $this->document()])->assertOk();
        $this->get('/launch')->assertNotFound();

        $this->postJson("/api/v1/pages/{$page->id}/publish", ['version' => 2])->assertOk()
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.has_unpublished_changes', false);

        $response = $this->get('/launch')->assertOk()
            ->assertViewIs('builder-page')
            ->assertSee('<title>Launch · ', false)
            ->assertSee('id="published-document"', false);
        $this->assertSame($page->fresh()->published_document, $response->viewData('document'));

        // Later edits stay in the draft until the next publish.
        $this->putJson("/api/v1/pages/{$page->id}", ['version' => 2, 'document' => $this->document('feature-trio')])
            ->assertJsonPath('data.has_unpublished_changes', true);
        $this->assertNotEquals($page->fresh()->document, $page->fresh()->published_document);

        // An Inertia visit gets a full page load, since the page has its own HTML.
        $this->get('/launch', ['X-Inertia' => 'true'])->assertStatus(409);
    }

    public function test_a_published_builder_home_page_is_the_home_page(): void
    {
        $this->actingAs($this->admin());
        $page = $this->builderPage(['is_home' => true, 'is_published' => true]);

        $this->get('/')->assertOk()->assertInertia(fn ($inertia) => $inertia->component('Site/Page'));

        $this->putJson("/api/v1/pages/{$page->id}", ['version' => 1, 'document' => $this->document()]);
        $this->postJson("/api/v1/pages/{$page->id}/publish");

        $this->get('/')->assertOk()->assertViewIs('builder-page');
    }

    public function test_history_lists_versions_and_restores_one(): void
    {
        $this->actingAs($this->admin());
        $page = $this->builderPage();

        $hero = $this->document();
        $this->putJson("/api/v1/pages/{$page->id}", ['version' => 1, 'document' => $hero]);
        $this->putJson("/api/v1/pages/{$page->id}", ['version' => 2, 'document' => $this->document('feature-trio'), 'label' => 'Hero version']);

        $versions = $this->getJson("/api/v1/pages/{$page->id}/versions?with_document=1")->assertOk()->json('data');
        $this->assertSame('Hero version', $versions[0]['label']);
        $this->assertArrayHasKey('document', $versions[0]);

        $this->postJson("/api/v1/pages/{$page->id}/versions/{$versions[0]['id']}/restore")->assertOk()
            ->assertJsonPath('data.version', 4);

        $this->assertEquals($this->strip($hero), $this->strip($page->fresh()->document));
        $this->assertSame('Before restoring v2', $page->versions()->first()->label);
    }

    public function test_every_design_is_a_valid_document_and_none_need_funnel_features(): void
    {
        $this->actingAs($this->admin());
        $designs = $this->getJson('/api/v1/design-blocks')->assertOk()->json('data');
        $validator = app(DocumentValidator::class);

        $this->assertGreaterThan(20, count($designs));

        foreach ($designs as $design) {
            $document = ['schema_version' => 1, 'settings' => [], ...$design['document']];
            $this->assertSame([], $validator->errorsFor($document), $design['key']);
            $this->assertDoesNotMatchRegularExpression('/"type":"(form|email_optin|phone_input|checkout|product|booking|calendar|popup_trigger)"/', json_encode($design), $design['key']);
        }
    }

    public function test_templates_install_pages_and_keep_saved_sections(): void
    {
        $this->actingAs($this->admin());
        $page = $this->builderPage();

        $this->getJson('/api/v1/templates?type=page')->assertOk()
            ->assertJsonPath('data.0.id', TemplateController::STARTER_ID);

        $this->postJson('/api/v1/templates/'.TemplateController::STARTER_ID.'/install', ['page_id' => $page->id, 'expected_version' => 1])
            ->assertOk()->assertJsonPath('data.page.version', 2);
        $this->assertGreaterThan(5, count($page->fresh()->document['sections']));

        $section = $this->document('feature-trio')['sections'][0];
        $this->postJson('/api/v1/templates', ['name' => 'My features', 'type' => 'section', 'node' => $section])->assertCreated();
        $this->postJson('/api/v1/templates', ['name' => 'Broken', 'type' => 'section', 'node' => ['rows' => 'x']])->assertUnprocessable();

        $saved = $this->getJson('/api/v1/templates?type=section&scope=mine')->assertOk()->json('data.0');
        $this->assertSame('My features', $saved['name']);
        $this->getJson("/api/v1/templates/{$saved['id']}")->assertJsonPath('data.payload.section.id', $section['id']);

        $this->postJson('/api/v1/templates', ['name' => 'Whole page', 'type' => 'page', 'page_id' => $page->id])->assertCreated();
        $this->deleteJson("/api/v1/templates/{$saved['id']}")->assertNoContent();
        $this->assertSame(1, BuilderTemplate::query()->count());
    }

    public function test_media_library_uploads_lists_and_deletes(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $id = $this->postJson('/api/v1/media', ['file' => UploadedFile::fake()->image('photo.jpg', 640, 480)])
            ->assertCreated()
            ->assertJsonPath('data.width', 640)
            ->json('data.id');

        $this->postJson('/api/v1/media', ['file' => UploadedFile::fake()->create('logo.svg', 2, 'image/svg+xml')])
            ->assertUnprocessable();

        $this->getJson('/api/v1/media')->assertOk()->assertJsonCount(1, 'data');
        $this->deleteJson("/api/v1/media/{$id}")->assertNoContent();
        $this->assertSame([], Storage::disk('public')->allFiles('media'));
    }

    public function test_funnel_only_pickers_answer_with_empty_lists(): void
    {
        $this->actingAs($this->admin());

        foreach (['forms', 'popups', 'products', 'booking/services', 'booking/staff', 'global-components'] as $path) {
            $this->getJson("/api/v1/{$path}")->assertOk()->assertExactJson(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 0]]);
        }
    }

    public function test_admin_page_list_offers_the_builder(): void
    {
        $this->actingAs($this->admin());
        $page = $this->builderPage(['title' => 'Designed']);

        $this->get(route('admin.pages.index'))->assertInertia(fn ($inertia) => $inertia
            ->where('pages.data.0.editor', 'builder')
            ->where('pages.data.0.builder_url', route('admin.pages.builder', $page)));
    }

    public function test_a_block_page_converts_to_the_builder_and_stays_live_until_published(): void
    {
        $this->actingAs($this->admin());
        $page = Page::factory()->create([
            'slug' => 'about-us',
            'is_published' => true,
            'blocks' => [
                PageBuilder::make('page_header', ['title' => 'About us']),
                PageBuilder::make('text', ['content' => "## Our story\n\nWe **build** things."]),
                PageBuilder::make('cta', ['title' => 'Talk to us', 'primary_label' => 'Contact', 'primary_url' => '/contact']),
                ['id' => 'p1', 'type' => 'acme/maps:map', 'hidden' => false, 'data' => []],
            ],
        ]);

        $this->post(route('admin.pages.convert', $page), [], ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('admin.pages.builder', $page));

        $page->refresh();
        $this->assertSame(Page::EDITOR_BUILDER, $page->editor);
        $this->assertCount(3, $page->document['sections']);
        $this->assertSame([], app(DocumentValidator::class)->errorsFor($page->document));
        $this->assertStringContainsString('<h2>Our story</h2>', json_encode($page->document, JSON_UNESCAPED_SLASHES));
        $this->assertStringContainsString('var(--site-dark, #0f172a)', json_encode($page->document, JSON_UNESCAPED_SLASHES));
        $this->assertSame('Converted. These blocks have no visual-builder version and were left out: acme/maps:map.', session('status'));
        $this->assertCount(4, $page->blocks, 'the original blocks are kept');

        // Not published from the builder yet: visitors still get the block version.
        $this->get('/about-us')->assertOk()->assertInertia(fn ($inertia) => $inertia->component('Site/Page'));

        $this->postJson("/api/v1/pages/{$page->id}/publish")->assertOk();
        $this->get('/about-us')->assertOk()->assertViewIs('builder-page');

        // Switching back shows the blocks again; switching once more keeps the builder design.
        $this->post(route('admin.pages.switch-to-blocks', $page))->assertRedirect(route('admin.pages.edit', $page));
        $this->get('/about-us')->assertInertia(fn ($inertia) => $inertia->component('Site/Page'));

        $design = $page->fresh()->document;
        $this->post(route('admin.pages.convert', $page), [], ['X-Inertia' => 'true'])->assertStatus(409);
        $this->assertEquals($design, $page->fresh()->document);
    }

    public function test_the_page_form_offers_conversion_and_switching_back(): void
    {
        $this->actingAs($this->admin());
        $blockPage = Page::factory()->create();
        $builderPage = $this->builderPage();

        $this->get(route('admin.pages.edit', $blockPage))->assertInertia(fn ($inertia) => $inertia
            ->where('page.convert_url', route('admin.pages.convert', $blockPage))
            ->where('page.has_builder_design', false));

        $this->get(route('admin.pages.edit', $builderPage))->assertInertia(fn ($inertia) => $inertia
            ->where('page.editor', 'builder')
            ->where('page.switch_back_url', null));

        $this->post(route('admin.pages.switch-to-blocks', $builderPage))->assertStatus(422);
    }

    /** Documents compared without the server-stamped schema version. */
    private function strip(array $document): array
    {
        unset($document['schema_version']);

        return $document;
    }
}
