<?php

namespace Modrix\Faq\Http;

use App\Cms\Cms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modrix\Faq\Models\FaqItem;

class VoteController
{
    public function __invoke(Request $request, FaqItem $item): JsonResponse
    {
        abort_unless(Cms::setting('modrix/faq', 'allow_voting', true) && $item->is_published, 403);

        $data = $request->validate([
            'vote' => ['required', 'in:yes,no'],
        ]);

        if ($data['vote'] === 'yes') {
            $item->increment('helpful_yes');
        } else {
            $item->increment('helpful_no');
        }

        $item->refresh();

        return response()->json([
            'helpful_yes' => $item->helpful_yes,
            'helpful_no' => $item->helpful_no,
        ]);
    }
}
