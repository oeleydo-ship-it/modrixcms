<?php

namespace Tests\Feature;

use App\Models\Page;
use App\PageBuilder\PageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_a_home_page_the_default_landing_page_shows(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInPage('Your store.')
            ->assertBlockAnchors(['features', 'marketplace', 'deploy', 'open-source']);
    }

    public function test_the_landing_page_lists_the_core_features(): void
    {
        $response = $this->get('/');

        $features = collect(PageBuilder::landingBlocks())->firstWhere('type', 'features');
        foreach ($features['data']['items'] as $card) {
            $response->assertSeeInPage($card['title']);
        }

        $response->assertSeeInPage('Laravel 13')->assertSeeInPage('MySQL 8');
    }

    public function test_the_home_page_is_built_from_its_blocks(): void
    {
        Page::factory()->home()->create([
            'blocks' => [
                PageBuilder::make('hero', ['title' => 'Custom headline', 'highlight' => '', 'show_mockup' => false]),
                ['id' => 'hidden1', 'type' => 'cta', 'hidden' => true, 'data' => PageBuilder::make('cta', ['title' => 'Hidden banner'])['data']],
            ],
        ]);

        $this->get('/')->assertOk()->assertSeeInPage('Custom headline')->assertDontSeeInPage('Hidden banner')->assertBlockAnchors([], ['features']);
    }

    public function test_the_home_page_slug_redirects_to_the_root(): void
    {
        Page::factory()->home()->create();

        $this->get('/home')->assertRedirect(route('home'));
    }

    public function test_button_links_follow_the_settings(): void
    {
        config([
            'site.links.github' => 'https://example.test/repo',
            'site.links.marketplace' => 'https://example.test/market',
        ]);

        $this->get('/')
            ->assertSeeInPage('https://example.test/repo')
            ->assertSeeInPage('https://example.test/market');
    }
}
