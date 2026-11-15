<?php

namespace App\Cms\Builder;

use App\Cms\Builder\Exceptions\InvalidDocumentException;
use App\Cms\Builder\Exceptions\StaleDocumentException;
use App\Cms\Builder\Schema\DocumentUpgrader;
use App\Cms\Builder\Schema\DocumentValidator;
use App\Cms\Themes\DesignTokens;
use App\Models\Page;
use App\Models\PageVersion;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Saving, publishing and restoring the visual builder's page documents.
 *
 * Saves carry the version they started from (optimistic concurrency): if someone else
 * saved in between, the save is refused with a 409 instead of overwriting their work.
 * A snapshot goes to page_versions every SNAPSHOT_EVERY saves, on a labelled save, on
 * publish and before a restore, so History stays useful without a row per keystroke.
 */
class PageDocuments
{
    public const SNAPSHOT_EVERY = 10;

    public function __construct(
        private DocumentValidator $validator,
        private DocumentUpgrader $upgrader,
    ) {}

    /** The draft document, upgraded to the current schema (an empty page when there is none). */
    public function draft(Page $page): array
    {
        return $this->upgrader->upgrade($page->document ?? self::blank());
    }

    /**
     * @param  array<string, mixed>  $document
     *
     * @throws StaleDocumentException
     * @throws InvalidDocumentException
     */
    public function save(Page $page, array $document, int $expectedVersion, ?int $userId = null, ?string $label = null): Page
    {
        $document = $this->upgrader->upgrade($document);
        $this->validator->validate($document);

        return DB::transaction(function () use ($page, $document, $expectedVersion, $userId, $label): Page {
            $locked = $this->lock($page, $expectedVersion);
            $savesSince = $locked->saves_since_snapshot + 1;
            $snapshot = $label !== null || $savesSince >= self::SNAPSHOT_EVERY;

            if ($snapshot && $locked->document !== null) {
                $this->snapshot($locked, $userId, $label);
            }

            $locked->forceFill([
                'editor' => Page::EDITOR_BUILDER,
                'document' => $document,
                'schema_version' => DocumentUpgrader::CURRENT_VERSION,
                'version' => $locked->version + 1,
                'saves_since_snapshot' => $snapshot ? 0 : $savesSince,
            ])->save();

            return $locked;
        });
    }

    /**
     * Makes the draft what visitors see.
     *
     * @throws StaleDocumentException
     * @throws InvalidDocumentException
     */
    public function publish(Page $page, ?int $userId = null, ?int $expectedVersion = null): Page
    {
        return DB::transaction(function () use ($page, $userId, $expectedVersion): Page {
            $locked = $this->lock($page, $expectedVersion);
            $document = $this->draft($locked);
            $this->validator->validate($document);

            $this->snapshot($locked, $userId, 'Published');

            $locked->forceFill([
                'editor' => Page::EDITOR_BUILDER,
                'document' => $document,
                'published_document' => $document,
                'published_version' => $locked->version,
                'published_at' => now(),
                // The site header and footer are never pages of their own.
                'is_published' => $locked->role === null ? true : $locked->is_published,
            ])->save();

            return $locked;
        });
    }

    /**
     * Puts an earlier version back into the draft. The current draft is kept as a version
     * first, so a restore can itself be undone.
     *
     * @throws InvalidDocumentException
     */
    public function restore(Page $page, PageVersion $version, ?int $userId = null): Page
    {
        if ($version->page_id !== $page->id) {
            throw new InvalidArgumentException('That version belongs to a different page.');
        }

        $document = $this->upgrader->upgrade($version->document ?? []);
        $this->validator->validate($document);

        return DB::transaction(function () use ($page, $version, $document, $userId): Page {
            $locked = $this->lock($page, null);
            $this->snapshot($locked, $userId, sprintf('Before restoring v%d', $version->version));

            $locked->forceFill([
                'document' => $document,
                'schema_version' => DocumentUpgrader::CURRENT_VERSION,
                'version' => $locked->version + 1,
                'saves_since_snapshot' => 0,
            ])->save();

            return $locked;
        });
    }

    /** An empty page: no sections, settings that follow the theme. */
    public static function blank(): array
    {
        return ['schema_version' => DocumentUpgrader::CURRENT_VERSION, 'settings' => self::themedSettings(), 'sections' => []];
    }

    /**
     * Page settings linked to Appearance → Theme settings, for new and converted pages: the content
     * width follows the theme; colours and fonts come from the --site-* variables.
     *
     * @return array<string, mixed>
     */
    public static function themedSettings(): array
    {
        return ['contentWidth' => DesignTokens::ref('content_width')];
    }

    /** @throws StaleDocumentException */
    private function lock(Page $page, ?int $expectedVersion): Page
    {
        /** @var Page $locked */
        $locked = Page::query()->whereKey($page->getKey())->lockForUpdate()->firstOrFail();

        if ($expectedVersion !== null && $locked->version !== $expectedVersion) {
            throw new StaleDocumentException(
                pageId: (string) $locked->id,
                expectedVersion: $expectedVersion,
                currentVersion: $locked->version,
                currentDocument: $locked->document ?? [],
            );
        }

        return $locked;
    }

    private function snapshot(Page $page, ?int $userId, ?string $label): void
    {
        PageVersion::query()->create([
            'page_id' => $page->id,
            'version' => $page->version,
            'schema_version' => $page->schema_version,
            'document' => $page->document ?? self::blank(),
            'label' => $label,
            'created_by_id' => $userId,
            'created_at' => now(),
        ]);
    }
}
