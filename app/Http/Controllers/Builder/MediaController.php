<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The builder's media library: images and videos on the public disk.
 * SVG is not accepted, because an SVG file can carry scripts.
 */
class MediaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = MediaItem::query()->latest()->take(min(200, max(1, (int) $request->query('per_page', 100))))->get();

        return response()->json(['data' => $items->map(fn (MediaItem $item) => $item->toApi())->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,avif,mp4,webm', 'max:20480'],
            'alt' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $request->file('file');
        $path = $file->store('media', 'public');
        $size = str_starts_with((string) $file->getMimeType(), 'image/') ? @getimagesize($file->getRealPath()) : false;

        $item = MediaItem::query()->create([
            'name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'path' => $path,
            'mime_type' => (string) $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $size ? $size[0] : null,
            'height' => $size ? $size[1] : null,
            'alt' => $request->input('alt'),
        ]);

        return response()->json(['data' => $item->toApi()], 201);
    }

    public function update(Request $request, MediaItem $media): JsonResponse
    {
        $media->update($request->validate(['alt' => ['nullable', 'string', 'max:500'], 'name' => ['sometimes', 'string', 'max:255']]));

        return response()->json(['data' => $media->toApi()]);
    }

    public function destroy(MediaItem $media): JsonResponse
    {
        Storage::disk('public')->delete($media->path);
        $media->delete();

        return response()->json(null, 204);
    }
}
