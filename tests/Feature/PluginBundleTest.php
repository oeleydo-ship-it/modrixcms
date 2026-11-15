<?php

namespace Tests\Feature;

use App\Cms\Extensions\CodeScanner;
use App\Cms\Extensions\Manifest;
use App\Cms\Extensions\PackageInspector;
use App\Cms\PluginLoader;
use App\Models\Extension;
use App\Models\Page;
use App\Models\Post;
use App\PageBuilder\PageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modrix\Faq\Models\FaqItem;
use Modrix\FormBuilder\FormSchema;
use Modrix\FormBuilder\Models\Form;
use Modrix\FormBuilder\Models\Submission;
use Modrix\Newsletter\Models\Campaign;
use Modrix\Newsletter\Models\Subscriber;
use Modrix\PromoBars\Models\PromoCampaign;
use Modrix\SeoAnalytics\Models\SeoPageView;
use Modrix\SeoAnalytics\Models\SeoRedirect;
use Modrix\SocialMedia\Models\SocialProfile;
use Modrix\Testimonials\Models\Testimonial;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class PluginBundleTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function bundlePlugins(): array
    {
        return [
            'form-builder' => ['modrix/form-builder', 'fb_forms'],
            'social-media' => ['modrix/social-media', 'social_profiles'],
            'newsletter' => ['modrix/newsletter', 'nl_subscribers'],
            'testimonials' => ['modrix/testimonials', 'testimonials'],
            'faq' => ['modrix/faq', 'faq_items'],
            'seo-analytics' => ['modrix/seo-analytics', 'seo_page_views'],
            'promo-bars' => ['modrix/promo-bars', 'promo_campaigns'],
        ];
    }

    #[DataProvider('bundlePlugins')]
    public function test_bundle_plugin_passes_manifest_inspector_code_scanner_and_installs(string $name, string $table): void
    {
        $path = base_path('extensions/plugins/'.$name);

        $manifest = Manifest::load($path, Extension::PLUGIN);
        $this->assertSame($name, $manifest->data['name']);
        $this->assertSame([], app(CodeScanner::class)->scan($path));

        $zip = $this->zipFolder('plugins/'.$name);
        [$extractedManifest] = app(PackageInspector::class)->extract($zip->getRealPath(), Extension::PLUGIN);
        $this->assertSame($name, $extractedManifest->data['name']);

        $this->confirmedAdmin();
        [$vendor, $package] = explode('/', $name);

        $this->post(route('admin.plugins.enable', [$vendor, $package]), ['trust' => 1])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Schema::hasTable($table));
        $this->assertTrue(Extension::query()->where('name', $name)->value('enabled'));

        $this->delete(route('admin.plugins.destroy', [$vendor, $package]), ['delete_data' => 1])
            ->assertRedirect(route('admin.plugins.index'));

        $this->assertFalse(Schema::hasTable($table));
    }

    public function test_form_builder_supports_conditional_validation_spam_detection_duplication_and_csv_export(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.plugins.enable', ['modrix', 'form-builder']), ['trust' => 1])->assertSessionHasNoErrors();
        $this->loadPluginRoutes();

        $fields = [
            [
                'id' => 'f1',
                'type' => 'text',
                'key' => 'full_name',
                'label' => 'Full name',
                'required' => true,
                'width' => 'half',
            ],
            [
                'id' => 'f2',
                'type' => 'select',
                'key' => 'topic',
                'label' => 'Topic',
                'required' => true,
                'options' => ['Support', 'Enterprise'],
            ],
            [
                'id' => 'f3',
                'type' => 'text',
                'key' => 'company_size',
                'label' => 'Company size',
                'required' => true,
                'condition' => ['field' => 'topic', 'operator' => 'equals', 'value' => 'Enterprise'],
            ],
        ];

        $this->post(route('plugin.modrix.form-builder.forms.store'), [
            'name' => '',
            'slug' => '',
            'status' => 'active',
            'fields' => json_encode($fields),
            'settings' => json_encode([]),
        ])->assertSessionHasErrors([
            'name' => 'Please enter a form name at the top-left of the studio before saving.',
        ]);

        $this->post(route('plugin.modrix.form-builder.forms.store'), [
            'name' => 'Lead Intake',
            'slug' => 'lead-intake',
            'description' => 'Qualify incoming leads',
            'status' => 'active',
            'fields' => json_encode($fields),
            'settings' => json_encode([
                'submit_label' => 'Send Request',
                'success_message' => 'Received!',
                'store_submissions' => true,
                'honeypot' => true,
            ]),
        ])->assertRedirect();

        $form = Form::query()->where('slug', 'lead-intake')->firstOrFail();

        Page::factory()->create([
            'slug' => 'quote',
            'blocks' => [PageBuilder::make('modrix/form-builder:form', ['form_slug' => 'lead-intake'])],
        ]);

        $this->get('/quote')->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('blocks.0.type', 'modrix/form-builder:form')
                ->where('blocks.0.data.form.slug', 'lead-intake'));

        // Conditional field hidden when topic=Support -> passes without company_size.
        $this->from('/quote')->post(route('plugin.modrix.form-builder.submit', 'lead-intake'), [
            'full_name' => 'Ada Lovelace',
            'topic' => 'Support',
        ])->assertRedirect('/quote#form');

        $this->assertDatabaseHas('fb_submissions', [
            'form_id' => $form->id,
            'status' => 'new',
        ]);

        // Conditional field visible when topic=Enterprise -> required validation triggers.
        $this->from('/quote')->post(route('plugin.modrix.form-builder.submit', 'lead-intake'), [
            'full_name' => 'Grace Hopper',
            'topic' => 'Enterprise',
        ])->assertSessionHasErrors('company_size', null, 'formBuilder_lead-intake');

        // Configure spam filter keyword, reload plugin runtime cache, and test spam tagging.
        $this->put(route('admin.plugins.settings.update', ['modrix', 'form-builder']), [
            'spam_keywords' => 'viagra,casino',
        ])->assertSessionHasNoErrors();
        app()->forgetInstance(PluginLoader::class);
        app(PluginLoader::class)->load();

        $this->from('/quote')->post(route('plugin.modrix.form-builder.submit', 'lead-intake'), [
            'full_name' => 'Buy cheap viagra http://a.test http://b.test http://c.test',
            'topic' => 'Support',
        ])->assertRedirect('/quote#form');

        $this->assertDatabaseHas('fb_submissions', [
            'form_id' => $form->id,
            'status' => 'spam',
        ]);

        $this->post(route('plugin.modrix.form-builder.forms.duplicate', $form))->assertRedirect();
        $this->assertSame(2, Form::query()->count());

        $export = $this->get(route('plugin.modrix.form-builder.submissions.export', ['form_id' => $form->id]));
        $export->assertOk();
        $content = $export->streamedContent();
        $this->assertStringContainsString('Ada Lovelace', $content);
        $this->assertSame(2, Submission::query()->where('form_id', $form->id)->count());

        // Enhanced schema normalization: new field types, widths, bounds, operators, and design settings.
        $cleanedFields = FormSchema::cleanFields([
            ['type' => 'time', 'label' => 'Callback time', 'key' => 'callback_time', 'width' => 'third'],
            ['type' => 'consent', 'label' => 'Marketing opt-in', 'key' => 'consent', 'required' => true],
            ['type' => 'paragraph', 'label' => 'Intro', 'placeholder' => 'We reply within 1 day.'],
            ['type' => 'divider', 'label' => ''],
            [
                'type' => 'rating',
                'label' => 'Score',
                'key' => 'score',
                'max_stars' => 7,
                'condition' => ['field' => 'callback_time', 'operator' => 'is_empty', 'value' => ''],
            ],
            [
                'type' => 'text',
                'label' => 'Promo code',
                'key' => 'promo',
                'min_length' => 4,
                'max_length' => 12,
                'condition' => ['field' => 'score', 'operator' => 'greater_than', 'value' => '3'],
            ],
        ]);
        $this->assertSame('third', $cleanedFields[0]['width']);
        $this->assertSame(7, $cleanedFields[4]['max_stars']);
        $this->assertSame('is_empty', $cleanedFields[4]['condition']['operator']);
        $this->assertSame('greater_than', $cleanedFields[5]['condition']['operator']);
        $this->assertSame(4, $cleanedFields[5]['min_length']);
        $this->assertSame(12, $cleanedFields[5]['max_length']);

        $cleanedSettings = FormSchema::cleanSettings([
            'accent_color' => '#2563eb',
            'card_style' => 'bordered',
            'border_radius' => 'pill',
            'spacing' => 'spacious',
            'button_align' => 'full',
        ]);
        $this->assertSame('#2563eb', $cleanedSettings['accent_color']);
        $this->assertSame('bordered', $cleanedSettings['card_style']);
        $this->assertSame('pill', $cleanedSettings['border_radius']);
        $this->assertSame('spacious', $cleanedSettings['spacing']);
        $this->assertSame('full', $cleanedSettings['button_align']);

        // Form status toggle and bulk submission actions.
        $this->post(route('plugin.modrix.form-builder.forms.toggle-status', $form))->assertRedirect();
        $this->assertSame('paused', $form->fresh()->status);
        $this->post(route('plugin.modrix.form-builder.forms.toggle-status', $form))->assertRedirect();
        $this->assertSame('active', $form->fresh()->status);

        $submissionIds = Submission::query()->where('form_id', $form->id)->pluck('id')->all();
        $this->post(route('plugin.modrix.form-builder.submissions.bulk'), [
            'ids' => $submissionIds,
            'action' => 'spam',
        ])->assertRedirect();
        $this->assertSame(2, Submission::query()->where('form_id', $form->id)->where('status', 'spam')->count());

        $this->post(route('plugin.modrix.form-builder.submissions.bulk'), [
            'ids' => [$submissionIds[0]],
            'action' => 'delete',
        ])->assertRedirect();
        $this->assertSame(1, Submission::query()->where('form_id', $form->id)->count());

        $this->get(route('plugin.modrix.form-builder.forms.edit', $form))
            ->assertOk()
            ->assertSee('form-builder.css', false)
            ->assertSee('form-builder-admin.css', false);

        // Visual Builder Form API: list, add phone field, rename label & key, capture submission, publish runtime, and delete field.
        $apiForm = Form::query()->create([
            'name' => 'Quick Phone Lead',
            'slug' => 'quick-phone-lead',
            'status' => 'active',
            'fields' => [],
            'settings' => FormSchema::cleanSettings([
                'store_submissions' => true,
                'honeypot' => true,
            ]),
        ]);

        $this->getJson(route('builder.forms.index'))
            ->assertOk()
            ->assertJsonFragment(['id' => (string) $apiForm->id, 'name' => 'Quick Phone Lead']);

        $added = $this->postJson(route('builder.forms.fields.store', ['form' => $apiForm->id]), [
            'type' => 'phone',
            'label' => 'Phone number',
            'name' => 'phone',
            'is_required' => true,
        ])->assertCreated();

        $phoneFieldId = $added->json('data.id');
        $this->assertNotEmpty($phoneFieldId);

        $this->patchJson(route('builder.form-fields.update', ['field' => $phoneFieldId]), [
            'label' => 'Direct Mobile',
            'name' => 'direct_mobile',
            'width' => 'half',
        ])
            ->assertOk()
            ->assertJsonPath('data.label', 'Direct Mobile')
            ->assertJsonPath('data.name', 'direct_mobile')
            ->assertJsonPath('data.width', 'half');

        $this->getJson(route('builder.forms.show', ['form' => $apiForm->id]))
            ->assertOk()
            ->assertJsonPath('data.fields.0.label', 'Direct Mobile')
            ->assertJsonPath('data.fields.0.name', 'direct_mobile');

        $this->postJson(route('forms.capture', ['form' => $apiForm->id]), [
            'direct_mobile' => '+1 555 234 5678',
            '_fb_trap' => '',
            '_ts' => time() - 5,
        ])->assertOk();

        $this->assertSame(1, Submission::query()->where('form_id', $apiForm->id)->count());

        $builderPage = Page::factory()->create([
            'editor' => Page::EDITOR_BUILDER,
            'title' => 'Contact Builder Page',
            'slug' => 'contact-builder-page',
            'is_published' => true,
            'published_document' => [
                'schema_version' => 2,
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
                                                'type' => 'form',
                                                'props' => ['formId' => (string) $apiForm->id],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->get(route('pages.show', $builderPage->slug))
            ->assertOk()
            ->assertSee('published-runtime', false)
            ->assertSee('Direct Mobile', false);

        $this->deleteJson(route('builder.form-fields.destroy', ['field' => $phoneFieldId]))
            ->assertNoContent();
        $this->assertCount(0, $apiForm->fresh()->fields ?? []);
    }

    public function test_social_media_tracks_profile_redirects_shares_and_og_head_tags(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.plugins.enable', ['modrix', 'social-media']), ['trust' => 1])->assertSessionHasNoErrors();
        $this->loadPluginRoutes();

        $this->post(route('plugin.modrix.social-media.profiles.store'), [
            'platform' => 'github',
            'label' => 'GitHub',
            'handle' => '@modrix',
            'url' => 'https://github.com/modrix',
            'followers' => '12.4K',
            'is_active' => true,
            'open_new_tab' => true,
        ])->assertRedirect();

        $profile = SocialProfile::query()->firstOrFail();

        $this->get(route('plugin.modrix.social-media.go', $profile))
            ->assertRedirect('https://github.com/modrix');

        $this->assertSame(1, $profile->fresh()->clicks_count);

        $this->postJson(route('plugin.modrix.social-media.track'), [
            'event_type' => 'share',
            'platform' => 'x',
            'page_url' => 'https://example.com/launch',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('social_events', [
            'event_type' => 'share',
            'platform' => 'x',
        ]);

        Page::factory()->create([
            'slug' => 'community',
            'blocks' => [PageBuilder::make('modrix/social-media:links'), PageBuilder::make('modrix/social-media:share')],
        ]);

        $this->get('/community')->assertOk()
            ->assertSee('twitter:card', false)
            ->assertInertia(fn ($page) => $page
                ->where('blocks.0.data.profiles.0.platform', 'github')
                ->where('socialMedia.trackUrl', route('plugin.modrix.social-media.track')));

        $this->get(route('plugin.modrix.social-media.analytics.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('plugin:modrix/social-media/AnalyticsIndex'));
    }

    public function test_newsletter_handles_double_opt_in_unsubscribe_and_campaign_broadcast(): void
    {
        Mail::fake();
        $this->confirmedAdmin();
        $this->post(route('admin.plugins.enable', ['modrix', 'newsletter']), ['trust' => 1])->assertSessionHasNoErrors();
        $this->loadPluginRoutes();

        $this->put(route('admin.plugins.settings.update', ['modrix', 'newsletter']), [
            'double_opt_in' => true,
            'send_welcome_email' => true,
            'success_message' => 'Subscribed!',
            'reply_to' => 'news@example.com',
        ])->assertSessionHasNoErrors();
        app()->forgetInstance(PluginLoader::class);
        app(PluginLoader::class)->load();

        Page::factory()->create([
            'slug' => 'updates',
            'blocks' => [PageBuilder::make('modrix/newsletter:signup', ['tag' => 'launch'])],
        ]);

        $this->from('/updates')->post(route('plugin.modrix.newsletter.subscribe'), [
            'email' => 'reader@example.com',
            'name' => 'Reader',
            'tag' => 'launch',
        ])->assertRedirect('/updates#newsletter');

        $subscriber = Subscriber::query()->where('email', 'reader@example.com')->firstOrFail();
        $this->assertSame('pending', $subscriber->status);
        $this->assertSame(['launch'], $subscriber->tags);

        $this->get(route('plugin.modrix.newsletter.confirm', $subscriber->token))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('plugin:modrix/newsletter/Status'));

        $this->assertSame('subscribed', $subscriber->fresh()->status);

        $this->post(route('plugin.modrix.newsletter.campaigns.store'), [
            'subject' => 'Release Notes 1.0',
            'preview_text' => 'Everything new in 1.0',
            'body_markdown' => "# Release 1.0\n\nThank you for following along.",
            'target_tag' => 'launch',
        ])->assertRedirect();

        $campaign = Campaign::query()->firstOrFail();
        $this->post(route('plugin.modrix.newsletter.campaigns.send', $campaign))->assertRedirect();

        $this->assertSame('sent', $campaign->fresh()->status);
        $this->assertSame(1, $campaign->fresh()->recipients_count);

        $this->get(route('plugin.modrix.newsletter.unsubscribe', $subscriber->token))->assertOk();
        $this->assertSame('unsubscribed', $subscriber->fresh()->status);
    }

    public function test_testimonials_moderates_reviews_and_outputs_aggregate_rating_schema(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.plugins.enable', ['modrix', 'testimonials']), ['trust' => 1])->assertSessionHasNoErrors();
        $this->loadPluginRoutes();

        Page::factory()->create([
            'slug' => 'reviews',
            'blocks' => [
                PageBuilder::make('modrix/testimonials:submit'),
                PageBuilder::make('modrix/testimonials:showcase'),
            ],
        ]);

        $this->from('/reviews')->post(route('plugin.modrix.testimonials.submit'), [
            'author_name' => 'Linus T.',
            'company' => 'Kernel Co',
            'rating' => 5,
            'quote' => 'Fastest headless commerce setup we have deployed.',
        ])->assertRedirect('/reviews#leave-review');

        $item = Testimonial::query()->firstOrFail();
        $this->assertSame('pending', $item->status);

        $this->put(route('plugin.modrix.testimonials.items.update', $item), [
            'author_name' => $item->author_name,
            'rating' => 5,
            'quote' => $item->quote,
            'status' => 'approved',
            'verified' => true,
            'is_featured' => true,
        ])->assertRedirect();

        $this->get('/reviews')->assertOk()
            ->assertSee('AggregateRating', false)
            ->assertInertia(fn ($page) => $page->where('blocks.1.data.items.0.author_name', 'Linus T.'));
    }

    public function test_faq_manages_items_records_helpfulness_votes_and_outputs_faq_schema(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.plugins.enable', ['modrix', 'faq']), ['trust' => 1])->assertSessionHasNoErrors();
        $this->loadPluginRoutes();

        $this->post(route('plugin.modrix.faq.items.store'), [
            'category' => 'Shipping',
            'question' => 'Do you ship internationally?',
            'answer_markdown' => 'Yes, we ship to **120+ countries** worldwide.',
            'is_published' => true,
        ])->assertRedirect();

        $faq = FaqItem::query()->firstOrFail();

        $this->postJson(route('plugin.modrix.faq.vote', $faq), ['vote' => 'yes'])
            ->assertOk()
            ->assertJson(['helpful_yes' => 1, 'helpful_no' => 0]);

        Page::factory()->create([
            'slug' => 'help',
            'blocks' => [PageBuilder::make('modrix/faq:accordion')],
        ]);

        $this->get('/help')->assertOk()
            ->assertSee('FAQPage', false)
            ->assertInertia(fn ($page) => $page->where('blocks.0.data.items.0.question', 'Do you ship internationally?'));
    }

    public function test_seo_analytics_records_hits_runs_redirects_and_generates_sitemap(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.plugins.enable', ['modrix', 'seo-analytics']), ['trust' => 1])->assertSessionHasNoErrors();
        $this->loadPluginRoutes();

        Page::factory()->create(['slug' => 'about', 'is_published' => true]);
        Post::factory()->create(['slug' => 'hello-world']);

        $this->postJson(route('plugin.modrix.seo-analytics.hit'), [
            'path' => '/about',
            'title' => 'About Us',
            'referrer' => 'https://google.com/search?q=modrix',
        ])->assertOk();

        // Admin views excluded by default when exclude_admins=true.
        $this->assertSame(0, SeoPageView::query()->count());

        auth()->logout();
        $this->postJson(route('plugin.modrix.seo-analytics.hit'), [
            'path' => '/about',
            'title' => 'About Us',
            'referrer' => 'https://google.com/search?q=modrix',
        ])->assertOk()->assertJson(['tracked' => true]);

        $this->assertDatabaseHas('seo_page_views', [
            'path' => '/about',
            'referrer_host' => 'google.com',
            'views' => 1,
        ]);

        $this->get(route('plugin.modrix.seo-analytics.sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('/about', false)
            ->assertSee('/blog/hello-world', false);

        $this->confirmedAdmin();
        $this->post(route('plugin.modrix.seo-analytics.redirects.store'), [
            'from_path' => '/legacy-pricing',
            'to_url' => '/about',
            'status_code' => '301',
            'is_active' => true,
        ])->assertRedirect();

        $this->loadPluginRoutes();

        $redirect = SeoRedirect::query()->firstOrFail();
        $this->get('/legacy-pricing')->assertRedirect('/about');
        $this->assertSame(1, $redirect->fresh()->hits);

        $this->get(route('plugin.modrix.seo-analytics.analytics.index'))->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('plugin:modrix/seo-analytics/AnalyticsIndex')
                ->where('summary.total_views', 1));

        $this->get(route('plugin.modrix.seo-analytics.seo.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('plugin:modrix/seo-analytics/SeoAuditIndex'));
    }

    public function test_promo_bars_manages_campaigns_tracks_clicks_and_shares_banner_block(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.plugins.enable', ['modrix', 'promo-bars']), ['trust' => 1])->assertSessionHasNoErrors();
        $this->loadPluginRoutes();

        $this->post(route('plugin.modrix.promo-bars.campaigns.store'), [
            'name' => 'Black Friday',
            'type' => 'bar',
            'title' => 'Save 30% sitewide',
            'badge_text' => 'BF30',
            'coupon_code' => 'BF30',
            'cta_label' => 'Shop Deals',
            'cta_url' => '/shop',
            'bg_color' => '#111827',
            'text_color' => '#ffffff',
            'accent_color' => '#f59e0b',
            'trigger_type' => 'immediate',
            'trigger_value' => 0,
            'is_active' => true,
        ])->assertRedirect();

        $campaign = PromoCampaign::query()->firstOrFail();

        $this->postJson(route('plugin.modrix.promo-bars.track', $campaign), ['event' => 'impression'])
            ->assertOk()->assertJson(['impressions' => 1]);
        $this->postJson(route('plugin.modrix.promo-bars.track', $campaign), ['event' => 'click'])
            ->assertOk()->assertJson(['clicks' => 1]);

        Page::factory()->create([
            'slug' => 'deals',
            'blocks' => [PageBuilder::make('modrix/promo-bars:banner', ['heading' => 'Flash Sale'])],
        ]);

        $this->get('/deals')->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('blocks.0.type', 'modrix/promo-bars:banner')
                ->where('promoBars.campaigns.0.coupon_code', 'BF30'));
    }
}
