<?php

namespace App\Http\Controllers;

use App\Cms\Frontend\BlockPresenter;
use App\Cms\Hooks;
use App\Models\Page;
use App\PageBuilder\PageBuilder;
use Illuminate\View\View;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class HomeController extends Controller
{
    public function __invoke(Hooks $hooks, BlockPresenter $presenter): Response|View|SymfonyResponse
    {
        $page = Page::home();

        if ($page?->usesBuilder() && $page->published_document !== null) {
            return PageController::builderPage($page);
        }

        // A builder home page not yet published from the builder shows its block version,
        // or counts as no home page if it never had one.
        $page = $page?->usesBuilder() && ($page->blocks ?? []) === [] ? null : $page;

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
