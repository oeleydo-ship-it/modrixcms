<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Asks for the admin's password again before code can be installed, enabled or removed. */
class ConfirmPasswordController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Admin/Auth/ConfirmPassword');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'That password is not correct.']);
        }

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('admin.dashboard'));
    }
}
