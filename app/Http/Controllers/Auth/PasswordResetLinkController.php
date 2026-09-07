<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StorePasswordResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    public function store(StorePasswordResetLinkRequest $request): RedirectResponse
    {
        Password::sendResetLink(
            $request->only('email')
        );

        // Always the same response to avoid email enumeration.
        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
