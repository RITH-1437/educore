<?php

namespace App\Providers;

use App\Models\AssignmentSubmission;
use App\Models\AttendanceSession;
use App\Policies\AssignmentPolicy;
use App\Policies\AttendancePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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

        // Public document verification (module 9.17).
        RateLimiter::for('verification', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->input('email').'|'.$request->ip(),
            );
        });
    }
}
