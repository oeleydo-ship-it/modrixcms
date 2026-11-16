<?php

namespace App\Http\Controllers\Builder;

use App\Cms\Media\MediaLibrary;
use App\Cms\Media\MediaRejected;
use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The builder's view of the media library. The pickers show images and videos unless they ask
 * for other kinds with `kind`. SVG is not accepted, because an SVG file can carry scripts.
 */
class MediaController extends Controller
{
    public function index(Request $request, MediaLibrary $library): JsonResponse
    {
        $filters = ['kind' => $request->query('kind', 'image,video'), 'q' => $request->query('q')];
        $items = $library->query($filters)->with('uploader')->take(min(200, max(1, (int) $request->query('per_page', 100))))->get();

        return response()->json(['data' => $items->map(fn (MediaItem $item) => $item->toApi())->values()]);
    }

    public function store(Request $request, MediaLibrary $library): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file'],
            'alt' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $item = $library->store($request->file('file'), $request->user(), $request->input('alt'));
        } catch (MediaRejected $rejected) {
            throw ValidationException::withMessages(['file' => $rejected->getMessage()]);
        }

        return response()->json(['data' => $item->toApi()], 201);
    }

    public function update(Request $request, MediaItem $media): JsonResponse
    {
        $media->update($request->validate(['alt' => ['nullable', 'string', 'max:500'], 'name' => ['sometimes', 'string', 'max:255']]));

        return response()->json(['data' => $media->toApi()]);
    }

    public function destroy(MediaItem $media, MediaLibrary $library): JsonResponse
    {
        $library->delete($media);

        return response()->json(null, 204);
    }
}
