<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Cms\Builder\Schema\Collections\VertexDesigns;
use App\Cms\Builder\Schema\DesignBlockLibrary;
use App\Cms\Builder\Schema\DocumentValidator;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VertexTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_all_vertex_design_blocks_validate_follow_theme_tokens_and_surface_in_library(): void
    {
        $blocks = (new VertexDesigns)->all();
        $this->assertCount(14, $blocks);

        $validator = new DocumentValidator;
        $library = app(DesignBlockLibrary::class);

        foreach ($blocks as $entry) {
            $this->assertStringStartsWith('Vertex · ', $entry['name']);
            $validator->validate($entry['document']);
            $this->assertNotNull($library->find($entry['key']));

            $serialized = json_encode($entry['document'], JSON_UNESCAPED_SLASHES);
            $this->assertIsString($serialized);
            $this->assertStringContainsString('var(--site-', $serialized);
            $this->assertStringContainsString('"designCollection":"vertex"', $serialized);
            $this->assertStringNotContainsStringIgnoringCase('vmware', $serialized);
            $this->assertStringNotContainsStringIgnoringCase('broadcom', $serialized);

            // The copy stays plain: no counts, versions or claims that cannot be checked.
            foreach (['55 module', '16-domain', '16 core domains', '45+', '21+', 'v1.2', 'mena', 'sovereign', 'inertia v3', 'mysql 8.4'] as $claim) {
                $this->assertStringNotContainsStringIgnoringCase($claim, $serialized, "Unverified claim \"{$claim}\" in {$entry['key']}");
            }
        }

        $keys = array_column(
            $this->actingAs($this->admin())->getJson(route('builder.designs.index'))->assertOk()->json('data'),
            'key',
        );

        foreach ([
            'vertex-header',
            'vertex-footer',
            'vertex-hero',
            'vertex-announcement',
            'vertex-spotlight',
            'vertex-solutions',
            'vertex-trending',
            'vertex-cta-band',
            'vertex-page-hero',
            'vertex-domains',
            'vertex-modules',
            'vertex-solutions-tracks',
            'vertex-cli-quickstart',
            'vertex-demo-import',
        ] as $key) {
            $this->assertContains($key, $keys);
        }
    }

    public function test_all_vertex_page_templates_list_and_install_cleanly(): void
    {
        $admin = $this->admin();

        $templates = $this->actingAs($admin)
            ->getJson(route('builder.templates.index', ['type' => 'page', 'category' => 'enterprise', 'per_page' => 100]))
            ->assertOk()
            ->json('data');

        $vertexTemplates = array_values(array_filter(
            $templates,
            fn (array $row): bool => str_starts_with((string) ($row['id'] ?? ''), 'vertex-'),
        ));

        $this->assertCount(6, $vertexTemplates);

        foreach ($vertexTemplates as $template) {
            $this->assertSame('Enterprise commerce', $template['category_label']);
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
