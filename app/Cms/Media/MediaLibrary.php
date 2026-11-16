<?php

namespace App\Cms\Media;

use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The media library: stores uploads on the public disk, records them in `media_items` and
 * answers the questions the admin screen and the pickers ask (list, search, filter, count).
 *
 * Only the file types listed in TYPES are accepted, and a file must be what its name says: its
 * detected content type has to match its extension. SVG, HTML, scripts and executables are
 * not on the list, because a browser can run them from the site's own address.
 */
class MediaLibrary
{
    public const DISK = 'public';

    public const KIND_IMAGE = 'image';

    public const KIND_VIDEO = 'video';

    public const KIND_FILE = 'file';

    /** The zip container that Office and OpenDocument files are, when a server cannot tell them apart. */
    private const ZIP = ['application/zip', 'application/x-zip', 'application/x-zip-compressed'];

    /** The old Office container (.doc, .xls, .ppt) as different servers name it. */
    private const OLE = ['application/x-cfb', 'application/CDFV2', 'application/x-ole-storage', 'application/vnd.ms-office'];

    /**
     * Accepted extension => [kind, content types that count as that extension].
     *
     * @return array<string, array{0: string, 1: list<string>}>
     */
    private static function types(): array
    {
        return [
            'jpg' => [self::KIND_IMAGE, ['image/jpeg']],
            'jpeg' => [self::KIND_IMAGE, ['image/jpeg']],
            'png' => [self::KIND_IMAGE, ['image/png']],
            'gif' => [self::KIND_IMAGE, ['image/gif']],
            'webp' => [self::KIND_IMAGE, ['image/webp']],
            'avif' => [self::KIND_IMAGE, ['image/avif']],
            'mp4' => [self::KIND_VIDEO, ['video/mp4']],
            'webm' => [self::KIND_VIDEO, ['video/webm']],
            'pdf' => [self::KIND_FILE, ['application/pdf']],
            'doc' => [self::KIND_FILE, ['application/msword', ...self::OLE]],
            'docx' => [self::KIND_FILE, ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', ...self::ZIP]],
            'xls' => [self::KIND_FILE, ['application/vnd.ms-excel', ...self::OLE]],
            'xlsx' => [self::KIND_FILE, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', ...self::ZIP]],
            'ppt' => [self::KIND_FILE, ['application/vnd.ms-powerpoint', ...self::OLE]],
            'pptx' => [self::KIND_FILE, ['application/vnd.openxmlformats-officedocument.presentationml.presentation', ...self::ZIP]],
            'odt' => [self::KIND_FILE, ['application/vnd.oasis.opendocument.text', ...self::ZIP]],
            'ods' => [self::KIND_FILE, ['application/vnd.oasis.opendocument.spreadsheet', ...self::ZIP]],
            'odp' => [self::KIND_FILE, ['application/vnd.oasis.opendocument.presentation', ...self::ZIP]],
            'rtf' => [self::KIND_FILE, ['text/rtf', 'application/rtf']],
            'txt' => [self::KIND_FILE, ['text/plain']],
            'csv' => [self::KIND_FILE, ['text/csv', 'text/plain', 'application/csv']],
            'zip' => [self::KIND_FILE, self::ZIP],
            'mp3' => [self::KIND_FILE, ['audio/mpeg', 'audio/mp3']],
            'wav' => [self::KIND_FILE, ['audio/wav', 'audio/x-wav', 'audio/vnd.wave']],
            'ogg' => [self::KIND_FILE, ['audio/ogg', 'application/ogg']],
        ];
    }

    /** The largest upload accepted, in bytes. */
    public function maxBytes(): int
    {
        return max(1, (int) config('cms.media.max_upload_mb', 20)) * 1024 * 1024;
    }

    /**
     * Accepted extensions by kind, for the upload hints on screen.
     *
     * @return array<string, list<string>>
     */
    public function extensionsByKind(): array
    {
        $grouped = [self::KIND_IMAGE => [], self::KIND_VIDEO => [], self::KIND_FILE => []];

        foreach (self::types() as $extension => [$kind]) {
            $grouped[$kind][] = $extension;
        }

        return $grouped;
    }

    /** The value for a file input's `accept` attribute, optionally for one kind only. */
    public function acceptAttribute(?string $kind = null): string
    {
        $extensions = $kind === null ? array_keys(self::types()) : ($this->extensionsByKind()[$kind] ?? []);

        return implode(',', array_map(fn (string $extension): string => '.'.$extension, $extensions));
    }

    /** The kind of a stored extension (image, video or file). */
    public function kindOf(string $extension): string
    {
        return self::types()[strtolower($extension)][0] ?? self::KIND_FILE;
    }

    /**
     * Why a file cannot be added, or null when it is fine.
     */
    public function problemWith(UploadedFile $file): ?string
    {
        $name = $file->getClientOriginalName();

        if (! $file->isValid()) {
            return "{$name} did not upload: ".($file->getErrorMessage() ?: 'the upload failed').'.';
        }

        if ($file->getSize() > $this->maxBytes()) {
            return "{$name} is larger than the ".(int) config('cms.media.max_upload_mb', 20).' MB limit.';
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $type = self::types()[$extension] ?? null;

        if ($type === null) {
            return "{$name} is not an accepted file type (.".($extension !== '' ? $extension : 'none').').';
        }

        $detected = (string) $file->getMimeType();

        if (! in_array($detected, $type[1], true)) {
            return "{$name} does not look like a .{$extension} file.";
        }

        return null;
    }

    /**
     * Stores an upload and records it.
     *
     * @throws MediaRejected
     */
    public function store(UploadedFile $file, ?User $user = null, ?string $alt = null): MediaItem
    {
        if (($problem = $this->problemWith($file)) !== null) {
            throw new MediaRejected($problem);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $kind = $this->kindOf($extension);
        $path = $file->storeAs($this->directory(), $this->filename($file, $extension), self::DISK);

        if ($path === false) {
            throw new MediaRejected("{$file->getClientOriginalName()} could not be saved.");
        }

        $size = $kind === self::KIND_IMAGE ? @getimagesize($file->getRealPath()) : false;

        return MediaItem::query()->create([
            'name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'path' => $path,
            'mime_type' => (string) $file->getMimeType(),
            'kind' => $kind,
            'size' => $file->getSize(),
            'width' => $size ? $size[0] : null,
            'height' => $size ? $size[1] : null,
            'alt' => $alt !== null && $alt !== '' ? mb_substr($alt, 0, 500) : null,
            'uploaded_by' => $user?->getKey(),
        ]);
    }

    /** Removes the file and its record. */
    public function delete(MediaItem $item): void
    {
        Storage::disk(self::DISK)->delete($item->path);
        $item->delete();
    }

    /**
     * Items matching the filters: `kind` (one or several, comma separated) and `q` (name or alt text).
     *
     * @param  array{kind?: ?string, q?: ?string}  $filters
     * @return Builder<MediaItem>
     */
    public function query(array $filters = []): Builder
    {
        $query = MediaItem::query()->latest()->latest('id');

        $kinds = array_values(array_intersect(
            array_filter(array_map('trim', explode(',', (string) ($filters['kind'] ?? '')))),
            [self::KIND_IMAGE, self::KIND_VIDEO, self::KIND_FILE],
        ));

        if ($kinds !== []) {
            $query->whereIn('kind', $kinds);
        }

        if (($term = trim((string) ($filters['q'] ?? ''))) !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(fn (Builder $inner) => $inner->where('name', 'like', $like)->orWhere('alt', 'like', $like));
        }

        return $query;
    }

    /**
     * How many items there are per kind, and how much space they use.
     *
     * @return array{all: int, image: int, video: int, file: int, bytes: int}
     */
    public function counts(): array
    {
        $perKind = MediaItem::query()->selectRaw('kind, count(*) as total, sum(size) as bytes')->groupBy('kind')->get()->keyBy('kind');

        return [
            'all' => (int) $perKind->sum('total'),
            'image' => (int) ($perKind[self::KIND_IMAGE]->total ?? 0),
            'video' => (int) ($perKind[self::KIND_VIDEO]->total ?? 0),
            'file' => (int) ($perKind[self::KIND_FILE]->total ?? 0),
            'bytes' => (int) $perKind->sum('bytes'),
        ];
    }

    /** Files are grouped by month so no folder grows without end. */
    private function directory(): string
    {
        return 'media/'.date('Y/m');
    }

    /** A readable, safe name: the original name as a slug plus a short random part, so names never collide. */
    private function filename(UploadedFile $file, string $extension): string
    {
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $base = $base !== '' ? Str::limit($base, 60, '') : 'file';

        return $base.'-'.Str::lower(Str::random(6)).'.'.$extension;
    }
}
