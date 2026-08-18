<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all unique roles from users table
        $roles = DB::table('users')->whereNotNull('role')->distinct()->pluck('role');

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Assign roles to users based on their string role
        $users = DB::table('users')->whereNotNull('role')->get();
        foreach ($users as $user) {
            $role = Role::where('name', $user->role)->where('guard_name', 'web')->first();
            if ($role) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $role->id,
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id,
                ]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('cashier')->after('password');
        });

        // Try to restore roles from spatie model_has_roles
        $userRoles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_type', 'App\Models\User')
            ->get();

        foreach ($userRoles as $ur) {
            DB::table('users')->where('id', $ur->model_id)->update(['role' => $ur->name]);
        }
    }
};
