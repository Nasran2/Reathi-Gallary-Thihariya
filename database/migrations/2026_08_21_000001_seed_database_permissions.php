<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = collect(config('access.groups', []))
            ->flatMap(fn (array $permissions) => array_keys($permissions))
            ->values();

        $permissionNames->each(fn (string $name) => Permission::findOrCreate($name, 'web'));

        foreach (config('access.roles', []) as $roleName => $patterns) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $selected = $permissionNames->filter(function (string $permission) use ($patterns) {
                $allowed = false;
                foreach ($patterns as $pattern) {
                    if (str_starts_with($pattern, '!') && Str::is(substr($pattern, 1), $permission)) {
                        return false;
                    }
                    if ($pattern === '*' || Str::is($pattern, $permission)) {
                        $allowed = true;
                    }
                }
                return $allowed;
            });
            $role->syncPermissions($selected);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Deliberately preserve access-control data on rollback. Removing roles or
        // permissions automatically could lock administrators out of production.
    }
};
