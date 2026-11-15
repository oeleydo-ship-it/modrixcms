<?php

namespace Modrix\Testimonials\Http\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modrix\Testimonials\Models\Testimonial;

class ItemsController
{
    public function index(Request $request): Response
    {
        $status = $request->query('status', 'all');

        $items = Testimonial::query()
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Testimonial $t): array => [
                'id' => $t->id,
                'author_name' => $t->author_name,
                'author_role' => $t->author_role ?? '',
                'company' => $t->company ?? '',
                'author_email' => $t->author_email ?? '',
                'rating' => $t->rating,
                'quote' => $t->quote,
                'status' => $t->status,
                'is_featured' => $t->is_featured,
                'verified' => $t->verified,
                'sort_order' => $t->sort_order,
                'created' => $t->created_at?->diffForHumans(),
            ]);

        $approvedCount = Testimonial::query()->approved()->count();

        return Inertia::render('plugin:modrix/testimonials/ItemsIndex', [
            'items' => $items,
            'stats' => [
                'all' => Testimonial::query()->count(),
                'approved' => $approvedCount,
                'pending' => Testimonial::query()->where('status', 'pending')->count(),
                'rejected' => Testimonial::query()->where('status', 'rejected')->count(),
                'average_rating' => $approvedCount > 0 ? round((float) Testimonial::query()->approved()->avg('rating'), 1) : 0,
            ],
            'filters' => ['status' => $status ?: 'all'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Testimonial::query()->create($this->validated($request));

        return back()->with('status', 'Testimonial added.');
    }

    public function update(Request $request, Testimonial $item): RedirectResponse
    {
        $item->update($this->validated($request));

        return back()->with('status', 'Testimonial updated.');
    }

    public function destroy(Testimonial $item): RedirectResponse
    {
        $item->delete();

        return back()->with('status', 'Testimonial deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:120'],
            'author_role' => ['nullable', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'author_email' => ['nullable', 'email', 'max:200'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'quote' => ['required', 'string', 'max:2000'],
            'status' => ['required', 'in:approved,pending,rejected'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        return $data + [
            'is_featured' => $request->boolean('is_featured'),
            'verified' => $request->boolean('verified'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
