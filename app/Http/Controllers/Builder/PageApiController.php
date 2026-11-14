<?php

namespace App\Http\Controllers\Builder;

use App\Cms\Builder\Exceptions\InvalidDocumentException;
use App\Cms\Builder\PageDocuments;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The visual builder's page API: load, save (autosave), publish, History. */
class PageApiController extends Controller
{
    public function __construct(private PageDocuments $documents) {}

    public function show(Page $page): JsonResponse
    {
        return response()->json(['data' => $this->resource($page)]);
    }

    public function update(Request $request, Page $page): JsonResponse
    {
        $data = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'document' => ['required', 'array'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        abort_if(strlen((string) json_encode($data['document'])) > 2_000_000, 413, 'This page is too large to save.');

        try {
            $saved = $this->documents->save($page, $data['document'], (int) $data['version'], $request->user()->id, $data['label'] ?? null);
        } catch (InvalidDocumentException $e) {
            throw $e->toValidationException();
        }

        return response()->json(['data' => $this->resource($saved)]);
    }

    public function publish(Request $request, Page $page): JsonResponse
    {
        $data = $request->validate(['version' => ['nullable', 'integer', 'min:1']]);

        try {
            $published = $this->documents->publish($page, $request->user()->id, isset($data['version']) ? (int) $data['version'] : null);
        } catch (InvalidDocumentException $e) {
            throw $e->toValidationException();
        }

        return response()->json(['data' => $this->resource($published)]);
    }

    public function versions(Request $request, Page $page): JsonResponse
    {
        $withDocument = $request->boolean('with_document');
        $versions = $page->versions()->take(min(100, max(1, (int) $request->query('per_page', 50))))->get();

        return response()->json(['data' => $versions->map(fn (PageVersion $version) => array_filter([
            'id' => $version->id,
            'page_id' => (string) $version->page_id,
            'version' => $version->version,
            'schema_version' => $version->schema_version,
            'label' => $version->label,
            'created_by_id' => $version->created_by_id,
            'created_at' => $version->created_at?->toIso8601String(),
            'document' => $withDocument ? $version->document : null,
        ], fn ($value, $key) => $key !== 'document' || $withDocument, ARRAY_FILTER_USE_BOTH))->values()]);
    }

    public function restore(Request $request, Page $page, PageVersion $version): JsonResponse
    {
        abort_unless($version->page_id === $page->id, 404);

        try {
            $restored = $this->documents->restore($page, $version, $request->user()->id);
        } catch (InvalidDocumentException $e) {
            throw $e->toValidationException();
        }

        return response()->json(['data' => $this->resource($restored)]);
    }

    /** @return array<string, mixed> */
    public function resource(Page $page): array
    {
        return [
            'id' => (string) $page->id,
            'name' => $page->title,
            'slug' => $page->slug,
            'status' => $page->is_published && $page->published_document !== null ? 'published' : 'draft',
            'version' => $page->version,
            'document' => $this->documents->draft($page),
            'published_version' => $page->published_version,
            'published_at' => $page->published_at?->toIso8601String(),
            'is_published' => (bool) $page->is_published,
            'has_unpublished_changes' => $page->published_version !== $page->version,
            'url' => $page->is_home ? route('home') : route('pages.show', $page),
            'funnel' => null,
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];
    }
}
