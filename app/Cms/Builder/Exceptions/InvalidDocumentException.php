<?php

declare(strict_types=1);

namespace App\Cms\Builder\Exceptions;

use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * A page document failed structural validation. Carries the full list of
 * problems so the builder can point at the offending node.
 */
final class InvalidDocumentException extends RuntimeException
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('The page document is invalid: '.implode(' ', $errors));
    }

    /**
     * Surfaces as a 422 with the errors under `document` when it escapes a
     * controller.
     */
    public function toValidationException(): ValidationException
    {
        return ValidationException::withMessages(['document' => $this->errors]);
    }
}
