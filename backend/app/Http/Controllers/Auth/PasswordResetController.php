<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\PasswordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** "Forgot password" flow for guests (web). */
class PasswordResetController extends Controller
{
    public const SENT = 'If an active account uses that email, a link to reset the password is on its way. Check your inbox.';

    public function __construct(
        private readonly PasswordService $passwords,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', ['status' => session('status')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->passwords->sendResetLink($request->validate(['email' => ['required', 'string', 'email']])['email']);

        // Same answer whether or not the email exists (no account enumeration).
        return back()->with('status', self::SENT);
    }

    public function edit(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $this->passwords->reset($request->validated());

        return redirect()->route('login')->with('status', 'Your password has been reset. Sign in with the new password.');
    }
}
