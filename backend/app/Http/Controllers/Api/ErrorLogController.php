<?php

namespace App\Http\Controllers\Api;

use App\Dto\ErrorLogs\ErrorLogListFilters;
use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorLogResource;
use App\Models\ErrorLog;
use App\Services\ErrorLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * System error-log endpoints.
 *
 * Read-only by design: rows are produced by `ErrorLogRecorder` from 404 and 5xx
 * responses, and nothing may create, edit or delete them — the same
 * append-only stance `audit_logs` takes (`skills/audit-logging/SKILL.md` §4).
 * There is therefore no store/update/destroy endpoint on purpose.
 *
 * Restricted to Super Admin because a row exposes an exception class, a message
 * and the originating path (`ErrorLogPolicy`).
 */
class ErrorLogController extends Controller
{
    public function __construct(
        private readonly ErrorLogService $errorLogs,
    ) {}

    #[OA\Get(
        path: '/error-logs',
        summary: 'List system errors',
        description: 'Returns recorded HTTP 404 and 5xx responses, newest first. Supports case-insensitive search over path, message and exception class, filtering by exact status code, by group (`server` = 5xx, `not_found` = 404) or by HTTP method, whitelisted sorting and a capped page size.',
        operationId: 'listErrorLogs',
        tags: ['System Error Logs'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'search',
                description: 'Case-insensitive partial match against `url`, `message` or `exception_class`.',
                schema: new OA\Schema(type: 'string'),
                example: 'QueryException'
            ),
            new OA\QueryParameter(
                name: 'filters[status_code]',
                description: 'Exact status code, e.g. 404, 500, 503.',
                schema: new OA\Schema(type: 'integer', format: 'int32'),
                example: 404,
            ),
            new OA\QueryParameter(
                name: 'filters[status_group]',
                description: 'Group errors the way an admin reads them.',
                schema: new OA\Schema(type: 'string', enum: [ErrorLogListFilters::GROUP_SERVER, ErrorLogListFilters::GROUP_NOT_FOUND]),
            ),
            new OA\QueryParameter(
                name: 'filters[method]',
                description: 'Only this HTTP method.',
                schema: new OA\Schema(type: 'string', enum: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']),
            ),
            new OA\QueryParameter(
                name: 'sort_by',
                description: 'Whitelisted sort column.',
                schema: new OA\Schema(type: 'string', enum: ErrorLogListFilters::SORTABLE, default: 'created_at'),
            ),
            new OA\QueryParameter(
                name: 'sort_dir',
                description: 'Sort direction.',
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc'),
            ),
            new OA\QueryParameter(
                name: 'per_page',
                description: 'Number of records per page (defaults to 20, max 100).',
                schema: new OA\Schema(type: 'integer', format: 'int32', default: 20, maximum: 100),
            ),
            new OA\QueryParameter(
                name: 'page',
                description: 'Page number for the paginated collection.',
                schema: new OA\Schema(type: 'integer', format: 'int32', default: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated collection of recorded errors.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorLogCollection')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Authenticated but not a Super Admin.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ErrorLog::class);

        $errorLogs = $this->errorLogs->paginate(ErrorLogListFilters::fromRequest($request));

        return ErrorLogResource::collection($errorLogs);
    }

    #[OA\Get(
        path: '/error-logs/{errorLog}',
        summary: 'Fetch one recorded error',
        operationId: 'getErrorLog',
        tags: ['System Error Logs'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'errorLog',
                description: 'Identifier of the error-log row.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The requested error.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorLogResourceResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Authenticated but not a Super Admin.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Error-log row not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function show(ErrorLog $errorLog): ErrorLogResource
    {
        $this->authorize('view', $errorLog);

        return new ErrorLogResource($errorLog);
    }
}
