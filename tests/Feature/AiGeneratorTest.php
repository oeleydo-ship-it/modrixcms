<?php

namespace Tests\Feature;

use App\Cms\Builder\Schema\DocumentValidator;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_guests_cannot_access_ai_generator(): void
    {
        $this->get(route('admin.ai.index'))->assertRedirect(route('admin.login'));
        $this->postJson(route('admin.ai.generate'), ['prompt' => 'Coffee shop', 'mode' => 'multipage'])->assertUnauthorized();
    }

    public function test_ai_settings_page_saves_encrypted_key_and_tests_connection(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.ai.update'), [
            'ai_provider' => 'openai',
            'ai_api_key' => 'sk-secret-key-1234',
            'ai_model' => 'gpt-4o-mini',
        ])->assertSessionHasNoErrors();

        $raw = Setting::query()->where('key', 'ai_api_key')->value('value');
        $this->assertNotSame('sk-secret-key-1234', $raw);
        $this->assertSame('sk-secret-key-1234', app(Settings::class)->get('ai_api_key'));

        // A blank API key keeps the saved encrypted key, and props never expose the key itself.
        $this->actingAs($admin)->put(route('admin.settings.ai.update'), [
            'ai_provider' => 'openai',
            'ai_api_key' => '',
            'ai_model' => 'gpt-4.1-mini',
        ])->assertSessionHasNoErrors();

        $this->assertSame('sk-secret-key-1234', app(Settings::class)->get('ai_api_key'));
        $this->actingAs($admin)->get(route('admin.settings.ai'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('aiKeySaved', true)
                ->where('aiConfigured', true)
                ->where('values.ai_model', 'gpt-4.1-mini'))
            ->assertDontSee('sk-secret-key-1234');

        Http::fake([
            'https://api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'OK']]],
            ], 200),
        ]);

        $this->actingAs($admin)->postJson(route('admin.settings.ai.test'))
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_ai_generates_and_applies_full_multipage_website(): void
    {
        $admin = $this->admin();

        app(Settings::class)->set([
            'ai_provider' => 'openai',
            'ai_api_key' => 'sk-test-key',
            'ai_model' => 'gpt-4o-mini',
        ]);

        $blueprint = [
            'brand' => [
                'site_name' => 'RoastCraft',
                'tagline' => 'Small-batch single origin coffee delivered fresh.',
                'footer_text' => 'RoastCraft Coffee Co.',
            ],
            'header' => [
                'announcement_text' => 'Free shipping on subscription boxes',
                'cta_label' => 'See Plans',
                'cta_slug' => 'pricing',
                'links' => [
                    ['label' => 'Home', 'slug' => 'home'],
                    ['label' => 'About', 'slug' => 'about'],
                    ['label' => 'Pricing', 'slug' => 'pricing'],
                    ['label' => 'Contact', 'slug' => 'contact'],
                ],
            ],
            'footer' => [
                'tagline' => 'Roasted to order in Portland.',
                'copyright' => '© 2026 RoastCraft',
                'links' => [
                    ['label' => 'About', 'slug' => 'about'],
                    ['label' => 'Pricing', 'slug' => 'pricing'],
                    ['label' => 'Contact', 'slug' => 'contact'],
                ],
            ],
            'pages' => [
                [
                    'title' => 'Home',
                    'slug' => 'home',
                    'is_home' => true,
                    'meta_title' => 'RoastCraft — Specialty Coffee',
                    'meta_description' => 'Freshly roasted coffee delivered to your door.',
                    'sections' => [
                        [
                            'type' => 'hero',
                            'eyebrow' => 'Freshly roasted',
                            'heading' => 'Better mornings start with better beans',
                            'subheading' => 'Single-origin harvests shipped within 24 hours of roasting.',
                            'primary_cta' => ['label' => 'Explore plans', 'slug' => 'pricing'],
                            'stats' => [['value' => '24h', 'label' => 'Roast to ship']],
                        ],
                        [
                            'type' => 'features',
                            'heading' => 'Why coffee lovers switch',
                            'items' => [
                                ['icon' => 'zap', 'title' => 'Peak freshness', 'body' => 'Roasted to order weekly.'],
                                ['icon' => 'globe', 'title' => 'Direct trade', 'body' => 'Sourced directly from partner farms.'],
                                ['icon' => 'check', 'title' => 'Flexible cadence', 'body' => 'Pause or skip anytime.'],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'About',
                    'slug' => 'about',
                    'is_home' => false,
                    'sections' => [
                        [
                            'type' => 'story',
                            'heading' => 'Crafted with growers',
                            'body' => 'We partner with independent farms across Colombia and Ethiopia.',
                            'bullets' => ['100% traceable lots', 'Compostable bags'],
                        ],
                    ],
                ],
                [
                    'title' => 'Pricing',
                    'slug' => 'pricing',
                    'is_home' => false,
                    'sections' => [
                        [
                            'type' => 'pricing',
                            'heading' => 'Subscription plans',
                            'plans' => [
                                ['name' => 'Single Bag', 'price' => '$18', 'interval' => 'month', 'features' => ['12oz whole bean'], 'button_label' => 'Start', 'slug' => 'contact', 'highlighted' => true],
                            ],
                        ],
                        [
                            'type' => 'faq',
                            'heading' => 'Common questions',
                            'faqs' => [['question' => 'When do you roast?', 'answer' => 'Every Tuesday morning.']],
                        ],
                    ],
                ],
                [
                    'title' => 'Contact',
                    'slug' => 'contact',
                    'is_home' => false,
                    'sections' => [
                        ['type' => 'contact', 'heading' => 'Talk to our roasters', 'email' => 'hello@roastcraft.test'],
                    ],
                ],
            ],
        ];

        Http::fake([
            'https://api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode($blueprint)]]],
            ], 200),
        ]);

        $generateResponse = $this->actingAs($admin)->postJson(route('admin.ai.generate'), [
            'prompt' => 'Specialty organic coffee roastery & subscription brand called RoastCraft based in Portland.',
            'mode' => 'multipage',
            'pages' => ['Home', 'About', 'Pricing', 'Contact'],
        ])->assertOk();

        $plan = $generateResponse->json('data');
        $this->assertCount(4, $plan['pages']);
        $this->assertCount(1, $plan['header_document']['sections']);
        $this->assertCount(1, $plan['footer_document']['sections']);

        $validator = app(DocumentValidator::class);
        $this->assertTrue($validator->passes($plan['header_document']));
        $this->assertTrue($validator->passes($plan['footer_document']));

        foreach ($plan['pages'] as $generatedPage) {
            $this->assertTrue($validator->passes($generatedPage['document']));
        }

        $applyResponse = $this->actingAs($admin)->postJson(route('admin.ai.apply'), [
            'plan' => $plan,
            'selected_slugs' => ['home', 'about', 'pricing', 'contact'],
            'replace_existing' => true,
            'publish_immediately' => true,
            'update_site_chrome' => true,
            'update_site_settings' => true,
        ])->assertOk();

        $this->assertCount(4, $applyResponse->json('data.pages'));
        $this->assertSame('RoastCraft', app(Settings::class)->get('site_name'));

        $header = Page::query()->where('role', Page::ROLE_HEADER)->firstOrFail();
        $this->assertCount(1, $header->published_document['sections']);

        $this->get('/')->assertOk();
        $this->get('/about')->assertOk();
        $this->get('/pricing')->assertOk();
        $this->get('/contact')->assertOk();

        $home = Page::home();
        $this->assertNotNull($home);
        $this->assertSame('builder', $home->editor);
    }

    public function test_ai_generates_single_page_via_gemini_without_overwriting_home(): void
    {
        $admin = $this->admin();

        app(Settings::class)->set([
            'ai_provider' => 'gemini',
            'ai_api_key' => 'gem-key-xyz',
            'ai_model' => 'gemini-2.5-flash',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'brand' => ['site_name' => 'RoastCraft'],
                                'pages' => [[
                                    'title' => 'Wholesale Program',
                                    'slug' => 'wholesale-program',
                                    'sections' => [
                                        ['type' => 'hero', 'heading' => 'Serve RoastCraft in your cafe'],
                                        ['type' => 'cta', 'heading' => 'Request a tasting kit'],
                                    ],
                                ]],
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $plan = $this->actingAs($admin)->postJson(route('admin.ai.generate'), [
            'prompt' => 'B2B wholesale coffee supply landing page for cafes and hotels.',
            'mode' => 'single',
            'page_title' => 'Wholesale Program',
        ])->assertOk()->json('data');

        $this->assertCount(1, $plan['pages']);
        $this->assertFalse($plan['pages'][0]['is_home']);

        $this->actingAs($admin)->postJson(route('admin.ai.apply'), [
            'plan' => $plan,
            'selected_slugs' => [$plan['pages'][0]['slug']],
            'publish_immediately' => true,
            'update_site_chrome' => false,
            'update_site_settings' => false,
        ])->assertOk();

        $this->get('/wholesale-program')->assertOk();
    }

    public function test_pages_index_exposes_inline_ai_workspace_and_existing_pages(): void
    {
        $admin = $this->admin();

        Page::factory()->create([
            'title' => 'About',
            'slug' => 'about',
            'editor' => Page::EDITOR_BLOCKS,
            'is_published' => true,
            'is_home' => false,
            'blocks' => [['type' => 'heading', 'text' => 'About our company']],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pages.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Pages/Index')
                ->where('ai.aiConfigured', false)
                ->has('ai.existingPages', 1)
                ->where('ai.existingPages.0.slug', 'about'));
    }

    public function test_ai_can_modify_and_redesign_existing_block_and_builder_pages(): void
    {
        $admin = $this->admin();

        app(Settings::class)->set([
            'ai_provider' => 'openai',
            'ai_api_key' => 'sk-test-redesign-1234',
            'ai_model' => 'gpt-4o-mini',
        ]);

        $about = Page::factory()->create([
            'title' => 'About',
            'slug' => 'about',
            'editor' => Page::EDITOR_BLOCKS,
            'is_published' => true,
            'is_home' => false,
            'menu_order' => 3,
            'show_in_menu' => true,
            'blocks' => [
                ['type' => 'heading', 'text' => 'Crafted for independent merchants'],
                ['type' => 'paragraph', 'text' => 'Legacy About paragraph to preserve in prompt context.'],
            ],
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'brand' => [
                                'site_name' => 'Modrix Commerce',
                                'tagline' => 'Independent merchant tools',
                                'footer_text' => 'Modrix',
                            ],
                            'pages' => [[
                                'title' => 'About Modrix',
                                'slug' => 'different-slug-ignored',
                                'meta_title' => 'About Modrix Commerce',
                                'meta_description' => 'Redesigned About page',
                                'sections' => [
                                    ['type' => 'hero', 'heading' => 'Built for independent merchants', 'subheading' => 'Modern redesigned story'],
                                    ['type' => 'stats', 'heading' => 'Trusted by stores', 'stats' => [['value' => '10k+', 'label' => 'Merchants']]],
                                    ['type' => 'cta', 'heading' => 'Launch your store today'],
                                ],
                            ]],
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $generateResponse = $this->actingAs($admin)->postJson(route('admin.ai.generate'), [
            'prompt' => 'Modernize the About page hero, keep our merchant focus, and highlight trust stats.',
            'mode' => 'redesign',
            'target_page_id' => $about->id,
            'tone' => 'Warm editorial',
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            $userMessage = (string) ($request['messages'][1]['content'] ?? '');

            return str_contains($userMessage, 'Crafted for independent merchants')
                && str_contains($userMessage, 'Legacy About paragraph');
        });

        $plan = $generateResponse->json('data');
        $this->assertCount(1, $plan['pages']);
        $this->assertSame($about->id, $plan['pages'][0]['target_page_id']);
        $this->assertSame('about', $plan['pages'][0]['slug']);
        $this->assertFalse($plan['pages'][0]['is_home']);

        $this->actingAs($admin)->postJson(route('admin.ai.apply'), [
            'plan' => $plan,
            'selected_slugs' => ['about'],
            'publish_immediately' => true,
            'update_site_chrome' => false,
            'update_site_settings' => false,
        ])->assertOk();

        $about->refresh();
        $this->assertSame('about', $about->slug);
        $this->assertSame('About Modrix', $about->title);
        $this->assertSame(Page::EDITOR_BUILDER, $about->editor);
        $this->assertSame(3, $about->menu_order);
        $this->assertTrue($about->show_in_menu);
        $this->assertTrue($about->is_published);
        $this->assertGreaterThanOrEqual(1, $about->versions()->count());
        $this->get('/about')->assertOk();
    }

    public function test_deepseek_provider_supports_latest_models_and_generates_pages(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.ai.update'), [
            'ai_provider' => 'deepseek',
            'ai_api_key' => 'ds-secret-key-9999',
            'ai_model' => '',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->get(route('admin.settings.ai'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('aiConfigured', true)
                ->where('values.ai_provider', 'deepseek')
                ->where('aiProviders.deepseek.default_model', 'deepseek-flash')
                ->where('aiProviders.deepseek.models', ['deepseek-flash', 'deepseek-v4-pro']));

        Http::fake([
            'https://api.deepseek.com/chat/completions' => Http::sequence()
                ->push([
                    'choices' => [[
                        'message' => ['content' => 'OK'],
                    ]],
                ], 200)
                ->push([
                    'choices' => [[
                        'message' => [
                            'content' => json_encode([
                                'brand' => ['site_name' => 'Verve Cloud'],
                                'pages' => [[
                                    'title' => 'Pricing',
                                    'slug' => 'pricing',
                                    'sections' => [
                                        ['type' => 'hero', 'heading' => 'Simple transparent pricing'],
                                        ['type' => 'pricing', 'heading' => 'Plans', 'plans' => [
                                            ['name' => 'Starter', 'price' => '$19', 'interval' => 'month', 'features' => ['1 store']],
                                        ]],
                                    ],
                                ]],
                            ]),
                        ],
                    ]],
                ], 200),
        ]);

        $this->actingAs($admin)->postJson(route('admin.settings.ai.test'), [
            'ai_provider' => 'deepseek',
            'ai_model' => 'deepseek-v4-pro',
        ])->assertOk()->assertJsonPath('ok', true);

        $plan = $this->actingAs($admin)->postJson(route('admin.ai.generate'), [
            'prompt' => 'Pricing landing page for Verve Cloud SaaS.',
            'mode' => 'single',
            'page_title' => 'Pricing',
        ])->assertOk()->json('data');

        $this->assertCount(1, $plan['pages']);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.deepseek.com/chat/completions'
            && $request['model'] === 'deepseek-flash'
            && ($request['response_format']['type'] ?? null) === 'json_object'
            && ($request['max_tokens'] ?? null) === 8192);
    }

    public function test_builder_can_modify_full_page_with_ai_and_keep_document_editable(): void
    {
        $admin = $this->admin();

        app(Settings::class)->set([
            'ai_provider' => 'openai',
            'ai_api_key' => 'sk-builder-modify-test',
            'ai_model' => 'gpt-4o-mini',
        ]);

        $page = Page::factory()->create([
            'title' => 'Landing Page',
            'slug' => 'landing-page',
            'editor' => Page::EDITOR_BUILDER,
            'version' => 1,
            'is_published' => false,
            'document' => ['schema_version' => 1, 'settings' => [], 'sections' => []],
        ]);

        $unsavedCanvasDoc = [
            'schema_version' => 1,
            'settings' => [],
            'sections' => [[
                'id' => 'sec_unsaved_1',
                'name' => 'Unsaved Draft Hero',
                'width' => 'boxed',
                'style' => [],
                'visibility' => ['desktop' => true, 'tablet' => true, 'mobile' => true],
                'rows' => [[
                    'id' => 'row_1',
                    'style' => [],
                    'visibility' => ['desktop' => true, 'tablet' => true, 'mobile' => true],
                    'columns' => [[
                        'id' => 'col_1',
                        'span' => ['desktop' => 12, 'tablet' => 12, 'mobile' => 12],
                        'style' => [],
                        'visibility' => ['desktop' => true, 'tablet' => true, 'mobile' => true],
                        'blocks' => [[
                            'id' => 'blk_1',
                            'type' => 'heading',
                            'props' => ['text' => 'Unsaved Headline In Canvas', 'level' => 'h1'],
                            'style' => [],
                            'visibility' => ['desktop' => true, 'tablet' => true, 'mobile' => true],
                        ]],
                    ]],
                ]],
            ]],
        ];

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'brand' => ['site_name' => 'Modrix Studio'],
                            'pages' => [[
                                'title' => 'Landing Page',
                                'slug' => 'landing-page',
                                'sections' => [
                                    ['type' => 'hero', 'heading' => 'Redesigned Full Page Hero', 'subheading' => 'Built with editable blocks'],
                                    ['type' => 'features', 'heading' => 'Why choose us', 'items' => [
                                        ['icon' => 'zap', 'title' => 'Editable', 'body' => 'Every block opens in the inspector'],
                                    ]],
                                    ['type' => 'faq', 'heading' => 'Questions', 'faqs' => [
                                        ['question' => 'Can I still edit blocks?', 'answer' => 'Yes, every section uses native builder nodes.'],
                                    ]],
                                ],
                            ]],
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.ai.generate'), [
            'prompt' => 'Upgrade this full page with a sharper hero, features section, and FAQ.',
            'mode' => 'redesign',
            'target_page_id' => $page->id,
            'current_document' => $unsavedCanvasDoc,
            'tone' => 'Modern & clear',
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            $userMessage = (string) ($request['messages'][1]['content'] ?? '');

            return str_contains($userMessage, 'Unsaved Draft Hero')
                && str_contains($userMessage, 'Unsaved Headline In Canvas');
        });

        $generatedDoc = $response->json('data.pages.0.document');
        $this->assertCount(3, $generatedDoc['sections']);
        $this->assertNotEmpty($generatedDoc['sections'][0]['rows'][0]['columns'][0]['blocks']);

        // Simulate a manual tweak in the builder before saving via the builder API.
        $generatedDoc['sections'][0]['rows'][0]['columns'][0]['blocks'][0]['props']['text'] = 'TWEAKED IN BUILDER';

        $this->actingAs($admin)
            ->putJson("/api/v1/pages/{$page->id}", [
                'version' => $page->version,
                'document' => $generatedDoc,
            ])
            ->assertOk()
            ->assertJsonPath('data.version', 2);

        $page->refresh();
        $this->assertSame('TWEAKED IN BUILDER', $page->document['sections'][0]['rows'][0]['columns'][0]['blocks'][0]['props']['text']);
    }
}
