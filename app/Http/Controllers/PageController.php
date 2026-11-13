<?php

namespace App\Http\Controllers;

use App\Cms\Frontend\BlockPresenter;
use App\Cms\Hooks;
use App\Models\Page;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class PageController extends Controller
{
    public function show(Page $page, Hooks $hooks, BlockPresenter $presenter, Settings $settings): Response|RedirectResponse
    {
        abort_unless($page->is_published, 404);

        if ($page->is_home) {
            return redirect()->route('home', status: 301);
        }

        return inertia('Site/Page', [
            'page' => ['title' => $page->title, 'slug' => $page->slug],
            'blocks' => $presenter->present($hooks->apply('page.blocks', $page->visibleBlocks(), $page)),
            'meta' => array_filter([
                'title' => ($page->meta_title ?: $page->title).' · '.$settings->get('site_name'),
                'description' => $page->meta_description ?: null,
            ]),
        ]);
    }
}
