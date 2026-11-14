<?php

declare(strict_types=1);

namespace App\Cms\Builder\Exceptions;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Two editors saved the same page from the same base version.
 *
 * The save is rejected rather than merged: the response carries the version the
 * server holds and the document that won, so the builder can reconcile without
 * a second round trip.
 */
final class StaleDocumentException extends RuntimeException implements Responsable
{
    /**
     * @param  array<string, mixed>  $currentDocument
     */
    public function __construct(
        public readonly string $pageId,
        public readonly int $expectedVersion,
        public readonly int $currentVersion,
        public readonly array $currentDocument = [],
    ) {
        parent::__construct(sprintf(
            'Page %s has moved on: the save was based on version %d but the stored version is %d.',
            $pageId,
            $expectedVersion,
            $currentVersion,
        ));
    }

    public function toResponse($request): JsonResponse
    {
        return new JsonResponse([
            'message' => 'This page was changed by someone else while you were editing.',
            'error' => 'stale_document',
            'expected_version' => $this->expectedVersion,
            'current_version' => $this->currentVersion,
            'current_document' => $this->currentDocument,
        ], Response::HTTP_CONFLICT);
    }
}
