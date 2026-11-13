<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Frontend\BlockPresenter;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\PageBuilder\PageBuilder;
use App\Support\Slugs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function index(Request $request): Response
    {
        $pages = Page::query()
            ->when($request->query('q'), fn ($q, $term) => $q->where('title', 'like', "%{$term}%"))
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Pages/Index', [
            'pages' => $pages->through(fn (Page $page) => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'is_home' => $page->is_home,
                'is_published' => $page->is_published,
                'show_in_menu' => $page->show_in_menu,
                'menu_order' => $page->menu_order,
                'block_count' => count($page->blocks ?? []),
                'updated' => $page->updated_at->diffForHumans(),
                'url' => $page->is_home ? route('home') : route('pages.show', $page),
            ]),
            'filters' => ['q' => $request->query('q')],
        ]);
    }

    public function create(Request $request): Response
    {
        // ?template=landing starts from a copy of the landing page.
        $blocks = $request->query('template') === 'landing'
            ? PageBuilder::landingBlocks()
            : [PageBuilder::make('page_header'), PageBuilder::make('text')];

        return $this->form(new Page(['is_published' => true, 'blocks' => $blocks]));
    }

    public function store(Request $request): RedirectResponse
    {
        $page = DB::transaction(fn () => $this->makeHomeIfAsked(Page::create($this->validated($request))));

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Page created.');
    }

    public function edit(Page $page): Response
    {
        return $this->form($page);
    }

    private function form(Page $page): Response
    {
        return Inertia::render('Admin/Pages/Form', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'blocks' => $page->blocks ?? [],
                'is_published' => (bool) $page->is_published,
                'is_home' => (bool) $page->is_home,
                'show_in_menu' => (bool) $page->show_in_menu,
                'menu_order' => $page->menu_order ?? 0,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'url' => $page->exists ? ($page->is_home ? route('home') : route('pages.show', $page)) : null,
            ],
            'builder' => PageBuilder::forEditor(),
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        DB::transaction(function () use ($request, $page) {
            $page->update($this->validated($request, $page));
            $this->makeHomeIfAsked($page);
        });

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        if ($page->is_home) {
            return back()->withErrors(['page' => 'This is the home page. Make another page the home page before deleting it.']);
        }

        $page->delete();

        return redirect()->route('admin.pages.index')->with('status', 'Page deleted.');
    }

    /** Renders unsaved blocks exactly as the site would, in a new tab. */
    public function preview(Request $request, BlockPresenter $presenter): Response
    {
        $request->validate(['blocks' => ['required', 'json', 'max:500000'], 'title' => ['nullable', 'string', 'max:200']]);

        $page = new Page(['title' => $request->input('title') ?: 'Preview']);

        Inertia::setRootView('app');

        return Inertia::render('Site/Page', [
            'page' => ['title' => $page->title, 'slug' => null],
            'blocks' => $presenter->present(PageBuilder::sanitize(json_decode($request->input('blocks'), true))),
            'preview' => true,
            'meta' => ['title' => $page->title.' (preview)', 'noindex' => true],
        ]);
    }

    /** Only one page can be the home page. */
    private function makeHomeIfAsked(Page $page): Page
    {
        if ($page->is_home) {
            Page::query()->whereKeyNot($page->id)->where('is_home', true)->update(['is_home' => false]);
        }

        return $page;
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $request->merge(['slug' => Slugs::from($request->input('slug') ?: $request->input('title'))]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'max:200', Rule::unique('pages', 'slug')->ignore($page), Rule::notIn(Slugs::RESERVED)],
            'blocks' => ['required', 'json', 'max:500000'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'menu_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'slug.not_in' => 'This URL is used by the site. Pick another one.',
        ]);

        $data['blocks'] = PageBuilder::sanitize(json_decode($data['blocks'], true));

        return $data + [
            'is_published' => $request->boolean('is_published'),
            'is_home' => $request->boolean('is_home'),
            'show_in_menu' => $request->boolean('show_in_menu'),
        ];
    }
}
