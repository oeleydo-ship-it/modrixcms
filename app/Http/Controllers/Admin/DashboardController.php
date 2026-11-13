<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'Pages' => Page::count(),
                'Published posts' => Post::published()->count(),
                'Drafts & scheduled' => Post::count() - Post::published()->count(),
                'Categories' => Category::count(),
            ],
            'recentPosts' => Post::latest('updated_at')->take(5)->get()->map(fn (Post $post) => [
                'id' => $post->id, 'title' => $post->title, 'status' => $post->status(), 'updated' => $post->updated_at->diffForHumans(),
            ]),
            'recentPages' => Page::latest('updated_at')->take(5)->get(['id', 'title', 'slug']),
        ]);
    }
}
