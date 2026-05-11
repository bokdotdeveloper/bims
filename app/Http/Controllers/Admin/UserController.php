<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with(['roles:id,name'])->orderBy('name');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $users = $query->paginate($request->integer('per_page', 25))->withQueryString();

        $availableRoles = Role::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('users.index', [
            'users' => $users,
            'availableRoles' => $availableRoles,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['uuid', Rule::exists('roles', 'id')->where('guard_name', 'web')],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $roleIds = $validated['roles'] ?? [];
        $rolesBefore = [];
        if ($roleIds !== []) {
            $user->syncRoles(Role::query()->whereIn('id', $roleIds)->where('guard_name', 'web')->pluck('name')->all());
        }
        $user->unsetRelation('roles');
        $rolesAfter = $user->roles()->pluck('name')->sort()->values()->all();
        AuditLogger::recordUserRolesChanged($user, $rolesBefore, $rolesAfter);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['uuid', Rule::exists('roles', 'id')->where('guard_name', 'web')],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $rolesBefore = $user->roles()->pluck('name')->sort()->values()->all();

        $user->save();

        $roleIds = $validated['roles'] ?? [];
        $user->syncRoles(Role::query()->whereIn('id', $roleIds)->where('guard_name', 'web')->pluck('name')->all());
        $user->unsetRelation('roles');
        $rolesAfter = $user->roles()->pluck('name')->sort()->values()->all();
        AuditLogger::recordUserRolesChanged($user, $rolesBefore, $rolesAfter);

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted.');
    }
}
