<?php

namespace App\Http\Controllers\Api;

use App\Dto\User\CreateUserData;
use App\Dto\User\UpdateUserData;
use App\Dto\User\UserListFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * User administration endpoints.
 *
 * All routes are protected by `auth:sanctum` and the `role:super-admin`
 * middleware, so every operation documents both 401 and 403 responses.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    #[OA\Get(
        path: '/users',
        summary: 'List users',
        description: 'Returns a paginated list of users, newest first. Supports case-insensitive search over name and e-mail, filtering by role, and a custom page size (capped at 100).',
        operationId: 'listUsers',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'search',
                description: 'Case-insensitive partial match against `name` or `email`.',
                schema: new OA\Schema(type: 'string'),
                example: 'sokha'
            ),
            new OA\QueryParameter(
                name: 'role_id',
                description: 'Only return users holding this role identifier.',
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 5
            ),
            new OA\QueryParameter(
                name: 'per_page',
                description: 'Number of records per page (defaults to 15, max 100).',
                schema: new OA\Schema(type: 'integer', format: 'int32', default: 15, maximum: 100),
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
                description: 'Paginated collection of users.',
                content: new OA\JsonContent(ref: '#/components/schemas/UserCollection')
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
        $this->authorize('viewAny', User::class);

        $users = $this->users->list(UserListFilters::fromInput($request->query()));

        return UserResource::collection($users);
    }

    #[OA\Post(
        path: '/users',
        summary: 'Create a user',
        operationId: 'createUser',
        tags: ['Users'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreUserRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User created.',
                content: new OA\JsonContent(ref: '#/components/schemas/UserResourceResponse')
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
                response: 422,
                description: 'Validation failed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = $this->users->create(CreateUserData::fromValidated($request->validated()));

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/users/{user}',
        summary: 'Fetch a user',
        operationId: 'getUser',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'user',
                description: 'Identifier of the user.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The requested user.',
                content: new OA\JsonContent(ref: '#/components/schemas/UserResourceResponse')
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
                description: 'User not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user->load('role', 'department:id,name'));
    }

    #[OA\Put(
        path: '/users/{user}',
        summary: 'Update a user',
        operationId: 'updateUser',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'user',
                description: 'Identifier of the user.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateUserRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated user.',
                content: new OA\JsonContent(ref: '#/components/schemas/UserResourceResponse')
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
                description: 'User not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    #[OA\Patch(
        path: '/users/{user}',
        summary: 'Update a user (PATCH)',
        description: 'Uses the same validation as PUT: `name`, `email`, and `role_id` remain required; password is optional.',
        operationId: 'patchUser',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'user',
                description: 'Identifier of the user.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateUserRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated user.',
                content: new OA\JsonContent(ref: '#/components/schemas/UserResourceResponse')
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
                description: 'User not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $this->authorize('update', $user);

        $user = $this->users->update($user, UpdateUserData::fromValidated($request->validated()));

        return new UserResource($user);
    }

    #[OA\Delete(
        path: '/users/{user}',
        summary: 'Delete a user',
        description: 'Soft deletes the user and revokes every API token issued to that account.',
        operationId: 'deleteUser',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'user',
                description: 'Identifier of the user.',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'User deleted.'),
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
                description: 'User not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->users->delete($user);

        return response()->json(null, 204);
    }
}
