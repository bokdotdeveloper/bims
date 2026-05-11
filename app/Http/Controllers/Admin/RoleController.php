<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RoleController extends Controller
{
    private const PROTECTED_ROLE = 'Super Admin';

    public function index(Request $request)
    {
        $query = Role::query()->where('guard_name', 'web')
            ->withCount('users')
            ->with(['permissions:id,name'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where('name', 'like', "%{$q}%");
        }

        $roles = $query->paginate($request->integer('per_page', 25))->withQueryString();

        $allPermissions = Permission::query()->where('guard_name', 'web')->orderBy('name')->pluck('name')->values();

        return Inertia::render('roles.index', [
            'roles' => $roles,
            'allPermissions' => $allPermissions,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'web')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $permissionsBefore = [];
        $role->syncPermissions($validated['permissions'] ?? []);
        $role->unsetRelation('permissions');
        $permissionsAfter = $role->permissions()->pluck('name')->sort()->values()->all();
        AuditLogger::recordRolePermissionsChanged($role, $permissionsBefore, $permissionsAfter);

        return redirect()->route('roles.index')->with('success', 'Role created.');
    }

    public function update(Request $request, Role $role)
    {
        if ($role->guard_name !== 'web') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        if ($role->name === self::PROTECTED_ROLE && $validated['name'] !== self::PROTECTED_ROLE) {
            return redirect()->route('roles.index')->with('error', 'The Super Admin role cannot be renamed.');
        }

        $permissionsBefore = $role->permissions()->pluck('name')->sort()->values()->all();

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);
        $role->unsetRelation('permissions');
        $permissionsAfter = $role->permissions()->pluck('name')->sort()->values()->all();
        AuditLogger::recordRolePermissionsChanged($role, $permissionsBefore, $permissionsAfter);

        return redirect()->route('roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if ($role->guard_name !== 'web') {
            abort(404);
        }

        if ($role->name === self::PROTECTED_ROLE) {
            return redirect()->route('roles.index')->with('error', 'The Super Admin role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return redirect()->route('roles.index')->with('error', 'Remove all users from this role before deleting it.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted.');
    }
}
