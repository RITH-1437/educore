<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Services\ErrorLogRecorder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // API clients such as Swagger UI may send `Accept: */*`. Keep API
        // failures JSON instead of redirecting them to the Inertia login page.
        $exceptions->shouldRenderJsonWhen(
            fn ($request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (BusinessRuleException $exception, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 409);
            }

            return back()->with('error', $exception->getMessage());
        });

        // Record 404 and 5xx responses (Module — System Error Logs).
        //
        // This finalises the *rendered response* rather than hooking `report()`:
        // Laravel lists `HttpException`, and so `NotFoundHttpException`, in
        // `$dontReport`, so a 404 never reaches the reporter. The rendered response
        // is the only place both 404s and 5xxes are visible with their final status
        // code. The recorder stores 404 and >= 500 only — 401/403/409/422 are normal
        // control flow and are left out on purpose.
        //
        // Signature is `($response, $exception, $request)`; returning `$response`
        // unchanged is required — the recorder only observes.
        $exceptions->respond(function (SymfonyResponse $response, Throwable $exception, $request) {
            if ($request instanceof Request) {
                app(ErrorLogRecorder::class)->record($request, $response->getStatusCode(), $exception);
            }

            return $response;
        });
    })->create();
