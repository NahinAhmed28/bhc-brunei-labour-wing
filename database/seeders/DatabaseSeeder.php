<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Applicant;
use App\Models\Company;
use App\Models\Desk;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Token;
use App\Models\TokenCategory;
use App\Models\TokenDeskHistory;
use App\Models\User;
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
        $admin = User::updateOrCreate(['email' => 'admin@bhcbrunei.gov.bd'], ['name' => 'System Administrator', 'password' => 'ChangeMe123!', 'role_id' => $roles['super-admin']->id, 'is_active' => true, 'email_verified_at' => now()]);
        User::updateOrCreate(['email' => 'operations@bhcbrunei.gov.bd'], ['name' => 'Operations Officer', 'password' => 'ChangeMe123!', 'role_id' => $roles['administrator']->id, 'is_active' => true, 'email_verified_at' => now()]);
        $companies = collect(['Borneo Infrastructure Services', 'Darussalam Marine Works', 'Seri Construction Group'])->map(fn ($name) => Company::firstOrCreate(['name' => $name], ['registration_no' => 'REG-'.fake()->unique()->numerify('####'), 'email' => str($name)->slug().'@example.com', 'phone' => '+673 '.fake()->numerify('#######'), 'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id]));
        $agencies = collect(['Dhaka Workforce Services', 'Meghna Recruiting International', 'Padma Overseas Employment'])->map(fn ($name) => Agency::firstOrCreate(['name' => $name], ['license_no' => 'RL-'.fake()->unique()->numerify('####'), 'email' => str($name)->slug().'@example.com', 'phone' => '+880 1'.fake()->numerify('#########'), 'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id]));
        $categories = collect([['Visa Attestation', 'VA'], ['Demand Letter Submission', 'DLS'], ['Pre Selected Applicant', 'PSA']])->map(fn ($x) => TokenCategory::firstOrCreate(['code' => $x[1]], ['name' => $x[0], 'is_active' => true]));
        $desks = collect([['Reception Desk', 'REC'], ['Visa Wing', 'VISA'], ['Labour Wing', 'LAB'], ['Consular Approval', 'CONS']])->map(fn ($x) => Desk::firstOrCreate(['code' => $x[1]], ['name' => $x[0], 'is_active' => true]));
        if (Token::count() === 0) {
            foreach (range(1, 8) as $i) {
                $token = Token::create(['token_number' => 'BHC-'.now()->format('ym').'-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT), 'token_category_id' => $categories[($i - 1) % $categories->count()]->id, 'company_id' => $companies[($i - 1) % $companies->count()]->id, 'agency_id' => $agencies[($i - 1) % $agencies->count()]->id, 'current_desk_id' => $desks[($i - 1) % $desks->count()]->id, 'agent_name' => 'Representative '.$i, 'received_on' => today()->subDays($i), 'demanded_workers' => 10 + $i, 'approved_workers' => 8 + $i, 'pre_selected' => $i % 3 === 0, 'bhc_number' => $i < 7 ? 'BHC/'.now()->format('Y').'/'.str_pad((string) $i, 4, '0', STR_PAD_LEFT) : null, 'boesl_status' => $i % 2 ? 'pending' : 'submitted', 'visa_status' => 'processing', 'file_status' => 'active', 'created_by' => $admin->id, 'updated_by' => $admin->id]);
                TokenDeskHistory::create(['token_id' => $token->id, 'new_desk_id' => $token->current_desk_id, 'changed_by' => $admin->id, 'arrived_at' => now()->subDays($i), 'remarks' => 'Seeded initial assignment']);
                if ($i <= 5) {
                    Applicant::create(['token_id' => $token->id, 'full_name' => fake()->name('male'), 'passport_number' => 'A'.fake()->unique()->numerify('#######'), 'nationality' => 'Bangladeshi', 'phone' => '+880 1'.fake()->numerify('#########'), 'job_category' => ['Welder', 'Electrician', 'Technician'][$i % 3], 'registration_number' => $i < 4 ? 'REG-'.now()->format('Y').'-'.$i : null, 'registration_date' => $i < 4 ? today()->subDays($i) : null, 'tracking_status' => 'in-progress', 'visa_status' => $i < 3 ? 'received' : 'processing', 'flight_status' => 'pending', 'insurance_status' => $i < 3 ? 'received' : 'pending', 'ic_status' => 'pending', 'medical_status' => 'cleared', 'boesl_status' => 'submitted', 'created_by' => $admin->id, 'updated_by' => $admin->id]);
                }
            }
        }
    }
}
