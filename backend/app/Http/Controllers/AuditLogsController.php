<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\AuditLogController as Api;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Audit trail viewer (module 9.24) — Super Admin, read-only. */
class AuditLogsController extends Controller
{
    public function __construct(
        private readonly Api $api,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        return Inertia::render('AuditLogs/Index', [
            'logs' => AuditLogResource::collection($this->api->search($request)),
            'filters' => ['search' => $request->input('search'), 'area' => $request->input('filters.area'), 'from' => $request->input('from'), 'to' => $request->input('to')],
            'areas' => Api::areas(),
        ]);
    }

    public function show(AuditLog $auditLog): Response
    {
        $this->authorize('view', $auditLog);

        return Inertia::render('AuditLogs/Show', ['log' => (new AuditLogResource($auditLog->load('actor')))->resolve()]);
    }
}
