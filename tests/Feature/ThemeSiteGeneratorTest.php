<?php

namespace Tests\Feature;

use App\Cms\Builder\Schema\DocumentValidator;
use App\Models\Page;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithCms;
use Tests\TestCase;

class ThemeSiteGeneratorTest extends TestCase
{
    use InteractsWithCms, RefreshDatabase;

    private function reply(array $json): array
    {
        return ['choices' => [['message' => ['content' => json_encode($json)]]]];
    }

    /** @return list<array<string, mixed>> every block in a document, nested ones included */
    private function blocks(array $document): array
    {
        $blocks = [];
        $collect = function (array $block) use (&$collect, &$blocks): void {
            $blocks[] = $block;
            foreach ($block['children'] ?? [] as $child) {
                $collect($child);
            }
        };
        foreach ($document['sections'] as $section) {
            foreach ($section['rows'] as $row) {
                foreach ($row['columns'] as $column) {
                    foreach ($column['blocks'] as $block) {
                        $collect($block);
                    }
                }
            }
        }

        return $blocks;
    }

    private function setUpLumikinWithAi(): void
    {
        $this->confirmedAdmin();
        $this->post(route('admin.themes.activate', ['modrix', 'lumikin']), ['trust' => 1])->assertSessionHasNoErrors();
        app(Settings::class)->set(['ai_provider' => 'openai', 'ai_api_key' => 'sk-test', 'ai_model' => 'gpt-4o-mini']);
    }

    public function test_the_workspace_offers_the_active_themes_designs(): void
    {
        $this->setUpLumikinWithAi();

        $this->get(route('admin.ai.index'))->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('themeKit.label', 'Lumikin')
                ->where('themeKit.pages', ['Home', 'About', 'Programs', 'Fees', 'Blog', 'Contact']));

        app(Settings::class)->set(['active_theme' => 'modrix/default']);
        $this->get(route('admin.ai.index'))->assertInertia(fn ($page) => $page->where('themeKit', null));
    }

    public function test_a_full_site_is_planned_written_and_saved_in_the_theme_designs(): void
    {
        $this->setUpLumikinWithAi();

        Http::fake(['https://api.openai.com/*' => Http::sequence()
            ->push($this->reply([
                'brand' => ['site_name' => 'RoastCraft', 'tagline' => 'Fresh coffee, roasted weekly.', 'email' => 'hi@roastcraft.test', 'phone' => '+1 555 0100', 'address' => '1 Bean St'],
                'cta_slug' => 'contact',
                'pages' => [
                    ['title' => 'Home', 'slug' => 'home', 'sections' => ['home.0', 'home.3', 'not-a-section', 'home.0']],
                    ['title' => 'Pricing', 'slug' => 'pricing', 'nav_label' => 'Plans', 'sections' => ['fees.0', 'fees.1']],
                    ['title' => 'Contact', 'slug' => 'contact', 'sections' => []],
                ],
            ]))
            ->push($this->reply(['texts' => ['t1' => 'FRESHLY ROASTED', 't2' => "Better mornings\nstart with Lumikin beans"]]))
            ->push($this->reply(['texts' => []]))]);

        $plan = $this->postJson(route('admin.ai.site.plan'), ['prompt' => 'A small-batch coffee roastery with subscriptions.', 'pages' => ['Home', 'Pricing', 'Contact']])
            ->assertOk()->json('data');

        $this->assertSame(['home', 'pricing', 'contact'], array_column($plan['pages'], 'slug'));
        $this->assertSame(['home.0', 'home.3'], $plan['pages'][0]['section_ids'], 'unknown and repeated sections are dropped');
        $this->assertNotEmpty($plan['pages'][2]['section_ids'], 'a page with no valid sections falls back to the closest theme page');
        $this->assertSame('Lumikin', $plan['theme']['label']);

        $site = ['brand' => $plan['brand'], 'theme' => $plan['theme'], 'prompt' => $plan['prompt'], 'tone' => $plan['tone'], 'cta_slug' => $plan['cta_slug'], 'pages' => $plan['pages']];

        $home = $this->postJson(route('admin.ai.site.page'), ['site' => $site, 'page' => $plan['pages'][0]])->assertOk()->json('data');
        $this->assertTrue($home['written']);
        $this->assertSame([], app(DocumentValidator::class)->errorsFor($home['document']));

        $texts = array_map(fn (array $block) => $block['props']['text'] ?? $block['props']['label'] ?? null, $this->blocks($home['document']));
        $this->assertContains('FRESHLY ROASTED', $texts);
        $this->assertContains("Better mornings\nstart with RoastCraft beans", $texts, 'the old theme brand is replaced');

        $urls = array_filter(array_map(fn (array $block) => $block['type'] === 'button' ? $block['props']['url'] : null, $this->blocks($home['document'])));
        $this->assertContains('/contact', $urls);
        $this->assertNotContains('/programs', $urls, 'links to pages that do not exist go to the contact page');

        $chrome = $this->postJson(route('admin.ai.site.chrome'), ['site' => $site, 'header_document' => $plan['header_document'], 'footer_document' => $plan['footer_document']])
            ->assertOk()->json('data');

        $headerBlocks = $this->blocks($chrome['header_document']);
        $nav = collect($headerBlocks)->firstWhere('type', 'navigation');
        $this->assertSame([['label' => 'Home', 'url' => '/'], ['label' => 'Plans', 'url' => '/pricing'], ['label' => 'Contact', 'url' => '/contact']], $nav['props']['items']);
        $logo = collect($headerBlocks)->firstWhere('type', 'logo');
        $this->assertNull($logo['props']['src']);
        $this->assertSame('RoastCraft', $logo['props']['alt']);
        $this->assertStringContainsString('mailto:hi@roastcraft.test', json_encode($chrome['footer_document'], JSON_UNESCAPED_SLASHES));

        $plan['pages'][0] = $home;
        $this->postJson(route('admin.ai.apply'), ['plan' => [...$plan, ...$chrome]])->assertOk();

        $saved = Page::query()->regular()->where('slug', 'pricing')->firstOrFail();
        $this->assertTrue($saved->is_published);
        $header = Page::query()->where('role', Page::ROLE_HEADER)->where('theme', 'modrix/lumikin')->firstOrFail();
        $this->assertStringContainsString('RoastCraft', json_encode($header->published_document));
        $this->assertSame('RoastCraft', app(Settings::class)->get('site_name'));
        $this->assertSame('hi@roastcraft.test', app(Settings::class)->get('contact_email'));
    }

    public function test_the_site_steps_need_a_theme_with_designs(): void
    {
        $this->confirmedAdmin();
        app(Settings::class)->set(['ai_provider' => 'openai', 'ai_api_key' => 'sk-test']);

        $this->postJson(route('admin.ai.site.plan'), ['prompt' => 'A small-batch coffee roastery.'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The active theme has no demo designs to build with.');
    }
}
