<?php

namespace App\Http\Controllers\Builder;

use App\Cms\Builder\Schema\DesignBlockLibrary;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** The builder's Designs tab: ready-made sections (heroes, features, pricing, footers…). */
class DesignBlockController extends Controller
{
    public function __construct(private DesignBlockLibrary $library) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->library->all(),
            'categories' => $this->library->categories(),
        ]);
    }

    public function show(string $key): JsonResponse
    {
        $block = $this->library->find($key);

        abort_if($block === null, 404);

        return response()->json(['data' => $block]);
    }
}
