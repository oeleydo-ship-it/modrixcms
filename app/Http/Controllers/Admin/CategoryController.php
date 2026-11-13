<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\Slugs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::withCount('posts')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return back()->with('status', 'Category added.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return back()->with('status', 'Category saved.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // Posts keep existing; they become uncategorised.
        $category->delete();

        return back()->with('status', 'Category deleted.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $request->merge(['slug' => Slugs::from($request->input('slug') ?: $request->input('name'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'max:100', Rule::unique('categories', 'slug')->ignore($category)],
        ]);
    }
}
