<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PebblewoodTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private const PAGE_TEMPLATES = [
        'pebblewood-home',
        'pebblewood-about',
        'pebblewood-programs-page',
        'pebblewood-blog',
        'pebblewood-contact',
    ];

    public function test_the_five_pebblewood_page_templates_are_listed_under_education(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $templates = collect($this->getJson('/api/v1/templates?type=page&per_page=100')->assertOk()->json('data'))
            ->where('category_label', 'Education & childcare')
            ->filter(fn (array $template) => str_starts_with($template['id'], 'pebblewood-'));

        $this->assertSame(self::PAGE_TEMPLATES, $templates->pluck('id')->values()->all());
    }

    public function test_each_pebblewood_template_installs_as_an_editable_page_tagged_with_its_collection(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (self::PAGE_TEMPLATES as $template) {
            $page = Page::factory()->create(['editor' => Page::EDITOR_BUILDER, 'blocks' => []]);

            $this->postJson("/api/v1/templates/{$template}/install", ['page_id' => $page->id, 'expected_version' => 1])
                ->assertOk()
                ->assertJsonPath('data.page.version', 2);

            $sections = $page->fresh()->document['sections'];
            $this->assertGreaterThanOrEqual(4, count($sections), $template);
            $this->assertSame(['pebblewood'], array_values(array_unique(array_column(array_column($sections, 'settings'), 'designCollection'))), $template);
            $first = $sections[0];
            $this->assertContains($first['settings']['designBlock'], ['header-light', 'hero'], $template);
            $this->assertSame('logo', $first['rows'][1]['columns'][0]['blocks'][0]['type'], "{$template}: the header starts with a logo image block");
            $this->assertNotEmpty($first['rows'][1]['columns'][0]['blocks'][0]['props']['src'], $template);
            $this->assertSame('footer', $sections[count($sections) - 1]['settings']['designBlock'], $template);
        }
    }

    public function test_pebblewood_sections_can_be_inserted_from_the_design_library(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $keys = collect($this->getJson('/api/v1/design-blocks')->assertOk()->json('data'))->pluck('key');

        foreach (['pebblewood-header-light', 'pebblewood-hero', 'pebblewood-spaces', 'pebblewood-faq', 'pebblewood-footer', 'pebblewood-contact'] as $key) {
            $this->assertContains($key, $keys);
        }
    }
}
