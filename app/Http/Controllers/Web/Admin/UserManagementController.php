<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreAdminUserRequest;
use App\Http\Requests\Web\UpdateAdminUserRequest;
use App\Models\User;
use App\Services\AdminWebService;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserManagementController extends Controller
{
    public function __construct(private readonly UserManagementService $users, private readonly AdminWebService $web) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $filters = $request->only(['search', 'role', 'status', 'sort', 'direction', 'per_page']);

        return view('admin.users.index', ['users' => $this->users->paginate($filters), 'roles' => $this->web->roles(), 'filters' => $filters]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.form', ['user' => null, 'roles' => $this->web->roles()]);
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);
        $user = $this->users->create($request->validated());

        return redirect()->route('admin.users.show', $user)->with('success', 'User created successfully.');
    }

    public function show(User $user): View
    {
        Gate::authorize('view', $user);

        return view('admin.users.show', ['user' => $user->load('roles', 'staffProfile.skills')]);
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.form', ['user' => $user->load('roles'), 'roles' => $this->web->roles()]);
    }

    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);
        $this->users->update($user, $request->validated());

        return redirect()->route('admin.users.show', $user)->with('success', 'User updated successfully.');
    }

    public function activate(User $user): RedirectResponse
    {
        Gate::authorize('update', $user);
        $this->users->setActive($user, true);

        return back()->with('success', 'User activated successfully.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages(['user' => 'You cannot deactivate your own account.']);
        }
        $this->users->setActive($user, false);

        return back()->with('success', 'User deactivated successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', User::class);
        $records = $this->users->records($request->only(['search', 'role', 'status', 'sort', 'direction']));

        return $this->web->csv('users', ['Name', 'Email', 'Employee No.', 'Student No.', 'Roles', 'Active', 'Created At'], $records, fn (User $user): array => [
            $user->name, $user->email, $user->employee_no, $user->student_no, $user->roles->pluck('name')->implode('; '), $user->is_active ? 'Yes' : 'No', $user->created_at?->toIso8601String(),
        ]);
    }
}
