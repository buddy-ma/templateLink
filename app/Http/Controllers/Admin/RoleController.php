<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Support\Acl;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->values()->all(),
                'users_count' => $role->users_count,
                'protected' => Acl::isProtectedRole($role->name),
            ]);

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'guard_name' => $p->guard_name,
                'core' => in_array($p->name, Acl::CORE_PERMISSIONS, true),
            ]);

        return Inertia::render('admin/roles/Index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $names = $validated['permission_names'] ?? [];

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($names);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Role created.');
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        if ($role->guard_name !== 'web') {
            abort(404);
        }

        if (Acl::isProtectedRole($role->name) && $request->filled('name') && $request->string('name')->toString() !== $role->name) {
            return back()->withErrors(['name' => 'This role cannot be renamed.']);
        }

        $validated = $request->validated();

        if (isset($validated['name'])) {
            $role->name = $validated['name'];
            $role->save();
        }

        /** @var list<string> $names */
        $names = array_values(array_unique($validated['permission_names'] ?? []));

        if (Acl::isProtectedRole($role->name)) {
            $names = array_values(array_unique(array_merge(
                $names,
                ['access_admin', 'manage_roles'],
            )));
        }

        $role->syncPermissions($names);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role->unsetRelation('permissions');

        return back()->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->guard_name !== 'web') {
            abort(404);
        }

        if (Acl::isProtectedRole($role->name)) {
            return back()->withErrors(['role' => 'This role cannot be deleted.']);
        }

        if ($role->users()->count() > 0) {
            return back()->withErrors(['role' => 'Remove all users from this role before deleting it.']);
        }

        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Role deleted.');
    }
}
