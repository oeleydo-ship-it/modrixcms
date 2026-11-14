<?php

namespace App\Http\Controllers\Builder;

use App\Cms\Builder\Exceptions\InvalidDocumentException;
use App\Cms\Builder\PageDocuments;
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

        $starter = $this->starter();

        if (($type === null || $type === 'page') && ($search === '' || stripos($starter['name'], $search) !== false)) {
            $rows->prepend($this->summary($starter));
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
        if ($id === self::STARTER_ID) {
            return $this->starter();
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

    /** A complete landing page assembled from the design library's starter keys. */
    private function starter(): array
    {
        $sections = [];

        foreach ($this->designs->starterPageKeys() as $key) {
            array_push($sections, ...($this->designs->find($key)['document']['sections'] ?? []));
        }

        return [
            'id' => self::STARTER_ID,
            'name' => 'Starter landing page',
            'description' => 'Navigation, hero, features, how it works, testimonials, pricing, FAQ and footer, ready to edit.',
            'type' => 'page',
            'category' => 'starter',
            'payload' => ['document' => ['schema_version' => DocumentUpgrader::CURRENT_VERSION, 'settings' => [], 'sections' => $sections]],
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
