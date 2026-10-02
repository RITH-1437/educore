<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * The audit trail (module 9.24): read-only, Super Admin only. There are no
 * create / update / delete endpoints — rows are written by `AuditLogger`.
 */
class AuditLogController extends Controller
{
    #[OA\Get(
        path: '/audit-logs',
        summary: 'Search the audit trail',
        description: 'Newest first. `filters[area]` is the part of the action before the dot (e.g. `grades`, `invoice`, `auth`); `filters[action]` an exact action; `search` matches description, actor name / email, or action.',
        operationId: 'listAuditLogs',
        tags: ['Audit'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'search', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'filters[area]', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'filters[action]', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'filters[actor_id]', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'from', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\QueryParameter(name: 'to', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\QueryParameter(name: 'page', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Audit entries.', content: new OA\JsonContent(ref: '#/components/schemas/AuditLogCollection')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid filter.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AuditLog::class);

        return AuditLogResource::collection($this->search($request));
    }

    #[OA\Get(
        path: '/audit-logs/{auditLog}',
        summary: 'One audit entry with before / after values',
        operationId: 'getAuditLog',
        tags: ['Audit'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'auditLog', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'The entry.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/AuditLog')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(AuditLog $auditLog): AuditLogResource
    {
        $this->authorize('view', $auditLog);

        return new AuditLogResource($auditLog->load('actor'));
    }

    /** Shared with the web page. */
    public function search(Request $request): LengthAwarePaginator
    {
        return $this->searchQuery($request)->paginate(25)->withQueryString();
    }

    /**
     * The filtered, ordered trail (shared by the list and the CSV export).
     *
     * @return Builder<AuditLog>
     */
    public function searchQuery(Request $request): Builder
    {
        $v = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'filters.area' => ['nullable', 'string', 'max:50', 'regex:/^[a-z_]+$/'],
            'filters.action' => ['nullable', 'string', 'max:100'],
            'filters.actor_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $f = $v['filters'] ?? [];

        return AuditLog::query()
            ->with('actor:id,name,email,deleted_at')
            ->when($f['area'] ?? null, fn ($q, $area) => $q->where('action', 'like', "{$area}.%"))
            ->when($f['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->when($f['actor_id'] ?? null, fn ($q, $id) => $q->where('actor_id', $id))
            ->when($v['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($v['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->when($v['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('description', 'ilike', "%{$s}%")
                ->orWhere('action', 'ilike', "%{$s}%")
                ->orWhereHas('actor', fn ($a) => $a->where('name', 'ilike', "%{$s}%")->orWhere('email', 'ilike', "%{$s}%"))))
            ->latest('created_at')
            ->latest('id');
    }

    /**
     * Distinct areas present in the trail, for the filter.
     *
     * @return list<string>
     */
    public static function areas(): array
    {
        return AuditLog::query()->selectRaw("distinct split_part(action, '.', 1) as area")->orderBy('area')->pluck('area')->all();
    }
}
