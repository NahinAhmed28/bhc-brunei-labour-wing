<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_supplied_users_with_their_roles(): void
    {
        Role::create(['name' => 'super-admin', 'label' => 'Super Administrator']);
        Role::create(['name' => 'administrator', 'label' => 'Administrator']);
        Role::create(['name' => 'viewer', 'label' => 'Viewer / Auditor']);

        $this->seed(UserSeeder::class);

        $this->assertDatabaseHas('users', ['name' => 'System Administrator', 'email' => 'admin@bhcbrunei.gov.bd', 'is_active' => true]);
        $this->assertDatabaseHas('users', ['name' => 'Operations Officer', 'email' => 'operations@bhcbrunei.gov.bd', 'is_active' => true]);
        $this->assertDatabaseHas('users', ['name' => 'test admin', 'email' => 'admin@admin.com', 'is_active' => true]);
        $this->assertSame('viewer', User::where('email', 'admin@admin.com')->firstOrFail()->role->name);
        $this->assertTrue(Hash::check('ChangeMe123!', User::where('email', 'admin@admin.com')->value('password')));
    }
}
