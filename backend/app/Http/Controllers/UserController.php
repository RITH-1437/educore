<?php

namespace App\Http\Controllers;

use App\Dto\User\CreateUserData;
use App\Dto\User\UpdateUserData;
use App\Dto\User\UserListFilters;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = UserListFilters::fromInput($request->query());

        $users = $this->users->list($filters)
            ->withQueryString()
            ->appends($filters->toQueryString());

        return Inertia::render('Users/Index', [
            'users' => UserResource::collection($users),
            'filters' => [
                'search' => $filters->search,
                'role' => $filters->role,
                'role_id' => $filters->roleId,
            ],
            'roles' => $this->rolesForSelect(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create', [
            'roles' => $this->rolesForSelect(),
            'departments' => $this->departmentsForSelect(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $this->users->create(CreateUserData::fromValidated($request->validated()));

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('Users/Edit', [
            // `resolve()` instead of the resource instance: Inertia treats a bare
            // `JsonResource` as a `Responsable`, so it would nest the payload under
            // `data` and the page would read `props.user.data.name`.
            'user' => (new UserResource($user->load('role', 'department:id,name')))->resolve(),
            'roles' => $this->rolesForSelect(),
            'departments' => $this->departmentsForSelect(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->users->update($user, UpdateUserData::fromValidated($request->validated()));

        return redirect()->back()->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->users->delete($user);

        return redirect()->route('users.index')->with('success', 'User deleted.');
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    private function rolesForSelect(): array
    {
        return Role::query()->orderBy('id')->get()
            ->map(fn (Role $role) => ['id' => $role->id, 'name' => $role->name, 'slug' => $role->slug])
            ->all();
    }

    /**
     * Departments a Department Admin can be assigned to.
     *
     * @return list<array{id: int, name: string}>
     */
    private function departmentsForSelect(): array
    {
        return Department::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (Department $department) => ['id' => $department->id, 'name' => $department->name])->all();
    }
}
