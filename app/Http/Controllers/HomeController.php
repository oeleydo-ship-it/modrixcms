<?php

namespace App\Http\Controllers;

use App\Cms\Frontend\BlockPresenter;
use App\Cms\Hooks;
use App\Models\Page;
use App\PageBuilder\PageBuilder;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Hooks $hooks, BlockPresenter $presenter): Response
    {
        $page = Page::home();

        // No home page set yet: show the default landing page.
        $blocks = $hooks->apply('page.blocks', $page?->visibleBlocks() ?? PageBuilder::landingBlocks(), $page);

        return inertia('Site/Page', [
            'page' => $page ? ['title' => $page->title, 'slug' => $page->slug] : null,
            'blocks' => $presenter->present($blocks),
            'meta' => array_filter([
                'title' => $page?->meta_title ?: null,
                'description' => $page?->meta_description ?: null,
            ]),
        ]);
    }
}
