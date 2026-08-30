<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Company;
use App\Models\Role;
use App\Models\Token;
use App\Models\TokenCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_metric_cards_link_to_their_corresponding_lists(): void
    {
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Administrator']);
        $administrator = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($administrator)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('class="card metric-card metric-card-link"', false);
        $response->assertSee('href="'.route('tokens.index', ['created' => 'today']).'"', false);
        $response->assertSee('href="'.route('tokens.index', ['bhc_status' => 'pending']).'"', false);
        $response->assertSee('href="'.route('tokens.index', ['boesl_status' => 'pending']).'"', false);
        $response->assertSee('href="'.route('workers.index', ['flight_status' => 'pending']).'"', false);
        $response->assertSee('href="'.route('tokens.index').'"', false);
        $response->assertSee('href="'.route('workers.index').'"', false);
        $response->assertSeeText('Workers');
        $response->assertDontSeeText('Applications');
        $response->assertSee('class="sidebar-navigation"', false);
        $response->assertSee('class="nav-sector"', false);
        $response->assertSeeText('Daily workflow');
        $response->assertSeeText('Records and access');
        $response->assertSeeText('Official registry');
        $response->assertSee('class="btn sidebar-toggle"', false);
        $response->assertSee('aria-controls="primary-sidebar"', false);
        $response->assertSee('data-sidebar-dismiss', false);
        $response->assertDontSee('class="btn btn-light d-lg-none" data-sidebar-toggle', false);
    }

    public function test_dashboard_only_lists_users_with_assigned_tokens(): void
    {
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Administrator']);
        $administrator = User::factory()->create(['role_id' => $role->id]);
        $holderWithFiles = User::factory()->create(['name' => 'Assigned Holder', 'role_id' => $role->id, 'is_active' => true]);
        $holderWithoutFiles = User::factory()->create(['name' => 'Idle Holder', 'role_id' => $role->id, 'is_active' => true]);

        $company = Company::create(['name' => 'Test Company']);
        $agency = Agency::create(['name' => 'Test Agency']);
        $category = TokenCategory::create(['name' => 'Demand Letter', 'code' => 'DL']);

        Token::create([
            'token_number' => 'BHC-2608-00001',
            'token_category_id' => $category->id,
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'current_holder_id' => $holderWithFiles->id,
            'received_on' => now()->toDateString(),
            'demanded_workers' => 5,
            'created_by' => $administrator->id,
            'updated_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Assigned Holder');
        $response->assertDontSee('Idle Holder');
    }

    public function test_data_entry_navigation_omits_the_empty_management_sector(): void
    {
        $role = Role::create(['name' => 'data-entry', 'label' => 'Data Entry Operator']);
        $dataEntryUser = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($dataEntryUser)->get(route('dashboard'));

        $response->assertSee('id="operations-sector"', false);
        $response->assertDontSee('id="management-sector"', false);
        $response->assertSeeText('Daily workflow');
    }
}
