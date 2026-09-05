<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Role;
use App\Models\Token;
use App\Models\TokenCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $response->assertSee('href="'.route('tokens.index', ['boesl_status' => 'pending']).'"', false);
        $response->assertSee('href="'.route('workers.index', ['flight_status' => 'pending']).'"', false);
        $response->assertSee('href="'.route('tokens.index').'"', false);
        $response->assertSee('href="'.route('workers.index').'"', false);
        $response->assertSee('href="'.route('token-categories.index').'"', false);
        $response->assertSeeText('Workers');
        $response->assertSeeText('Total registered workers');
        $response->assertDontSeeText('Workers entered');
        $response->assertDontSeeText('Pending BHC No.');
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

    public function test_super_administrator_dashboard_displays_recent_activity(): void
    {
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Administrator']);
        $superAdministrator = User::factory()->create(['role_id' => $role->id]);
        AuditLog::create([
            'user_id' => $superAdministrator->id,
            'action' => 'reviewed',
            'module' => 'confidential-records',
        ]);

        $response = $this->actingAs($superAdministrator)->get(route('dashboard'));

        $response->assertSeeText('Recent activity');
        $response->assertSeeText('Reviewed · confidential records');
    }

    #[DataProvider('nonSuperAdministratorRoles')]
    public function test_non_super_administrator_dashboard_hides_recent_activity(string $roleName): void
    {
        $role = Role::create(['name' => $roleName, 'label' => ucwords(str_replace('-', ' ', $roleName))]);
        $user = User::factory()->create(['role_id' => $role->id]);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'reviewed',
            'module' => 'confidential-records',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertDontSeeText('Recent activity');
        $response->assertDontSeeText('Confidential records');
        $response->assertSee('class="col-12"', false);
    }

    public static function nonSuperAdministratorRoles(): array
    {
        return [
            'administrator' => ['administrator'],
            'data entry' => ['data-entry'],
            'viewer' => ['viewer'],
        ];
    }

    public function test_dashboard_lists_active_users_with_assigned_or_generated_token_counts(): void
    {
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Administrator']);
        $administrator = User::factory()->create(['role_id' => $role->id]);
        $holderWithFiles = User::factory()->create(['name' => 'Assigned Holder', 'role_id' => $role->id, 'is_active' => true]);
        $holderWithoutFiles = User::factory()->create(['name' => 'Idle Holder', 'role_id' => $role->id, 'is_active' => true]);
        $creatorWithoutAssignedFiles = User::factory()->create(['name' => 'Token Generator', 'role_id' => $role->id, 'is_active' => true]);
        $inactiveCreator = User::factory()->create(['name' => 'Inactive Token Generator', 'role_id' => $role->id, 'is_active' => false]);

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
        Token::create([
            'token_number' => 'DL-01234567',
            'token_category_id' => $category->id,
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'received_on' => now()->toDateString(),
            'demanded_workers' => 3,
            'created_by' => $creatorWithoutAssignedFiles->id,
            'updated_by' => $creatorWithoutAssignedFiles->id,
        ]);
        Token::create([
            'token_number' => 'DL-76543210',
            'token_category_id' => $category->id,
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'received_on' => now()->toDateString(),
            'demanded_workers' => 2,
            'created_by' => $inactiveCreator->id,
            'updated_by' => $inactiveCreator->id,
        ]);

        $response = $this->actingAs($administrator)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Assigned Holder');
        $response->assertSee('Token Generator');
        $response->assertDontSee('Idle Holder');
        $response->assertDontSee('Inactive Token Generator');
        $response->assertSeeText('Assigned files');
        $response->assertSeeText('Tokens generated');
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
