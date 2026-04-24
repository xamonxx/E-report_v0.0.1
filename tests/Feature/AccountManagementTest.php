<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_delete_account_and_detach_admin_users(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super-admin@example.test',
            'password' => bcrypt('password'),
            'role' => UserRole::SuperAdmin,
        ]);

        $account = Account::create([
            'name' => 'Account Alpha',
            'description' => 'Interior',
        ]);

        $admin = User::create([
            'name' => 'Admin Alpha',
            'email' => 'admin-alpha@example.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Admin,
            'account_id' => $account->id,
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('accounts.destroy', $account))
            ->assertRedirect(route('accounts.index'));

        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'account_id' => null,
        ]);
    }

    public function test_account_admins_relation_uses_user_account_id(): void
    {
        $account = Account::create([
            'name' => 'Account Beta',
            'description' => 'Interior',
        ]);

        $admin = User::create([
            'name' => 'Admin Beta',
            'email' => 'admin-beta@example.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Admin,
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->admins->contains($admin));
    }
}
