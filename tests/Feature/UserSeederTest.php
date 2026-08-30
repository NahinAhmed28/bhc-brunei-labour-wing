<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_supplied_users_with_their_roles(): void
    {
        Role::create(['name' => 'super-admin', 'label' => 'Super Administrator']);
        Role::create(['name' => 'administrator', 'label' => 'Administrator']);
        Role::create(['name' => 'data-entry', 'label' => 'Data Entry Operator']);
        Role::create(['name' => 'viewer', 'label' => 'Viewer / Auditor']);

        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        $this->assertDatabaseCount('users', 14);
        $this->assertDatabaseHas('users', [
            'name' => 'Admin User',
            'email' => 'mission.bandarseribegawan@mofa.gov.bd',
            'is_active' => true,
            'email_verified_at' => '2025-10-19 04:47:38',
            'remember_token' => null,
        ]);
        $this->assertDatabaseHas('users', [
            'name' => 'Md Abu Bakkar Siddique',
            'email' => 'labourwingbrunei@gmail.com',
            'email_verified_at' => null,
            'created_at' => '2025-11-24 09:34:22',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'Bhcgov9@gmail.com',
            'created_at' => null,
            'updated_at' => null,
        ]);

        $this->assertSame('super-admin', User::where('email', 'superadmin@gmail.com')->firstOrFail()->role->name);
        $this->assertSame('administrator', User::where('email', 'mission.bandarseribegawan@mofa.gov.bd')->firstOrFail()->role->name);
        $this->assertSame('data-entry', User::where('email', 'boeseladmin@gmail.com')->firstOrFail()->role->name);
        $this->assertSame('administrator', User::where('email', 'brhcadmin@gmail.com')->firstOrFail()->role->name);
        $this->assertSame(
            '$2y$12$CB1W2OfdNXjXihazki8a2ebPxlw0vnU8FsDrmeJehIQ.IvoqY3jWe',
            User::where('email', 'superadmin@gmail.com')->firstOrFail()->getRawOriginal('password'),
        );
    }

    public function test_database_seeders_can_use_any_seeded_administrator_user(): void
    {
        $this->seed();

        $administrator = User::query()
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['administrator', 'super-admin']))
            ->firstOrFail();

        $this->assertNotNull($administrator);
        $this->assertDatabaseHas('companies', ['created_by' => $administrator->id]);
        $this->assertDatabaseHas('agencies', ['created_by' => $administrator->id]);
        $this->assertGreaterThan(0, $administrator->id);
    }
}
