<?php

namespace App\Http\Controllers;

use App\Cms\Frontend\BlockPresenter;
use App\Cms\Hooks;
use App\Models\Page;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PageController extends Controller
{
    public function show(Page $page, Hooks $hooks, BlockPresenter $presenter, Settings $settings): Response|RedirectResponse|View|SymfonyResponse
    {
        abort_unless($page->is_published, 404);

        if ($page->is_home) {
            return redirect()->route('home', status: 301);
        }

        if ($page->usesBuilder() && $page->published_document !== null) {
            return self::builderPage($page);
        }

        // Converted to the builder but not published from it yet: visitors keep the block version.
        abort_if($page->usesBuilder() && ($page->blocks ?? []) === [], 404);

        return inertia('Site/Page', [
            'page' => ['title' => $page->title, 'slug' => $page->slug],
            'blocks' => $presenter->present($hooks->apply('page.blocks', $page->visibleBlocks(), $page)),
            'meta' => array_filter([
                'title' => ($page->meta_title ?: $page->title).' · '.$settings->get('site_name'),
                'description' => $page->meta_description ?: null,
            ]),
        ]);
    }

    /**
     * A page published from the visual builder, drawn by the builder's own renderer.
     * Reached from an Inertia link, it asks for a full page load (it has its own HTML).
     */
    public static function builderPage(Page $page): View|SymfonyResponse
    {
        if (request()->header('X-Inertia')) {
            return Inertia::location(request()->fullUrl());
        }

        return view('builder-page', ['page' => $page, 'document' => $page->published_document]);
    }
}
