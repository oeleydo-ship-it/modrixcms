<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Media\MediaLibrary;
use App\Cms\Media\MediaRejected;
use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** The Media screen: upload images and files, find them again, rename them and remove them. */
class MediaController extends Controller
{
    public function index(Request $request, MediaLibrary $library): Response
    {
        $filters = $request->only('kind', 'q');

        return Inertia::render('Admin/Media/Index', [
            'items' => $library->query($filters)->with('uploader')->paginate(24)->withQueryString()
                ->through(fn (MediaItem $item) => $item->toApi()),
            'filters' => ['kind' => $filters['kind'] ?? '', 'q' => $filters['q'] ?? ''],
            'counts' => $library->counts(),
            'upload' => [
                'maxMb' => (int) config('cms.media.max_upload_mb', 20),
                'accept' => $library->acceptAttribute(),
                'types' => $library->extensionsByKind(),
            ],
        ]);
    }

    /**
     * Stores one or more files. Each file is judged on its own, so one bad file does not stop the others.
     */
    public function store(Request $request, MediaLibrary $library): RedirectResponse|JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['file'],
        ], [
            'files.required' => 'Choose at least one file to upload.',
            'files.max' => 'Upload up to 20 files at a time.',
        ]);

        $stored = [];
        $problems = [];

        foreach ($request->file('files') as $file) {
            try {
                $stored[] = $library->store($file, $request->user());
            } catch (MediaRejected $rejected) {
                $problems[] = $rejected->getMessage();
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'data' => array_map(fn (MediaItem $item) => $item->toApi(), $stored),
                'errors' => $problems,
            ], $stored === [] ? 422 : 201);
        }

        if ($stored === []) {
            return back()->withErrors(['files' => implode(' ', $problems)]);
        }

        $count = count($stored);
        $redirect = back()->with('status', $count === 1 ? '1 file uploaded.' : "{$count} files uploaded.");

        return $problems === [] ? $redirect : $redirect->withErrors(['files' => implode(' ', $problems)]);
    }

    public function update(Request $request, MediaItem $media): RedirectResponse
    {
        $media->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:500'],
        ]));

        return back()->with('status', 'Saved.');
    }

    public function destroy(MediaItem $media, MediaLibrary $library): RedirectResponse
    {
        $library->delete($media);

        return back()->with('status', 'File deleted.');
    }

    /** Removes several items at once. */
    public function destroyMany(Request $request, MediaLibrary $library): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'max:100'], 'ids.*' => ['string']])['ids'];

        $items = MediaItem::query()->whereIn('id', $ids)->get();
        $items->each(fn (MediaItem $item) => $library->delete($item));

        return back()->with('status', $items->count() === 1 ? '1 file deleted.' : "{$items->count()} files deleted.");
    }

    /** The picker's list: the same filters, as JSON, for pickers inside other admin screens. */
    public function list(Request $request, MediaLibrary $library): JsonResponse
    {
        $page = $library->query($request->only('kind', 'q'))->with('uploader')->paginate(24)->withQueryString();

        return response()->json([
            'data' => $page->getCollection()->map(fn (MediaItem $item) => $item->toApi())->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }
}
