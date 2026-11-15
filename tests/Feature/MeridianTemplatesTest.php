<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Cms\Builder\Schema\Collections\MeridianDesigns;
use App\Cms\Builder\Schema\DesignBlockLibrary;
use App\Cms\Builder\Schema\DocumentValidator;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeridianTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_all_meridian_design_blocks_validate_use_theme_tokens_and_surface_in_library(): void
    {
        $collection = new MeridianDesigns;
        $blocks = $collection->all();
        $this->assertGreaterThanOrEqual(15, count($blocks));

        $validator = new DocumentValidator;
        $library = app(DesignBlockLibrary::class);

        foreach ($blocks as $entry) {
            $this->assertStringStartsWith('Meridian · ', $entry['name']);
            $validator->validate($entry['document']);
            $this->assertNotNull($library->find($entry['key']));

            $serialized = json_encode($entry['document'], JSON_UNESCAPED_SLASHES);
            $this->assertIsString($serialized);
            $this->assertStringContainsString('var(--site-', $serialized);
            $this->assertStringNotContainsStringIgnoringCase('viselio', $serialized);
            $this->assertDoesNotMatchRegularExpression('/"type":"(form|email_optin|phone_input|checkout|product|booking|calendar|popup_trigger)"/', $serialized);
        }

        $response = $this->actingAs($this->admin())
            ->getJson(route('builder.designs.index'))
            ->assertOk()
            ->json('data');

        $keys = array_column($response, 'key');
        $this->assertContains('meridian-header', $keys);
        $this->assertContains('meridian-hero', $keys);
        $this->assertContains('meridian-destinations-gallery', $keys);
        $this->assertContains('meridian-routes-tabs', $keys);
        $this->assertContains('meridian-live-desk', $keys);
        $this->assertContains('meridian-footer', $keys);
    }

    public function test_all_seven_meridian_page_templates_list_and_install_cleanly(): void
    {
        $admin = $this->admin();

        $templates = $this->actingAs($admin)
            ->getJson(route('builder.templates.index', ['type' => 'page', 'per_page' => 50]))
            ->assertOk()
            ->json('data');

        $meridianTemplates = array_values(array_filter(
            $templates,
            fn (array $row): bool => str_starts_with((string) ($row['id'] ?? ''), 'meridian-'),
        ));

        $this->assertCount(7, $meridianTemplates);

        foreach ($meridianTemplates as $template) {
            $this->assertSame('Global advisory', $template['category_label']);
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
