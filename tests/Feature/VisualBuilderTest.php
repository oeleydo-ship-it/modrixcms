<?php

namespace Tests\Feature;

use App\Cms\Builder\Schema\BlockRegistry;
use App\Cms\Builder\Schema\BlockType;
use App\Cms\Builder\Schema\DesignBlockLibrary;
use App\Cms\Builder\Schema\DocumentValidator;
use App\Http\Controllers\Builder\TemplateController;
use App\Models\BuilderTemplate;
use App\Models\Page;
use App\Models\User;
use App\PageBuilder\PageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function test_code_blocks_sit_in_their_own_group_and_publish_with_the_page(): void
    {
        $this->actingAs($this->admin());

        $this->assertSame('code', BlockType::Html->group());
        $this->assertSame('code', BlockType::CustomCode->group());
        $this->assertSame('Custom code / script', BlockType::CustomCode->label());
        $this->assertSame('capture', BlockType::SiteForm->group());
        $this->assertSame('Saved form', BlockType::SiteForm->label());
        $this->assertSame(['formSlug' => ''], BlockRegistry::defaultProps(BlockType::SiteForm));

        $page = $this->builderPage(['slug' => 'with-code', 'title' => 'With code']);
        $document = ['schema_version' => 1, 'settings' => [], 'sections' => [[
            'id' => (string) Str::ulid(),
            'width' => 'boxed',
            'sticky' => false,
            'style' => [],
            'rows' => [[
                'id' => (string) Str::ulid(),
                'stackBelow' => 'mobile',
                'style' => [],
                'columns' => [[
                    'id' => (string) Str::ulid(),
                    'span' => ['desktop' => 12, 'tablet' => 12, 'mobile' => 12],
                    'style' => [],
                    'blocks' => [
                        ['id' => (string) Str::ulid(), 'type' => 'html', 'props' => ['html' => '<iframe src="https://example.test/forms/lead?embed=1" title="Lead"></iframe>'], 'style' => []],
                        ['id' => (string) Str::ulid(), 'type' => 'custom_code', 'props' => ['html' => '<div id="widget"></div>', 'css' => '#widget{color:red}', 'js' => 'window.widgetReady = true;', 'placement' => 'body_end'], 'style' => []],
                    ],
                ]],
            ]],
        ]]];

        $this->putJson("/api/v1/pages/{$page->id}", ['version' => 1, 'document' => $document])->assertOk();
        $this->postJson("/api/v1/pages/{$page->id}/publish", ['version' => 2])->assertOk();

        $this->get('/with-code')->assertOk()
            ->assertSee('example.test/forms/lead?embed=1', false)
            ->assertSee('window.widgetReady = true;', false)
            ->assertSee('body_end', false);
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

        $this->assertGreaterThan(35, count($designs));

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

    public function test_capture_blocks_support_editable_labels_field_names_and_consent(): void
    {
        $this->actingAs($this->admin());

        $page = Page::factory()->create([
            'editor' => Page::EDITOR_BUILDER,
            'title' => 'Lead Capture',
            'slug' => 'lead-capture',
        ]);

        $initial = $this->getJson(route('builder.pages.show', $page))
            ->assertOk();
        $version = $initial->json('data.version');
        $schemaVersion = $initial->json('data.document.schema_version');

        $document = [
            'schema_version' => $schemaVersion,
            'sections' => [
                [
                    'id' => (string) Str::ulid(),
                    'rows' => [
                        [
                            'id' => (string) Str::ulid(),
                            'columns' => [
                                [
                                    'id' => (string) Str::ulid(),
                                    'span' => ['desktop' => 12],
                                    'blocks' => [
                                        [
                                            'id' => (string) Str::ulid(),
                                            'type' => 'phone_input',
                                            'props' => [
                                                'label' => 'WhatsApp number',
                                                'showLabel' => true,
                                                'fieldName' => 'whatsapp_number',
                                                'placeholder' => '(555) 010-9999',
                                                'required' => true,
                                                'layout' => 'stacked',
                                                'consentText' => 'Allow WhatsApp updates.',
                                                'buttonLabel' => 'Send code',
                                            ],
                                        ],
                                        [
                                            'id' => (string) Str::ulid(),
                                            'type' => 'email_optin',
                                            'props' => [
                                                'label' => 'Work email',
                                                'showLabel' => true,
                                                'fieldName' => 'work_email',
                                                'placeholder' => 'you@company.com',
                                                'required' => true,
                                                'buttonLabel' => 'Join list',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->putJson(route('builder.pages.update', $page), [
            'document' => $document,
            'version' => $version,
        ])
            ->assertOk()
            ->assertJsonPath('data.document.sections.0.rows.0.columns.0.blocks.0.props.label', 'WhatsApp number')
            ->assertJsonPath('data.document.sections.0.rows.0.columns.0.blocks.0.props.fieldName', 'whatsapp_number')
            ->assertJsonPath('data.document.sections.0.rows.0.columns.0.blocks.1.props.label', 'Work email');

        $this->postJson(route('builder.pages.publish', $page))->assertOk();

        $this->get('/lead-capture')->assertOk()->assertSee('Lead Capture');
    }

    public function test_builder_pages_follow_the_theme_settings(): void
    {
        $this->actingAs($this->admin());
        $page = $this->builderPage(['slug' => 'themed', 'is_published' => true]);

        // The canvas loads the theme's CSS variables and offers its colours as linked swatches.
        $this->get(route('admin.pages.builder', $page))->assertOk()
            ->assertSee('<style id="site-design">:root{--site-primary:', false)
            ->assertSee('"value":"var(--site-primary, #4f46e5)"', false);

        // New pages take their content width from the theme; designs reference theme colours.
        $this->getJson("/api/v1/pages/{$page->id}")
            ->assertJsonPath('data.document.settings.contentWidth', 'var(--site-content-width, 1140px)');
        $design = json_encode(app(DesignBlockLibrary::class)->find('feature-trio'), JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString('var(--site-heading, #0f172a)', $design);
        $this->assertStringContainsString('var(--site-section-padding, 88px)', $design);

        $this->putJson("/api/v1/pages/{$page->id}", ['version' => 1, 'document' => $this->document('feature-trio')]);
        $this->postJson("/api/v1/pages/{$page->id}/publish");

        $this->get('/themed')->assertOk()->assertSee('<style id="site-design">:root{--site-primary:', false);
    }

    public function test_site_header_and_footer_wrap_every_builder_page(): void
    {
        $this->actingAs($this->admin());

        // Opening them creates them from starter designs; they are never ordinary pages.
        $this->get(route('admin.pages.site-header'))->assertOk()->assertSee('<div id="builder"', false);
        $this->get(route('admin.pages.site-footer'))->assertOk();
        $header = Page::query()->where('role', Page::ROLE_HEADER)->firstOrFail();
        $footer = Page::query()->where('role', Page::ROLE_FOOTER)->firstOrFail();
        $this->assertSame('Site header', $header->title);
        $this->assertNotEmpty($header->document['sections']);
        $this->getJson("/api/v1/pages/{$header->id}")->assertOk()->assertJsonPath('data.role', 'header');
        $this->getJson("/api/v1/pages/{$footer->id}")->assertOk()->assertJsonPath('data.role', 'footer');
        $this->get(route('admin.pages.index'))->assertInertia(fn ($inertia) => $inertia
            ->where('pages.data', [])
            ->where('siteChrome.headerLive', false));
        $this->get(route('admin.pages.edit', $header), ['X-Inertia' => 'true'])->assertStatus(409);

        $page = $this->builderPage(['slug' => 'landing', 'is_published' => true]);
        $this->putJson("/api/v1/pages/{$page->id}", ['version' => 1, 'document' => $this->document()]);
        $this->postJson("/api/v1/pages/{$page->id}/publish");
        $own = count($page->fresh()->published_document['sections']);

        // Until they are published, pages show without them.
        $this->assertCount($own, $this->get('/landing')->viewData('document')['sections']);

        $this->postJson("/api/v1/pages/{$header->id}/publish")->assertOk();
        $this->postJson("/api/v1/pages/{$footer->id}/publish")->assertOk();
        $this->assertFalse($header->fresh()->is_published, 'the header never becomes a page of its own');

        $sections = $this->get('/landing')->assertOk()->viewData('document')['sections'];
        $this->assertCount($own + count($header->document['sections']) + count($footer->document['sections']), $sections);
        $this->assertSame($header->document['sections'][0]['id'], $sections[0]['id']);
        $footerSections = $footer->fresh()->published_document['sections'];
        $this->assertSame(end($footerSections)['id'], end($sections)['id']);

        // The canvas gets them too, locked, with links to edit them.
        $this->get(route('admin.pages.builder', $page))
            ->assertSee('<script id="site-chrome"', false)
            ->assertSee('"editUrl":"'.route('admin.pages.site-header').'"', false);

        // A page can switch them off.
        $this->put(route('admin.pages.update', $page), [
            'title' => $page->title, 'slug' => 'landing', 'blocks' => '[]', 'is_published' => true,
            'show_site_header' => false, 'show_site_footer' => true,
        ])->assertSessionHasNoErrors();
        $this->assertCount($own + count($footer->document['sections']), $this->get('/landing')->viewData('document')['sections']);
    }

    public function test_atomos99_theme_and_builder_pages_are_valid_and_editable(): void
    {
        $this->actingAs($this->admin());

        $blocks = $this->getJson('/api/v1/design-blocks')->assertOk()->json('data');
        $atomosKeys = array_values(array_map(
            static fn (array $b): string => $b['key'],
            array_filter($blocks, static fn (array $b): bool => str_starts_with($b['key'], 'atomos99-')),
        ));

        $this->assertContains('atomos99-navbar', $atomosKeys);
        $this->assertContains('atomos99-hero-home', $atomosKeys);
        $this->assertContains('atomos99-process', $atomosKeys);
        $this->assertContains('atomos99-benefits', $atomosKeys);
        $this->assertContains('atomos99-about-hero', $atomosKeys);
        $this->assertContains('atomos99-about-team', $atomosKeys);
        $this->assertContains('atomos99-services-hero', $atomosKeys);
        $this->assertContains('atomos99-blog-grid', $atomosKeys);
        $this->assertContains('atomos99-faq-hub', $atomosKeys);

        $templates = $this->getJson('/api/v1/templates?type=page&per_page=100')->assertOk()->json('data');
        $templateIds = array_column($templates, 'id');
        $this->assertContains('atomos99-home-page', $templateIds);
        $this->assertContains('atomos99-about-page', $templateIds);
        $this->assertContains('atomos99-services-page', $templateIds);
        $this->assertContains('atomos99-faqs-page', $templateIds);
        $this->assertContains('atomos99-blog-page', $templateIds);

        File::copyDirectory(
            base_path('extensions/themes/modrix/nexus-ai'),
            config('cms.path').'/themes/modrix/nexus-ai',
        );

        $result = app(DesignBlockLibrary::class)->applyAtomos99Site();
        $this->assertSame('modrix/nexus-ai', $result['theme']);

        $this->get('/')
            ->assertOk()
            ->assertSee('We build custom AI solutions for your business')
            ->assertSee('See measurable results')
            ->assertSee('AI Task Draft')
            ->assertSee('Save hours every week')
            ->assertSee('data:image/svg+xml;base64,', false);
        $this->get('/about')->assertOk()->assertSee('An AI partner that thinks like an operator, not a vendor');
        $this->get('/services')->assertOk()->assertSee('Four ways we help you grow with AI');
        $this->get('/faqs')->assertOk()->assertSee('hi@nexusai.io', false);
        $this->get('/blogs')->assertOk()->assertSee('How to know if your business is ready for AI');
        $this->get('/blog/how-to-know-if-your-business-is-ready-for-ai')->assertOk();
    }

    /** Documents compared without the server-stamped schema version. */
    private function strip(array $document): array
    {
        unset($document['schema_version']);

        return $document;
    }
}
