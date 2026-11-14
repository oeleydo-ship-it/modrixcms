<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema;

use App\Cms\Builder\Schema\Upgrades\DocumentUpgrade;
use RuntimeException;

/**
 * Migrates a stored page document forward to the schema version this build
 * understands.
 *
 * There is exactly one schema version today, so the pipeline is empty — but the
 * mechanism is real, because documents live in five places (pages.document,
 * pages.published_document, page_versions.document, page_variants.document and
 * templates.payload) and a schema change has to reach all of them lazily rather
 * than through a giant backfill.
 *
 * To add version 2: write an upgrade class implementing DocumentUpgrade with
 * `from() === 1`, register it in `upgrades()`, and bump CURRENT_VERSION.
 * Everything read after that deploy is upgraded on the way through, and the
 * next save persists the upgraded shape.
 */
final class DocumentUpgrader
{
    /**
     * The schema version this build writes.
     */
    public const CURRENT_VERSION = 1;

    /** @var array<int, DocumentUpgrade>|null */
    private ?array $pipeline = null;

    /**
     * The registered upgrades, keyed by the version they read.
     *
     * @return list<DocumentUpgrade>
     */
    public function upgrades(): array
    {
        return [
            // v1 is the first published schema, so nothing to upgrade from yet.
            // e.g. new Upgrades\UpgradeV1ToV2,
        ];
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    public function upgrade(array $document): array
    {
        $version = $this->versionOf($document);

        if ($version > self::CURRENT_VERSION) {
            throw new RuntimeException(sprintf(
                'Page document is at schema version %d but this build only understands up to %d. '
                .'Refusing to downgrade — deploy a newer build.',
                $version,
                self::CURRENT_VERSION,
            ));
        }

        $pipeline = $this->pipeline();

        while ($version < self::CURRENT_VERSION) {
            $upgrade = $pipeline[$version] ?? null;

            if ($upgrade === null) {
                throw new RuntimeException(sprintf(
                    'No document upgrade registered from schema version %d. The pipeline has a hole in it.',
                    $version,
                ));
            }

            $document = $upgrade($document);
            $version++;
            $document['schema_version'] = $version;
        }

        $document['schema_version'] = self::CURRENT_VERSION;
        unset($document['schemaVersion']);

        return $document;
    }

    /**
     * @param  array<string, mixed>  $document
     */
    public function needsUpgrade(array $document): bool
    {
        return $this->versionOf($document) < self::CURRENT_VERSION;
    }

    /**
     * @param  array<string, mixed>  $document
     */
    public function versionOf(array $document): int
    {
        return (int) ($document['schema_version'] ?? $document['schemaVersion'] ?? 1);
    }

    /**
     * @return array<int, DocumentUpgrade>
     */
    private function pipeline(): array
    {
        if ($this->pipeline !== null) {
            return $this->pipeline;
        }

        $pipeline = [];

        foreach ($this->upgrades() as $upgrade) {
            $pipeline[$upgrade->from()] = $upgrade;
        }

        return $this->pipeline = $pipeline;
    }
}
