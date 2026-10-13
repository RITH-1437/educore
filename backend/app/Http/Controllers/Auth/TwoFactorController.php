<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Two-factor sign-in settings for your own account (every role, report 52).
 * New recovery codes travel to the page once, encrypted in the flashed
 * session, and are never stored in readable form.
 */
class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
    ) {}

    public function edit(Request $request): Response
    {
        $user = $request->user();
        $flashed = $request->session()->get('two_factor.recovery_codes');

        return Inertia::render('Account/TwoFactor', [
            'enabled' => $user->hasTwoFactorEnabled(),
            'confirmedAt' => $user->two_factor_confirmed_at?->toIso8601String(),
            'setup' => $this->twoFactor->setup($user),
            'recoveryCodesLeft' => $this->twoFactor->recoveryCodesLeft($user),
            'recoveryCodes' => $flashed ? Crypt::decrypt($flashed) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->twoFactor->start($request->user());

        return back()->with('success', 'Scan the QR code with your authenticator app, then enter the code it shows.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $code = $request->validate(['code' => ['required', 'string', 'max:20']])['code'];
        $codes = $this->twoFactor->confirm($request->user(), $code);

        return back()->with('success', 'Two-factor sign-in is on. Save your recovery codes now.')
            ->with('two_factor.recovery_codes', Crypt::encrypt($codes));
    }

    public function regenerate(Request $request): RedirectResponse
    {
        $password = $request->validate(['current_password' => ['required', 'string']])['current_password'];
        $codes = $this->twoFactor->regenerateRecoveryCodes($request->user(), $password);

        return back()->with('success', 'New recovery codes created; the old ones no longer work.')
            ->with('two_factor.recovery_codes', Crypt::encrypt($codes));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $password = $request->validate(['current_password' => ['required', 'string']])['current_password'];
        $wasEnabled = $request->user()->hasTwoFactorEnabled();
        $this->twoFactor->disable($request->user(), $password);

        return back()->with('success', $wasEnabled ? 'Two-factor sign-in is off.' : 'Setup cancelled.');
    }
}
