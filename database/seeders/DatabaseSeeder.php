<?php

namespace Database\Seeders;

use App\Models\Desk;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissionNames = ['manage-users', 'manage-masters', 'create-tokens', 'edit-tokens', 'edit-protected-tokens', 'manage-applicants', 'manage-documents', 'view-reports', 'view-audit'];
        foreach ($permissionNames as $name) {
            Permission::firstOrCreate(['name' => $name], ['label' => ucwords(str_replace('-', ' ', $name))]);
        }
        $roles = [];
        foreach (['super-admin' => 'Super Administrator', 'administrator' => 'Administrator', 'data-entry' => 'Data Entry Operator', 'viewer' => 'Viewer / Auditor'] as $name => $label) {
            $roles[$name] = Role::firstOrCreate(['name' => $name], ['label' => $label]);
        }
        $roles['super-admin']->permissions()->sync(Permission::pluck('id'));
        $roles['administrator']->permissions()->sync(Permission::whereIn('name', ['manage-masters', 'create-tokens', 'edit-tokens', 'manage-applicants', 'manage-documents', 'view-reports'])->pluck('id'));
        $roles['data-entry']->permissions()->sync(Permission::whereIn('name', ['manage-applicants', 'manage-documents'])->pluck('id'));
        $roles['viewer']->permissions()->sync(Permission::whereIn('name', ['view-reports', 'view-audit'])->pluck('id'));
        foreach ([['Reception Desk', 'REC'], ['Visa Wing', 'VISA'], ['Labour Wing', 'LAB'], ['Consular Approval', 'CONS']] as [$name, $code]) {
            Desk::firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }

        $this->call([
            UserSeeder::class,
            CompanySeeder::class,
            AgencySeeder::class,
            TokenSeeder::class,
        ]);
    }
}
