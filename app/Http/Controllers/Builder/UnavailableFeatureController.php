<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Pickers the builder still asks for (forms, products, popups, booking, global components)
 * that belong to a funnel platform, not to Modrix CMS: they answer with an empty list, so
 * the builder shows "none yet" instead of an error.
 */
class UnavailableFeatureController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 0]]);
    }
}
