<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $perm = Permission::firstOrCreate(['name' => 'reports.trending', 'guard_name' => 'web']);

        foreach (['super_admin', 'admin', 'manager'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && !$role->hasPermissionTo('reports.trending')) {
                $role->givePermissionTo($perm);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::where('name', 'reports.trending')->delete();
    }
};
