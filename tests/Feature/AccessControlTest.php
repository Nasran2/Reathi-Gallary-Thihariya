<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function userWith(array $permissions): User
    {
        $role = Role::create(['name' => 'restricted-'.str()->random(8), 'guard_name' => 'web']);
        $permissionModels = collect($permissions)->map(fn ($name) => Permission::findOrCreate($name, 'web'));
        $role->syncPermissions($permissionModels);
        $user = User::factory()->create(['active' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_permission_catalogue_and_default_roles_are_seeded(): void
    {
        $this->assertSame(109, Permission::count());
        foreach (['super_admin', 'admin', 'manager', 'cashier', 'storekeeper'] as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role, 'guard_name' => 'web']);
        }
        $this->assertSame(Permission::count(), Role::findByName('super_admin')->permissions()->count());
        $this->assertFalse(Role::findByName('cashier')->hasPermissionTo('products.view_cost'));
        $this->assertTrue(Role::findByName('storekeeper')->hasPermissionTo('inventory.adjust'));
    }

    public function test_sidebar_hides_unavailable_modules_and_direct_routes_return_forbidden(): void
    {
        $user = $this->userWith(['products.view']);

        $this->actingAs($user)->get(route('products.index'))
            ->assertOk()
            ->assertSee('Products List')
            ->assertDontSee('Add Purchase')
            ->assertDontSee('User Management')
            ->assertDontSee('Remnant POS')
            ->assertDontSee('New Sale');

        $this->actingAs($user)->get(route('purchases.index'))->assertForbidden();
        $this->actingAs($user)->get(route('products.create'))->assertForbidden();
        $this->actingAs($user)->get(route('settings.show', ['section' => 'security']))->assertForbidden();
    }

    public function test_settings_permission_is_enforced_for_the_specific_section(): void
    {
        $user = $this->userWith(['settings.general']);

        $this->actingAs($user)->get(route('settings.show', ['section' => 'general']))->assertOk();
        $this->actingAs($user)->get(route('settings.show', ['section' => 'security']))->assertForbidden();
    }

    public function test_super_admin_bypass_and_inactive_login_protection(): void
    {
        $admin = User::factory()->create(['active' => true]);
        $admin->assignRole(Role::findByName('super_admin'));
        $this->actingAs($admin)->get(route('roles.index'))->assertOk();
        $this->post(route('logout'))->assertRedirect(route('login'));

        $inactive = User::factory()->create([
            'username' => 'inactive-user',
            'password' => bcrypt('correct-password'),
            'active' => false,
        ]);
        $this->post(route('login.attempt'), ['login' => $inactive->username, 'password' => 'correct-password'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }
}
