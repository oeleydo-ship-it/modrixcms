<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Cms\Builder\Schema\Collections\LarkspurDesigns;
use App\Cms\Builder\Schema\DesignBlockLibrary;
use App\Cms\Builder\Schema\DocumentValidator;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LarkspurTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_all_larkspur_design_blocks_validate_follow_theme_tokens_and_surface_in_library(): void
    {
        $blocks = (new LarkspurDesigns)->all();
        $this->assertGreaterThanOrEqual(20, count($blocks));

        $validator = new DocumentValidator;
        $library = app(DesignBlockLibrary::class);

        foreach ($blocks as $entry) {
            $this->assertStringStartsWith('Larkspur · ', $entry['name']);
            $validator->validate($entry['document']);
            $this->assertNotNull($library->find($entry['key']));

            $serialized = json_encode($entry['document'], JSON_UNESCAPED_SLASHES);
            $this->assertIsString($serialized);
            $this->assertStringContainsString('var(--site-', $serialized);
            $this->assertStringContainsString('"designCollection":"larkspur"', $serialized);
            $this->assertStringNotContainsStringIgnoringCase('housecall', $serialized);
        }

        $keys = array_column(
            $this->actingAs($this->admin())->getJson(route('builder.designs.index'))->assertOk()->json('data'),
            'key',
        );

        foreach (['larkspur-header', 'larkspur-hero', 'larkspur-marquee', 'larkspur-visit-types', 'larkspur-treat', 'larkspur-clinics', 'larkspur-providers', 'larkspur-coverage', 'larkspur-reviews', 'larkspur-faq', 'larkspur-book-band', 'larkspur-footer', 'larkspur-stats', 'larkspur-services-grid', 'larkspur-steps', 'larkspur-spotlight', 'larkspur-hours', 'larkspur-contact', 'larkspur-cta-banner', 'larkspur-health-notes', 'larkspur-services-catalog', 'larkspur-wide-photo', 'larkspur-service-hero', 'larkspur-service-details'] as $key) {
            $this->assertContains($key, $keys);
        }
    }

    public function test_all_larkspur_page_templates_list_and_install_cleanly(): void
    {
        $admin = $this->admin();

        $templates = $this->actingAs($admin)
            ->getJson(route('builder.templates.index', ['type' => 'page', 'per_page' => 80]))
            ->assertOk()
            ->json('data');

        $larkspurTemplates = array_values(array_filter(
            $templates,
            fn (array $row): bool => str_starts_with((string) ($row['id'] ?? ''), 'larkspur-'),
        ));

        $this->assertCount(8, $larkspurTemplates);

        foreach ($larkspurTemplates as $template) {
            $this->assertSame('Healthcare & clinics', $template['category_label']);
            $page = Page::factory()->create(['editor' => Page::EDITOR_BUILDER, 'blocks' => []]);

            $this->actingAs($admin)
                ->postJson(route('builder.templates.install', ['template' => $template['id']]), [
                    'page_id' => $page->id,
                    'expected_version' => 1,
                ])
                ->assertOk();

            $this->assertNotEmpty($page->fresh()->document['sections']);
        }
    }
}
