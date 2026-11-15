<?php

namespace Modrix\Testimonials\Http;

use App\Cms\Cms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modrix\Testimonials\Models\Testimonial;

class SubmitController
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(Cms::setting('modrix/testimonials', 'allow_public_submissions', true), 403);

        $back = redirect()->to(url()->previous().'#'.($request->input('_anchor') ?: 'leave-review'));

        if ($request->filled('_tm_trap')) {
            return $back->with('testimonials.submitted', true);
        }

        $data = $request->validateWithBag('testimonials', [
            'author_name' => ['required', 'string', 'max:120'],
            'author_role' => ['nullable', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'author_email' => ['nullable', 'email', 'max:200'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'quote' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $requireModeration = (bool) Cms::setting('modrix/testimonials', 'require_moderation', true);

        Testimonial::query()->create([
            ...$data,
            'status' => $requireModeration ? 'pending' : 'approved',
            'verified' => false,
            'is_featured' => false,
        ]);

        return $back->with('testimonials.submitted', true);
    }
}
