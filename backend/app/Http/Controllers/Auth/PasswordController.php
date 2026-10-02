<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Services\PasswordService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Change your own password while signed in (every role). */
class PasswordController extends Controller
{
    public function __construct(
        private readonly PasswordService $passwords,
    ) {}

    public function edit(): Response
    {
        return Inertia::render('Account/Password');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $this->passwords->change($request->user(), $request->validated('current_password'), $request->validated('password'));

        // This session stays signed in: AuthenticateSession stores the new
        // hash at the end of the request; other sessions no longer match it.
        return back()->with('success', 'Password changed. Your other sessions have been signed out.');
    }
}
