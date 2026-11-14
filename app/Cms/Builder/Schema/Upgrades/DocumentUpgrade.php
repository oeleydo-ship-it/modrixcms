<?php

declare(strict_types=1);

namespace App\Cms\Builder\Schema\Upgrades;

/**
 * One hop in the document schema upgrade pipeline: it takes a document at
 * version `from()` and returns the same content at version `from() + 1`.
 *
 * Upgrades are pure array transforms — no database, no container, no side
 * effects — because they run on read, on every stored version row, and inside
 * the template installer.
 */
interface DocumentUpgrade
{
    /**
     * The schema version this upgrade reads.
     */
    public function from(): int;

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    public function __invoke(array $document): array;
}
