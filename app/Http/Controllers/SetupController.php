<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\FirstRunSetup;
use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * First-run setup: creates the first admin on a site that has none (see FirstRunSetup).
 */
class SetupController extends Controller
{
    public function create(FirstRunSetup $setup, Settings $settings): Response|RedirectResponse
    {
        if (! $setup->isPending()) {
            return redirect()->route('admin.login');
        }

        return Inertia::render('Setup', [
            'requiresToken' => $setup->requiresToken(),
            'defaults' => ['site_name' => $settings->get('site_name')],
        ]);
    }

    public function store(Request $request, FirstRunSetup $setup, Settings $settings): RedirectResponse
    {
        if (! $setup->isPending()) {
            return redirect()->route('admin.login');
        }

        $rules = [
            'site_name' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'confirmed', Password::min(10)],
            'starter_content' => ['boolean'],
        ];

        if ($setup->requiresToken()) {
            $rules['setup_token'] = ['required', 'string'];
        }

        $data = $request->validate($rules);

        if ($setup->requiresToken() && ! $setup->tokenMatches($data['setup_token'])) {
            throw ValidationException::withMessages(['setup_token' => 'That setup token is not right.']);
        }

        $admin = DB::transaction(function () use ($data, $settings): ?User {
            // Two visitors submitting at once: only the first one creates the admin.
            if (User::query()->where('is_admin', true)->lockForUpdate()->exists()) {
                return null;
            }

            $admin = User::firstOrNew(['email' => $data['email']]);
            $admin->name = $data['name'];
            $admin->password = $data['password'];
            $admin->is_admin = true;
            $admin->save();

            $settings->set(['site_name' => $data['site_name']]);

            if ($data['starter_content'] ?? false) {
                (new DatabaseSeeder)->starterContent($admin);
            }

            return $admin;
        });

        if ($admin === null) {
            return redirect()->route('admin.login');
        }

        $setup->markComplete();

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('status', 'Your site is ready. Welcome!');
    }
}
