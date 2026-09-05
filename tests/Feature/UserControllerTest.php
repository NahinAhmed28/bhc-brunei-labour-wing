<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_administrator_can_deactivate_and_activate_a_user(): void
    {
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Administrator']);
        $superAdministrator = User::factory()->create(['role_id' => $role->id]);
        $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

        $this->actingAs($superAdministrator)
            ->patch(route('users.status.update', $user), ['is_active' => false])
            ->assertRedirect()
            ->assertSessionHas('success', 'User account deactivated.');

        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'record_id' => (string) $user->id,
            'module' => 'users',
            'action' => 'deactivate',
        ]);

        $this->actingAs($superAdministrator)
            ->patch(route('users.status.update', $user), ['is_active' => true])
            ->assertRedirect()
            ->assertSessionHas('success', 'User account activated.');

        $this->assertTrue($user->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'record_id' => (string) $user->id,
            'module' => 'users',
            'action' => 'activate',
        ]);
    }

    public function test_non_super_administrator_cannot_change_user_status(): void
    {
        $administratorRole = Role::create(['name' => 'administrator', 'label' => 'Administrator']);
        $administrator = User::factory()->create(['role_id' => $administratorRole->id]);
        $user = User::factory()->create(['role_id' => $administratorRole->id, 'is_active' => true]);

        $this->actingAs($administrator)
            ->patch(route('users.status.update', $user), ['is_active' => false])
            ->assertForbidden();

        $this->assertTrue($user->fresh()->is_active);
    }
}
