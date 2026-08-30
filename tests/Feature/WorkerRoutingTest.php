<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Company;
use App\Models\Role;
use App\Models\Token;
use App\Models\TokenCategory;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_open_the_worker_create_page(): void
    {
        $role = Role::create(['name' => 'administrator', 'label' => 'Administrator']);
        $administrator = User::factory()->create(['role_id' => $role->id]);
        $token = $this->createToken($administrator);

        $response = $this->actingAs($administrator)->get(route('workers.create', ['token_id' => $token->id]));

        $response->assertOk();
        $response->assertSeeText('Add worker against token');
        $response->assertSee('action="'.route('workers.store').'"', false);
        $response->assertSee('type="search"', false);
        $response->assertSee('list="token-lookup-options"', false);
        $response->assertSee('value="'.$token->token_number.'"', false);
        $response->assertSee('name="token_id" value="'.$token->id.'"', false);
    }

    public function test_super_administrator_can_type_a_token_number_when_editing_a_worker(): void
    {
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Administrator']);
        $superAdministrator = User::factory()->create(['role_id' => $role->id]);
        $token = $this->createToken($superAdministrator);
        $worker = Worker::create([
            'token_id' => $token->id,
            'full_name' => 'Nur Rahman',
            'passport_number' => 'BA0123456',
            'created_by' => $superAdministrator->id,
            'updated_by' => $superAdministrator->id,
        ]);

        $response = $this->actingAs($superAdministrator)->get(route('workers.edit', $worker));

        $response->assertOk();
        $response->assertSee('data-token-lookup', false);
        $response->assertSee('value="'.$token->token_number.'"', false);
        $response->assertDontSee('data-token-lookup readonly', false);
    }

    private function createToken(User $user): Token
    {
        $company = Company::create(['name' => 'Brunei Harbour Services']);
        $agency = Agency::create(['name' => 'Dhaka Workforce Agency']);
        $category = TokenCategory::create(['name' => 'Labour', 'code' => 'LAB', 'display_order' => 1]);

        return Token::create([
            'token_number' => 'BHC260800001',
            'bhc_number' => 'BHC-2026-001',
            'token_category_id' => $category->id,
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 12,
            'approved_workers' => 8,
            'boesl_status' => 'pending',
            'visa_status' => 'pending',
            'file_status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
