<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Builder\BlockPageConverter;
use App\Cms\Builder\Exceptions\InvalidDocumentException;
use App\Cms\Builder\PageDocuments;
use App\Cms\Builder\SiteChrome;
use App\Cms\Frontend\BlockPresenter;
use App\Cms\Themes\ThemeManager;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\PageBuilder\PageBuilder;
use App\Support\Slugs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PageController extends Controller
{
    public function index(Request $request, ThemeManager $themes, AiGeneratorController $aiGenerator): Response
    {
        $regularPageCount = Page::query()->regular()->count();
        $pages = Page::query()
            ->regular()
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
                'can_delete' => ! $page->is_home || $regularPageCount <= 1,
                'is_published' => $page->is_published,
                'show_in_menu' => $page->show_in_menu,
                'menu_order' => $page->menu_order,
                'block_count' => count($page->blocks ?? []),
                'editor' => $page->editor,
                'builder_url' => route('admin.pages.builder', $page),
                'unpublished_changes' => $page->hasUnpublishedChanges(),
                'updated' => $page->updated_at->diffForHumans(),
                'url' => $page->is_home ? route('home') : route('pages.show', $page),
            ]),
            'filters' => ['q' => $request->query('q')],
            'siteChrome' => [
                'headerUrl' => route('admin.pages.site-header'),
                'footerUrl' => route('admin.pages.site-footer'),
                'headerLive' => app(SiteChrome::class)->current(Page::ROLE_HEADER)?->published_document !== null,
                'footerLive' => app(SiteChrome::class)->current(Page::ROLE_FOOTER)?->published_document !== null,
                'theme' => $themes->theme()?->displayName() ?? $themes->name(),
            ],
            'ai' => $aiGenerator->workspaceProps($themes, $request->filled('ai_page') ? $request->integer('ai_page') : null),
        ]);
    }

    /** Opens the site header (shown on top of every visual-builder page) in the builder. */
    public function siteHeader(Request $request, SiteChrome $chrome): View|SymfonyResponse
    {
        return $this->builder($request, $chrome->page(Page::ROLE_HEADER));
    }

    /** Opens the site footer (shown below every visual-builder page) in the builder. */
    public function siteFooter(Request $request, SiteChrome $chrome): View|SymfonyResponse
    {
        return $this->builder($request, $chrome->page(Page::ROLE_FOOTER));
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

    /** Starts a new page in the visual builder. */
    public function storeBuilderPage(): RedirectResponse
    {
        $slug = Slugs::from('untitled-page');
        $base = $slug;

        for ($n = 2; Page::query()->where('slug', $slug)->exists(); $n++) {
            $slug = $base.'-'.$n;
        }

        $page = Page::query()->create([
            'title' => 'Untitled page',
            'slug' => $slug,
            'editor' => Page::EDITOR_BUILDER,
            'blocks' => [],
            'is_published' => false,
        ]);

        return redirect()->route('admin.pages.builder', $page);
    }

    /** The visual page builder (a full-screen React app talking to /api/v1). */
    public function builder(Request $request, Page $page): View|SymfonyResponse
    {
        // It is its own full-screen app: reached from an Inertia page, ask for a full load.
        if ($request->header('X-Inertia')) {
            return Inertia::location(route('admin.pages.builder', $page));
        }

        return view('builder', ['page' => $page]);
    }

    /**
     * Moves a block-editor page into the visual builder. Its blocks become builder
     * sections (themed colours); the blocks are kept, so it can be switched back.
     * Visitors keep seeing the block version until the page is published from the builder.
     */
    public function convertToBuilder(Request $request, Page $page, BlockPageConverter $converter, PageDocuments $documents): RedirectResponse|SymfonyResponse
    {
        if ($page->usesBuilder()) {
            return Inertia::location(route('admin.pages.builder', $page));
        }

        $skipped = [];

        // A page converted before keeps the builder work done since; only a first conversion builds from blocks.
        if ($page->document === null) {
            $result = $converter->convert($page->blocks ?? []);
            $skipped = $result['skipped'];

            try {
                $page = $documents->save($page, $result['document'], $page->version, $request->user()->id, 'Converted from the block editor');
            } catch (InvalidDocumentException $e) {
                return back()->withErrors(['page' => 'This page could not be converted: '.implode(' ', array_slice($e->errors, 0, 3))]);
            }
        } else {
            $page->forceFill(['editor' => Page::EDITOR_BUILDER])->save();
        }

        if ($skipped !== []) {
            $request->session()->flash('status', 'Converted. These blocks have no visual-builder version and were left out: '.implode(', ', $skipped).'.');
        }

        return Inertia::location(route('admin.pages.builder', $page));
    }

    /** Back to the block editor (its blocks were kept). The builder design stays saved for later. */
    public function switchToBlocks(Page $page): RedirectResponse
    {
        abort_if(($page->blocks ?? []) === [], 422, 'This page has no block-editor version to go back to.');

        $page->forceFill(['editor' => Page::EDITOR_BLOCKS])->save();

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Back in the block editor. Your visual-builder design is kept if you switch again.');
    }

    public function edit(Page $page): Response|SymfonyResponse
    {
        // The site header and footer have no settings of their own: they open in the builder.
        if ($page->role !== null) {
            return Inertia::location(route('admin.pages.builder', $page));
        }

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
                'editor' => $page->editor ?? Page::EDITOR_BLOCKS,
                'builder_url' => $page->exists ? route('admin.pages.builder', $page) : null,
                'convert_url' => $page->exists ? route('admin.pages.convert', $page) : null,
                'switch_back_url' => $page->exists && ($page->blocks ?? []) !== [] ? route('admin.pages.switch-to-blocks', $page) : null,
                'has_builder_design' => $page->document !== null,
                'builder_live' => $page->published_document !== null,
                'show_site_header' => (bool) ($page->show_site_header ?? true),
                'show_site_footer' => (bool) ($page->show_site_footer ?? true),
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
        if ($page->role !== null) {
            return back()->withErrors(['page' => 'Site header and footer templates cannot be deleted.']);
        }

        if ($page->is_home && Page::query()->regular()->whereKeyNot($page->getKey())->exists()) {
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
            'show_site_header' => $request->boolean('show_site_header', true),
            'show_site_footer' => $request->boolean('show_site_footer', true),
        ];
    }
}
