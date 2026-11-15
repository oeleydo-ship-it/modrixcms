<?php

namespace Modrix\Faq\Http\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modrix\Faq\Models\FaqItem;

class ItemsController
{
    public function index(Request $request): Response
    {
        $category = $request->query('category');
        $search = $request->query('q');

        $items = FaqItem::query()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($search, fn ($q) => $q->where(fn ($sub) => $sub->where('question', 'like', "%{$search}%")->orWhere('answer_markdown', 'like', "%{$search}%")))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (FaqItem $item): array => [
                'id' => $item->id,
                'category' => $item->category,
                'question' => $item->question,
                'answer_markdown' => $item->answer_markdown,
                'sort_order' => $item->sort_order,
                'is_published' => $item->is_published,
                'helpful_yes' => $item->helpful_yes,
                'helpful_no' => $item->helpful_no,
            ]);

        $categories = FaqItem::query()->distinct()->orderBy('category')->pluck('category')->values()->all();

        return Inertia::render('plugin:modrix/faq/ItemsIndex', [
            'items' => $items,
            'categories' => $categories,
            'filters' => [
                'category' => $category ?: '',
                'q' => $search ?: '',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort_order'] = (int) ($data['sort_order'] ?: ((FaqItem::query()->max('sort_order') ?? 0) + 1));

        FaqItem::query()->create($data);

        return back()->with('status', 'FAQ item added.');
    }

    public function update(Request $request, FaqItem $item): RedirectResponse
    {
        $item->update($this->validated($request));

        return back()->with('status', 'FAQ item updated.');
    }

    public function destroy(FaqItem $item): RedirectResponse
    {
        $item->delete();

        return back()->with('status', 'FAQ item deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:80'],
            'question' => ['required', 'string', 'max:300'],
            'answer_markdown' => ['required', 'string', 'max:20000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        return $data + [
            'is_published' => $request->boolean('is_published', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
