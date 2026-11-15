<?php

namespace App\Http\Controllers\Builder;

use App\Cms\Builder\Exceptions\InvalidDocumentException;
use App\Cms\Builder\PageDocuments;
use App\Cms\Builder\Schema\Collections\KairoDesigns;
use App\Cms\Builder\Schema\Collections\LarkspurDesigns;
use App\Cms\Builder\Schema\Collections\LumikinDesigns;
use App\Cms\Builder\Schema\Collections\MeridianDesigns;
use App\Cms\Builder\Schema\Collections\PebblewoodDesigns;
use App\Cms\Builder\Schema\Collections\SableDesigns;
use App\Cms\Builder\Schema\Collections\VertexDesigns;
use App\Cms\Builder\Schema\DesignBlockLibrary;
use App\Cms\Builder\Schema\DocumentUpgrader;
use App\Cms\Builder\Schema\DocumentValidator;
use App\Http\Controllers\Controller;
use App\Models\BuilderTemplate;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The builder's template library: whole pages, sections and blocks. Saved sections
 * (the "Saved" tab) are section templates. A built-in starter landing page is
 * assembled from the design library.
 */
class TemplateController extends Controller
{
    public const STARTER_ID = 'starter-landing-page';

    private const CATEGORY_LABELS = [
        'lead_generation' => 'Lead generation', 'product_sales' => 'Product sales', 'webinar' => 'Webinar',
        'course' => 'Course', 'booking' => 'Booking', 'newsletter' => 'Newsletter', 'saas_signup' => 'SaaS signup',
        'free_trial' => 'Free trial', 'event' => 'Event', 'ecommerce' => 'E-commerce', 'application' => 'Application',
        'survey' => 'Survey', 'sections' => 'Sections & blocks', 'starter' => 'Starter',
        'education' => 'Education & childcare',
        'advisory' => 'Global advisory',
        'saas' => 'SaaS & mobile app',
        'healthcare' => 'Healthcare & clinics',
        'enterprise' => 'Enterprise commerce',
        'ai_agency' => 'AI & automation studio',
    ];

    public function __construct(
        private DesignBlockLibrary $designs,
        private DocumentValidator $validator,
        private PageDocuments $documents,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(BuilderTemplate::TYPES)],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $type = $filters['type'] ?? null;
        $search = trim($filters['search'] ?? '');
        $rows = BuilderTemplate::query()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->latest('updated_at')
            ->get()
            ->map(fn (BuilderTemplate $template) => $this->summary($template));

        $builtIn = array_reverse($this->builtInPages());

        if ($type === null || $type === 'page') {
            foreach ($builtIn as $pageTemplate) {
                if ($search === '' || stripos($pageTemplate['name'], $search) !== false || stripos($pageTemplate['description'] ?? '', $search) !== false) {
                    $rows->prepend($this->summary($pageTemplate));
                }
            }
        }

        $perPage = (int) ($filters['per_page'] ?? 12);
        $page = (int) ($filters['page'] ?? 1);

