<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceSession;
use App\Models\User;
use App\Policies\AssignmentPolicy;
use App\Policies\AttendancePolicy;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Not discoverable by name: attendance is authorized per section/student.
        Gate::policy(AttendanceSession::class, AttendancePolicy::class);
        Gate::policy(AssignmentSubmission::class, AssignmentPolicy::class);

        // Audit trail of sign-in activity (module 9.24): who signed in or out,
        // failed attempts (the email tried, never the password) and lockouts.
        Event::listen(Login::class, fn (Login $e) => app(AuditLogger::class)->record('auth.login', $e->user, description: "Guard: {$e->guard}", actor: $e->user));
        Event::listen(Logout::class, fn (Logout $e) => $e->user ? app(AuditLogger::class)->record('auth.logout', $e->user, actor: $e->user) : null);
        Event::listen(Failed::class, fn (Failed $e) => app(AuditLogger::class)->record('auth.failed', $e->user, description: 'Sign-in failed for '.($e->credentials['email'] ?? 'an unknown email').'.'));
        Event::listen(Lockout::class, fn (Lockout $e) => app(AuditLogger::class)->record('auth.lockout', null, description: 'Too many sign-in attempts for '.($e->request->input('email') ?? 'an unknown email').'.'));

        // Institution-wide analytics (module 9.23): managers only — Faculty Admin
        // would need unit scoping first (`skills/analytics-reporting` §12).
        Gate::define('view-analytics', fn (User $user) => $user->isRole(Role::SuperAdmin->value) || $user->isRole(Role::UniversityAdmin->value));

        // Test notifications from the preferences page (modules 9.20 / 9.21).
        RateLimiter::for('notification-test', fn (Request $request) => Limit::perMinute(3)->by((string) $request->user()?->getKey()));

        // Public document verification (module 9.17).
        RateLimiter::for('verification', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->input('email').'|'.$request->ip(),
            );
        });
    }
}
