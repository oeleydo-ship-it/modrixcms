<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Cms\Builder\Schema\Collections\KairoDesigns;
use App\Cms\Builder\Schema\DesignBlockLibrary;
use App\Cms\Builder\Schema\DocumentValidator;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KairoTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_all_kairo_design_blocks_validate_use_theme_tokens_and_surface_in_library(): void
    {
        $collection = new KairoDesigns;
        $blocks = $collection->all();
        $this->assertGreaterThanOrEqual(16, count($blocks));

        $validator = new DocumentValidator;
        $library = app(DesignBlockLibrary::class);

        foreach ($blocks as $entry) {
            $this->assertStringStartsWith('Kairo · ', $entry['name']);
            $validator->validate($entry['document']);
            $this->assertNotNull($library->find($entry['key']));

            $serialized = json_encode($entry['document'], JSON_UNESCAPED_SLASHES);
            $this->assertIsString($serialized);
            $this->assertStringContainsString('var(--site-', $serialized);
            $this->assertStringNotContainsStringIgnoringCase('reako', $serialized);
            $this->assertStringNotContainsStringIgnoringCase('metaflow', $serialized);
            $this->assertDoesNotMatchRegularExpression('/"type":"(form|email_optin|phone_input|checkout|product|booking|calendar|popup_trigger)"/', $serialized);
        }

        $response = $this->actingAs($this->admin())
            ->getJson(route('builder.designs.index'))
            ->assertOk()
            ->json('data');

        $keys = array_column($response, 'key');
        $this->assertContains('kairo-header', $keys);
        $this->assertContains('kairo-hero', $keys);
        $this->assertContains('kairo-top-features', $keys);
        $this->assertContains('kairo-advantages', $keys);
        $this->assertContains('kairo-stages', $keys);
        $this->assertContains('kairo-why-us', $keys);
        $this->assertContains('kairo-testimonials', $keys);
        $this->assertContains('kairo-pricing', $keys);
        $this->assertContains('kairo-comparison', $keys);
        $this->assertContains('kairo-faq', $keys);
        $this->assertContains('kairo-cta-download', $keys);
        $this->assertContains('kairo-footer', $keys);
    }

    public function test_all_six_kairo_page_templates_list_and_install_cleanly(): void
    {
        $admin = $this->admin();

        $templates = $this->actingAs($admin)
            ->getJson(route('builder.templates.index', ['type' => 'page', 'per_page' => 60]))
            ->assertOk()
            ->json('data');

        $kairoTemplates = array_values(array_filter(
            $templates,
            fn (array $row): bool => str_starts_with((string) ($row['id'] ?? ''), 'kairo-'),
        ));

        $this->assertCount(6, $kairoTemplates);

        foreach ($kairoTemplates as $template) {
            $this->assertSame('SaaS & mobile app', $template['category_label']);
            $page = Page::factory()->create(['editor' => Page::EDITOR_BUILDER, 'blocks' => []]);

            $installed = $this->actingAs($admin)
                ->postJson(route('builder.templates.install', ['template' => $template['id']]), [
                    'page_id' => $page->id,
                    'expected_version' => 1,
                ])
                ->assertOk()
                ->json('data.page');

            $this->assertNotNull($installed);
            $this->assertSame(2, $installed['version']);
            $this->assertNotEmpty($page->fresh()->document['sections']);
        }
    }
}