        return response()->json([
            'data' => $rows->forPage($page, $perPage)->values(),
            'meta' => ['current_page' => $page, 'last_page' => max(1, (int) ceil($rows->count() / $perPage)), 'total' => $rows->count()],
        ]);
    }

    public function show(string $template): JsonResponse
    {
        $found = $this->find($template);

        return response()->json(['data' => [...$this->summary($found), 'payload' => $found['payload']]]);
    }

    /** Copies a template into the page (page templates) or hands back the fragment to insert. */
    public function install(Request $request, string $template): JsonResponse
    {
        $found = $this->find($template);

        if ($found['type'] !== 'page') {
            return response()->json(['data' => ['page' => null, 'fragment' => $found['payload'][$found['type']] ?? null]]);
        }

        $data = $request->validate([
            'page_id' => ['required', 'integer', 'exists:pages,id'],
            'expected_version' => ['required', 'integer', 'min:1'],
        ]);

        $page = Page::query()->findOrFail($data['page_id']);

        try {
            $saved = $this->documents->save($page, $found['payload']['document'], (int) $data['expected_version'], $request->user()->id, 'Before template: '.$found['name']);
        } catch (InvalidDocumentException $e) {
            throw $e->toValidationException();
        }

        return response()->json(['data' => ['page' => app(PageApiController::class)->resource($saved), 'fragment' => null]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'type' => ['required', Rule::in(BuilderTemplate::TYPES)],
            'category' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:1000'],
            'page_id' => ['required_if:type,page', 'nullable', 'integer', 'exists:pages,id'],
            'node' => ['required_unless:type,page', 'nullable', 'array'],
        ]);

        $payload = match ($data['type']) {
            'page' => ['document' => $this->documents->draft(Page::query()->findOrFail($data['page_id']))],
            'section' => ['section' => $data['node']],
            'block' => ['block' => $data['node']],
        };

        abort_if(strlen((string) json_encode($payload)) > 2_000_000, 413, 'This template is too large.');

        if ($data['type'] === 'section') {
            $this->validateDocument(['schema_version' => DocumentUpgrader::CURRENT_VERSION, 'settings' => [], 'sections' => [$data['node']]]);
        }

        $template = BuilderTemplate::query()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'category' => $data['category'] ?? ($data['type'] === 'page' ? null : 'sections'),
            'description' => $data['description'] ?? null,
            'payload' => $payload,
            'created_by_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $this->summary($template)], 201);
    }

    public function destroy(BuilderTemplate $template): JsonResponse
    {
        $template->delete();

        return response()->json(null, 204);
    }

    /** @return array<string, mixed> */
    private function summary(BuilderTemplate|array $template): array
    {
        $row = $template instanceof BuilderTemplate ? [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'type' => $template->type,
            'category' => $template->category,
            'updated_at' => $template->updated_at?->toIso8601String(),
        ] : $template;

        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? null,
            'type' => $row['type'],
            'category_label' => self::CATEGORY_LABELS[$row['category'] ?? ''] ?? 'My templates',
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    /** @return array{id: string, name: string, type: string, payload: array<string, mixed>, category?: string, description?: string|null} */
    private function find(string $id): array
    {
        foreach ($this->builtInPages() as $builtIn) {
            if ($builtIn['id'] === $id) {
                return [...$builtIn, 'payload' => $builtIn['payload'] instanceof \Closure ? ($builtIn['payload'])() : $builtIn['payload']];
            }
        }

        $template = BuilderTemplate::query()->findOrFail($id);

        return [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'type' => $template->type,
            'category' => $template->category,
            'payload' => $template->payload,
        ];
    }

    /**
     * Built-in whole-page templates assembled from the design library.
     *
     * @return list<array{id: string, name: string, description: string, type: string, category: string, payload: array<string, mixed>}>
     */
    private function builtInPages(): array
    {
        $collection = new PebblewoodDesigns;
        $pebblewood = array_map(fn (array $page): array => [
            'id' => $page['id'],
            'name' => $page['name'],
            'description' => $page['description'],
            'type' => 'page',
            'category' => 'education',
            'payload' => fn (): array => ['document' => [
                'schema_version' => DocumentUpgrader::CURRENT_VERSION,
                'settings' => [],
                'sections' => $collection->sections($page['keys']),
            ]],
        ], $collection->pages());

        $lumikin = new LumikinDesigns;
        $lumikinPages = array_map(fn (array $page): array => [
            'id' => $page['id'],
            'name' => $page['name'],
            'description' => $page['description'],
            'type' => 'page',
            'category' => 'education',
            'payload' => fn (): array => ['document' => [
                'schema_version' => DocumentUpgrader::CURRENT_VERSION,
                'settings' => [],
                'sections' => $lumikin->sections(['lumikin-header', ...$page['keys'], 'lumikin-footer']),
            ]],
        ], $lumikin->pages());

        return [
            $this->starter(),
            ...$pebblewood,
            ...$lumikinPages,
            $this->assemblePage(
                id: 'atomos99-home-page',
                name: 'Nexus AI · AI Agency Home',
                description: 'Complete Nexus AI Agency home page with floating pill header, interactive AI workflow hero, logo strip, services bento, case studies, 3-step process, benefits bento, dark reviews, FAQ, and dark footer.',
                category: 'saas_signup',
                keys: [
                    'atomos99-navbar',
                    'atomos99-hero-home',
                    'atomos99-logo-strip',
                    'atomos99-services',
                    'atomos99-case-studies',
                    'atomos99-process',
                    'atomos99-benefits',
                    'atomos99-testimonials',
                    'atomos99-faq',
                    'atomos99-footer',
                ],
            ),
            $this->assemblePage(
                id: 'atomos99-about-page',
                name: 'Nexus AI · About Us',
                description: 'Nexus AI About Us page featuring operator bento hero, brand strip, company mission & 4 metrics, strategy pillars, 6-member team grid, dark reviews, FAQ, and dark footer.',
                category: 'saas_signup',
                keys: [
                    'atomos99-navbar',
                    'atomos99-about-hero',
                    'atomos99-logo-strip',
                    'atomos99-about-mission',
                    'atomos99-about-strategy',
                    'atomos99-about-team',
                    'atomos99-testimonials',
                    'atomos99-faq',
                    'atomos99-footer',
                ],
            ),
            $this->assemblePage(
                id: 'atomos99-services-page',
                name: 'Nexus AI · Services',
                description: 'Nexus AI Services page ("Four ways we help you grow with AI") with 5 service showcases, strategy value pillars, 3-step process, case studies, FAQ, and dark footer.',
                category: 'saas_signup',
                keys: [
                    'atomos99-navbar',
                    'atomos99-services-hero',
                    'atomos99-services-strategy',
                    'atomos99-process',
                    'atomos99-case-studies',
                    'atomos99-faq',
                    'atomos99-footer',
                ],
            ),
            $this->assemblePage(
                id: 'atomos99-faqs-page',
                name: 'Nexus AI · FAQs & Support',
                description: 'Nexus AI FAQs & Help Hub featuring categorized two-column accordions, 3 direct contact channels (Email, WhatsApp, Austin HQ), LightAI spotlight, and dark footer.',
                category: 'saas_signup',
                keys: [
                    'atomos99-navbar',
                    'atomos99-faq-hub',
                    'atomos99-faq',
                    'atomos99-testimonials',
                    'atomos99-footer',
                ],
            ),
            $this->assemblePage(
                id: 'atomos99-blog-page',
                name: 'Nexus AI · Blogs',
                description: 'Nexus AI Blogs page featuring editorial hero ("How to know if your business is ready for AI"), 6 blog article cards with categories & dates, FAQ, and dark footer.',
                category: 'saas_signup',
                keys: [
                    'atomos99-navbar',
                    'atomos99-blog-grid',
                    'atomos99-faq',
                    'atomos99-footer',
                ],
            ),
            $this->assemblePage(
                id: 'saas-product-launch',
                name: 'SaaS & product launch',
                description: 'Minimal SaaS hero, bento showcase, process timeline, case study spotlight, 3-tier pricing and two-column FAQ.',
                category: 'saas_signup',
                keys: [
                    'navbar-floating-pill',
                    'hero-minimal-saas',
                    'content-bento-showcase',
                    'content-process-timeline',
                    'social-case-study-spotlight',
                    'commerce-pricing-pro',
                    'structure-faq-two-column',
                    'conversion-gradient-card',
                    'structure-footer-minimal',
                ],
            ),
            $this->assemblePage(
                id: 'commerce-brand-showcase',
                name: 'Commerce brand & bundle showcase',
                description: 'Announcement header, editorial split hero, trust badges, featured bundle, lookbook gallery, review wall and footer.',
                category: 'ecommerce',
                keys: [
                    'navbar-announcement',
                    'hero-editorial-split',
                    'social-trust-badges',
                    'commerce-bundle-spotlight',
                    'media-bento-gallery',
                    'social-testimonial-wall',
                    'guarantee-band',
                    'footer-columns',
                ],
            ),
            $this->assemblePage(
                id: 'comparison-conversion-page',
                name: 'High-conversion comparison page',
                description: 'Product launch hero, video walkthrough split, us-vs-others matrix, testimonials, pricing and FAQ.',
                category: 'product_sales',
                keys: [
                    'navbar-dark',
                    'hero-product-launch',
                    'media-showcase-split',
                    'content-comparison-matrix',
                    'testimonial-row',
                    'pricing-table',
                    'faq-section',
                    'conversion-gradient-card',
                    'footer-columns',
                ],
            ),
            ...array_map(fn (array $page): array => [
                'id' => $page['key'],
                'name' => $page['name'],
                'description' => $page['description'],
                'type' => 'page',
                'category' => 'advisory',
                'payload' => fn (): array => ['document' => $page['document']],
            ], (new MeridianDesigns)->pages()),
            ...array_map(fn (array $page): array => [
                'id' => $page['key'],
                'name' => $page['name'],
                'description' => $page['description'],
                'type' => 'page',
                'category' => 'saas',
                'payload' => fn (): array => ['document' => $page['document']],
            ], (new KairoDesigns)->pages()),
            ...array_map(fn (array $page): array => [
                'id' => $page['key'],
                'name' => $page['name'],
                'description' => $page['description'],
                'type' => 'page',
                'category' => 'healthcare',
                'payload' => fn (): array => ['document' => $page['document']],
            ], (new LarkspurDesigns)->pages()),

            ...array_map(fn (array $page): array => [
                'id' => $page['key'],
                'name' => $page['name'],
                'description' => $page['description'],
                'type' => 'page',
                'category' => 'enterprise',
                'payload' => fn (): array => ['document' => $page['document']],
            ], (new VertexDesigns)->pages()),

            ...array_map(fn (array $page): array => [
                'id' => $page['key'],
                'name' => $page['name'],
                'description' => $page['description'],
                'type' => 'page',
                'category' => 'ai_agency',
                'payload' => fn (): array => ['document' => $page['document']],
            ], (new SableDesigns)->pages()),
        ];
    }

    /** A complete landing page assembled from the design library's starter keys. */
    private function starter(): array
    {
        return $this->assemblePage(
            id: self::STARTER_ID,
            name: 'Starter landing page',
            description: 'Navigation, hero, features, how it works, testimonials, pricing, FAQ and footer, ready to edit.',
            category: 'starter',
            keys: $this->designs->starterPageKeys(),
        );
    }

    /**
     * @param  list<string>  $keys
     * @return array{id: string, name: string, description: string, type: string, category: string, payload: array<string, mixed>}
     */
    private function assemblePage(string $id, string $name, string $description, string $category, array $keys): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'type' => 'page',
            'category' => $category,
            // Built only when this template is opened: the list needs just the summary.
            'payload' => function () use ($keys): array {
                $byKey = array_column($this->designs->all(), 'document', 'key');
                $sections = [];

                foreach ($keys as $key) {
                    array_push($sections, ...($byKey[$key]['sections'] ?? []));
                }

                return ['document' => ['schema_version' => DocumentUpgrader::CURRENT_VERSION, 'settings' => [], 'sections' => $sections]];
            },
        ];
    }

    private function validateDocument(array $document): void
    {
        try {
            $this->validator->validate($document);
        } catch (InvalidDocumentException $e) {
            throw $e->toValidationException();
        }
    }
}
