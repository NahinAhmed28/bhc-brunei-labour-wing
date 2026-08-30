<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use LogicException;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleIds = Role::whereIn('name', ['super-admin', 'administrator', 'viewer'])->pluck('id', 'name');

        if ($roleIds->count() !== 3) {
            throw new LogicException('Seed roles before seeding users.');
        }

        foreach ($this->records() as $record) {
            User::updateOrCreate(
                ['email' => $record['email']],
                [
                    'name' => $record['name'],
                    'password' => 'ChangeMe123!',
                    'role_id' => $roleIds[$record['role']],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
        }
    }

    /**
     * @return array<int, array{name: string, email: string, role: string}>
     */
    private function records(): array
    {
        return [
            ['name' => 'System Administrator', 'email' => 'admin@bhcbrunei.gov.bd', 'role' => 'super-admin'],
            ['name' => 'Operations Officer', 'email' => 'operations@bhcbrunei.gov.bd', 'role' => 'administrator'],
            ['name' => 'test admin', 'email' => 'admin@admin.com', 'role' => 'viewer'],
        ];
    }
}
