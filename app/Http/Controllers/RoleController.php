<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $this->authorize('roles.assign_permissions');
        return view('roles.form', ['permissionGroups' => config('access.groups', [])]);
    }

    public function store(Request $request)
    {
        $this->authorize('roles.assign_permissions');
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        
        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $this->authorize('roles.assign_permissions');
        return view('roles.form', ['role' => $role, 'permissionGroups' => config('access.groups', [])]);
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('roles.assign_permissions');
        if ($role->name === 'super_admin') {
            $role->syncPermissions(Permission::all());
            return redirect()->route('roles.index')->with('success', 'Super Admin retains all permissions.');
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->update(['name' => $validated['name']]);
        
        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        } else {
            $role->syncPermissions([]);
        }

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'super_admin') {
            return back()->withErrors(['error' => 'Cannot delete super_admin role.']);
        }
        
        if ($role->users()->exists()) {
            return back()->withErrors(['error' => 'Reassign users before deleting this role.']);
        }

        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
