<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Ai\AiClient;
use App\Cms\Ai\ThemeSiteGenerator;
use App\Cms\Ai\WebsiteGenerator;
use App\Cms\Builder\Exceptions\InvalidDocumentException;
use App\Cms\Themes\DesignTokens;
use App\Cms\Themes\ThemeManager;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AiGeneratorController extends Controller
{
    public function __construct(
        private AiClient $ai,
        private WebsiteGenerator $generator,
        private Settings $settings,
        private ThemeSiteGenerator $themeSite,
    ) {}

    public function index(Request $request, ThemeManager $themes): Response
    {
        $selectedPageId = $request->filled('page') ? $request->integer('page') : null;

        return Inertia::render('Admin/Ai/Index', $this->workspaceProps($themes, $selectedPageId));
    }

    /**
     * Build shared workspace props used by the dedicated AI page and `/admin/pages`.
     *
     * @return array<string, mixed>
     */
    public function workspaceProps(ThemeManager $themes, ?int $selectedPageId = null): array
    {
        $config = $this->ai->config();

        $existingPages = Page::query()
            ->regular()
            ->orderByDesc('is_home')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'is_home', 'editor', 'is_published'])
            ->map(fn (Page $page): array => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'is_home' => $page->is_home,
                'editor' => $page->editor,
                'is_published' => $page->is_published,
            ])
            ->values()
            ->all();

        $validTargetPageId = $selectedPageId !== null
            && collect($existingPages)->contains(fn (array $page): bool => $page['id'] === $selectedPageId)
            ? $selectedPageId
            : null;

        return [
            'aiConfigured' => $this->ai->isConfigured(),
            'aiProviderLabel' => AiClient::PROVIDERS[$config['provider']]['label'] ?? $config['provider'],
            'aiModel' => $config['model'],
            'settingsUrl' => route('admin.settings.ai'),
            'siteName' => $this->settings->get('site_name', 'Modrix CMS'),
            'defaultPages' => ['Home', 'About', 'Services', 'Pricing', 'FAQ', 'Contact'],
            'siteCssVars' => str_replace(':root', '.ai-site-preview', DesignTokens::stylesheet($themes->design())),
            'existingPages' => $existingPages,
            'initialTargetPageId' => $validTargetPageId,
            'themeKit' => $this->themeSite->summary(),
        ];
    }

    /** Theme-designed site, step 1: the plan with each page's sections from the active theme. */
    public function sitePlan(Request $request): JsonResponse
    {
        $this->allowLongRequest();

        $data = $request->validate([
            'prompt' => ['required', 'string', 'min:10', 'max:4000'],
            'tone' => ['nullable', 'string', 'max:100'],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'pages' => ['nullable', 'array', 'max:12'],
            'pages.*' => ['string', 'max:60'],
        ]);

        return $this->attempt(fn (): array => $this->themeSite->plan($data));
    }

    /** Theme-designed site, step 2: writes one page's texts. */
    public function sitePage(Request $request): JsonResponse
    {
        $this->allowLongRequest();

        $request->validate([
            'site' => ['required', 'array'],
            'site.brand' => ['required', 'array'],
            'site.pages' => ['required', 'array', 'min:1'],
            'page' => ['required', 'array'],
            'page.title' => ['required', 'string', 'max:120'],
            'page.document' => ['required', 'array'],
        ]);

        return $this->attempt(fn (): array => $this->themeSite->writePage($request->input('site'), $request->input('page')));
    }

    /** Theme-designed site, step 3: writes the theme's global header and footer. */
    public function siteChrome(Request $request): JsonResponse
    {
        $this->allowLongRequest();

        $request->validate([
            'site' => ['required', 'array'],
            'site.brand' => ['required', 'array'],
            'site.pages' => ['required', 'array', 'min:1'],
            'header_document' => ['nullable', 'array'],
            'footer_document' => ['nullable', 'array'],
        ]);

        return $this->attempt(fn (): array => $this->themeSite->writeChrome($request->input('site'), $request->input('header_document'), $request->input('footer_document')));
    }

    /** @param  callable(): array<string, mixed>  $work */
    private function attempt(callable $work): JsonResponse
    {
        try {
            return response()->json(['data' => $work()]);
        } catch (InvalidDocumentException $e) {
            return response()->json(['message' => 'The generated page failed validation: '.implode(' ', array_slice($e->errors, 0, 3))], 422);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /** AI calls can take a while; a test run must never inherit the limit. */
    private function allowLongRequest(): void
    {
        if (function_exists('set_time_limit') && ! app()->runningUnitTests()) {
            @set_time_limit(300);
        }
    }

    public function generate(Request $request): JsonResponse
    {
        $this->allowLongRequest();

        $data = $request->validate([
            'prompt' => ['required', 'string', 'min:10', 'max:4000'],
            'mode' => ['required', Rule::in(['multipage', 'single', 'redesign'])],
            'target_page_id' => [
                'nullable',
                'required_if:mode,redesign',
                'integer',
                Rule::exists('pages', 'id')->whereNull('role'),
            ],
            'tone' => ['nullable', 'string', 'max:100'],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'page_title' => ['nullable', 'string', 'max:120'],
            'pages' => ['nullable', 'array', 'max:12'],
            'pages.*' => ['string', 'max:60'],
            'current_document' => ['nullable', 'array'],
        ]);

        try {
            $plan = $this->generator->generate($data);
        } catch (InvalidDocumentException $e) {
            return response()->json(['message' => 'Generated document failed validation: '.implode(' ', array_slice($e->errors, 0, 3))], 422);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $plan]);
    }

    public function apply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan' => ['required', 'array'],
            'plan.brand' => ['nullable', 'array'],
            'plan.header_document' => ['nullable', 'array'],
            'plan.footer_document' => ['nullable', 'array'],
            'plan.pages' => ['required', 'array', 'min:1'],
            'selected_slugs' => ['nullable', 'array'],
            'selected_slugs.*' => ['string', 'max:120'],
            'replace_existing' => ['nullable', 'boolean'],
            'publish_immediately' => ['nullable', 'boolean'],
            'update_site_chrome' => ['nullable', 'boolean'],
            'update_site_settings' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $this->generator->apply($data['plan'], $request->user()->id, [
                'selected_slugs' => $data['selected_slugs'] ?? [],
                'replace_existing' => $request->boolean('replace_existing', true),
                'publish_immediately' => $request->boolean('publish_immediately', true),
                'update_site_chrome' => $request->boolean('update_site_chrome', true),
                'update_site_settings' => $request->boolean('update_site_settings', true),
            ]);
        } catch (InvalidDocumentException $e) {
            return response()->json(['message' => 'A page document failed validation: '.implode(' ', array_slice($e->errors, 0, 3))], 422);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $result]);
    }
}
