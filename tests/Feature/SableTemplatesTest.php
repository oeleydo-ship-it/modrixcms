<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Cms\Builder\Schema\Collections\SableDesigns;
use App\Cms\Builder\Schema\DesignBlockLibrary;
use App\Cms\Builder\Schema\DocumentValidator;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SableTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_all_sable_design_blocks_validate_follow_theme_tokens_and_surface_in_library(): void
    {
        $blocks = (new SableDesigns)->all();
        $this->assertCount(14, $blocks);

        $validator = new DocumentValidator;
        $library = app(DesignBlockLibrary::class);

        foreach ($blocks as $entry) {
            $this->assertStringStartsWith('Sable · ', $entry['name']);
            $validator->validate($entry['document']);
            $this->assertNotNull($library->find($entry['key']));

            $serialized = json_encode($entry['document'], JSON_UNESCAPED_SLASHES);
            $this->assertIsString($serialized);
            $this->assertStringContainsString('var(--site-', $serialized);
            $this->assertStringContainsString('"designCollection":"sable"', $serialized);
            $this->assertStringNotContainsStringIgnoringCase('ninelabs', $serialized);
            $this->assertStringNotContainsStringIgnoringCase('webflow', $serialized);
        }

        $keys = array_column(
            $this->actingAs($this->admin())->getJson(route('builder.designs.index'))->assertOk()->json('data'),
            'key',
        );

        foreach ([
            'sable-header',
            'sable-footer',
            'sable-hero',
            'sable-services',
            'sable-process',
            'sable-stats',
            'sable-cases',
            'sable-testimonial',
            'sable-bento',
            'sable-faq',
            'sable-articles',
            'sable-cta-band',
            'sable-pricing',
            'sable-contact',
        ] as $key) {
            $this->assertContains($key, $keys);
        }
    }

    public function test_all_sable_page_templates_list_and_install_cleanly(): void
    {
        $admin = $this->admin();

        $templates = $this->actingAs($admin)
            ->getJson(route('builder.templates.index', ['type' => 'page', 'per_page' => 100]))
            ->assertOk()
            ->json('data');

        $sableTemplates = array_values(array_filter(
            $templates,
            fn (array $row): bool => str_starts_with((string) ($row['id'] ?? ''), 'sable-'),
        ));

        $this->assertCount(7, $sableTemplates);

        foreach ($sableTemplates as $template) {
            $this->assertSame('AI & automation studio', $template['category_label']);
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
