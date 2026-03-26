<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use App\Support\Acl;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function store(StorePermissionRequest $request): RedirectResponse
    {
        Permission::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Permission created.');
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        if ($permission->guard_name !== 'web') {
            abort(404);
        }

        if (in_array($permission->name, Acl::CORE_PERMISSIONS, true)) {
            return back()->withErrors(['name' => 'Core permissions cannot be renamed.']);
        }

        $permission->name = $request->validated('name');
        $permission->save();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Permission updated.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        if ($permission->guard_name !== 'web') {
            abort(404);
        }

        if (in_array($permission->name, Acl::CORE_PERMISSIONS, true)) {
            return back()->withErrors(['permission' => 'Core permissions cannot be deleted.']);
        }

        $permission->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Permission deleted.');
    }
}
