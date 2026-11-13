<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\PageBuilder\PageBuilder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Admin account from ADMIN_EMAIL / ADMIN_PASSWORD in .env, plus starter content.
     * In production, prefer: php artisan site:admin you@example.com
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        $admin = null;

        if ($email && $password) {
            $admin = User::firstOrNew(['email' => $email]);
            $admin->name = $admin->name ?: 'Admin';
            $admin->password = $password;
            $admin->is_admin = true;
            $admin->save();
        } else {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set: no admin created. Run php artisan site:admin <email>.');
        }

        // The landing page, editable in Admin → Pages → Home with the page builder.
        if (! Page::where('is_home', true)->exists()) {
            Page::firstOrCreate(['slug' => 'home'], [
                'title' => 'Home',
                'blocks' => PageBuilder::landingBlocks(),
                'is_published' => true,
                'is_home' => true,
            ]);
        }

        Page::firstOrCreate(['slug' => 'about'], [
            'title' => 'About',
            'blocks' => [
                PageBuilder::make('page_header', ['eyebrow' => 'About', 'title' => 'About Modrix Commerce']),
                PageBuilder::make('text', ['content' => 'Modrix Commerce is a free, open-source e-commerce platform built on Laravel 13. Install it on your own server, keep every order and every byte of data, and add free themes and modules from the Modrix Cloud marketplace.']),
                PageBuilder::make('cta'),
            ],
            'meta_description' => 'What Modrix Commerce is and who builds it.',
            'is_published' => true,
            'show_in_menu' => true,
            'menu_order' => 1,
        ]);

        $news = Category::firstOrCreate(['slug' => 'news'], ['name' => 'News']);
        Category::firstOrCreate(['slug' => 'guides'], ['name' => 'Guides']);

        Post::firstOrCreate(['slug' => 'introducing-modrix-commerce'], [
            'title' => 'Introducing Modrix Commerce',
            'category_id' => $news->id,
            'author_id' => $admin?->id,
            'excerpt' => 'A free, open-source store you deploy on your own server, with a marketplace of free themes and modules.',
            'content' => "Modrix Commerce is now available.\n\n- **Free and MIT licensed**\n- **Self-hosted** on shared hosting, a VPS or a Git platform\n- **Modrix Cloud marketplace** for free themes and modules\n\nDownload it from GitHub and run the web installer at `/install`.",
            'published_at' => now(),
        ]);
    }
}
