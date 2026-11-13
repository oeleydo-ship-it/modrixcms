<?php

namespace Database\Factories;

use App\Models\Page;
use App\PageBuilder\PageBuilder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(2, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'blocks' => [
                PageBuilder::make('page_header', ['title' => $title]),
                PageBuilder::make('text', ['content' => fake()->paragraphs(3, true)]),
            ],
            'is_published' => true,
            'show_in_menu' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }

    /** A page whose text block holds the given Markdown. */
    public function withText(string $markdown): static
    {
        return $this->state(fn (array $attributes) => [
            'blocks' => [PageBuilder::make('text', ['content' => $markdown])],
        ]);
    }

    public function home(): static
    {
        return $this->state(fn () => ['is_home' => true, 'slug' => 'home', 'title' => 'Home', 'blocks' => PageBuilder::landingBlocks()]);
    }
}
