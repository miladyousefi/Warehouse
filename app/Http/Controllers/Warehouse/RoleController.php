<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): Response
    {
        $this->authorize('roles.view');

        $roles = Role::withCount('permissions')
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        return Inertia::render('warehouse/roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('roles.create');

        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        return Inertia::render('warehouse/roles/Create', [
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('roles.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (!empty($validated['permission_ids'])) {
            $permissions = Permission::where('guard_name', 'web')->whereIn('id', $validated['permission_ids'])->pluck('name')->toArray();
            \Illuminate\Support\Facades\Log::info('Creating role ' . $role->name . ' with permissions', ['permissions' => $permissions]);
            $role->syncPermissions($permissions);
        }

        // Explicitly forget cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('warehouse.roles.index')->with('success', __('Role created.'));
    }

    public function edit(Role $role): Response
    {
        $this->authorize('roles.edit');

        $role->load('permissions');
        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        return Inertia::render('warehouse/roles/Edit', [
            'role' => $role,
            'permission_ids' => $role->permissions->pluck('id')->values()->all(),
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('roles.edit');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role->update(['name' => $validated['name']]);

        if (array_key_exists('permission_ids', $validated)) {
            $permissions = empty($validated['permission_ids'])
                ? []
                : Permission::where('guard_name', 'web')->whereIn('id', $validated['permission_ids'])->pluck('name')->toArray();

            \Illuminate\Support\Facades\Log::info('Syncing permissions for role ' . $role->name, ['permissions' => $permissions]);
            $role->syncPermissions($permissions);
        }

        // Explicitly forget cached permissions to ensure immediate update
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('warehouse.roles.index')->with('success', __('Role updated.'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('roles.delete');

        if ($role->name === 'admin') {
            return back()->with('error', __('You cannot delete the admin role.'));
        }

        $role->delete();

        return redirect()->route('warehouse.roles.index')->with('success', __('Role deleted.'));
    }
}
