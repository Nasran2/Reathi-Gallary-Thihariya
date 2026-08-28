<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear cached permissions
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        
        // Add the new permission
        Permission::firstOrCreate(['name' => 'reports.ledger', 'guard_name' => 'web']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::where('name', 'reports.ledger')->delete();
    }
};
