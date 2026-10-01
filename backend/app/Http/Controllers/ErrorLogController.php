<?php

namespace App\Http\Controllers;

use App\Dto\ErrorLogs\ErrorLogListFilters;
use App\Http\Resources\ErrorLogResource;
use App\Models\ErrorLog;
use App\Services\ErrorLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * System error-log screens.
 *
 * Reached by typing `/error-logs` rather than from the sidebar: this is a
 * diagnostic tool for a Super Admin chasing a failure, not a destination
 * anyone navigates to while working.
 */
class ErrorLogController extends Controller
{
    public function __construct(
        private readonly ErrorLogService $errorLogs,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ErrorLog::class);

        $filters = ErrorLogListFilters::fromRequest($request);

        $errorLogs = $this->errorLogs->paginate($filters)
            ->withQueryString()
            ->appends($filters->toQueryString());

        return Inertia::render('ErrorLogs/Index', [
            'errorLogs' => ErrorLogResource::collection($errorLogs),
            'filters' => [
                'search' => $filters->search,
                'status_code' => $filters->statusCode,
                'status_group' => $filters->statusGroup,
                'method' => $filters->method,
            ],
            'summary' => $this->errorLogs->summary(),
        ]);
    }

    public function show(ErrorLog $errorLog): Response
    {
        $this->authorize('view', $errorLog);

        return Inertia::render('ErrorLogs/Show', [
            // `resolve()` instead of the resource instance: Inertia treats a bare
            // `JsonResource` as a `Responsable`, so it would nest the payload under
            // `data` and the page would read `props.errorLog.data.status_code`.
            'errorLog' => (new ErrorLogResource($errorLog->load('user:id,name,email')))->resolve(),
        ]);
    }
}
