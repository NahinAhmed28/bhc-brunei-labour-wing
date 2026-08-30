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

class TokenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_does_not_persist_change_reason_as_a_token_attribute(): void
    {
        [$administrator, $company, $agency, $category] = $this->createTokenDependencies();

        $response = $this->actingAs($administrator)->post(route('tokens.store'), [
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'token_category_id' => $category->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 60,
            'approved_workers' => 40,
            'boesl_status' => 'pending',
            'visa_status' => 'pending',
            'file_status' => 'active',
            'change_reason' => 'Initial submission note',
        ]);

        $token = Token::sole();

        $response->assertRedirect(route('tokens.show', $token));
        $this->assertDatabaseHas('tokens', [
            'id' => $token->id,
            'demanded_workers' => 60,
        ]);
    }

    public function test_update_stores_change_reason_only_in_the_audit_log(): void
    {
        [$administrator, $company, $agency, $category] = $this->createTokenDependencies();
        $token = Token::create([
            'token_number' => 'BHC-2608-00001',
            'token_category_id' => $category->id,
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 60,
            'approved_workers' => 40,
            'boesl_status' => 'pending',
            'visa_status' => 'pending',
            'file_status' => 'active',
            'received_by' => 'Legacy Officer',
            'created_by' => $administrator->id,
            'updated_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)->put(route('tokens.update', $token), [
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'token_category_id' => $category->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 60,
            'approved_workers' => 40,
            'boesl_status' => 'submitted',
            'visa_status' => 'pending',
            'file_status' => 'active',
            'remarks' => 'Legacy record updated.',
            'received_by' => 'Replacement Officer',
            'change_reason' => 'BOESL submission completed',
        ]);

        $response->assertRedirect(route('tokens.show', $token));
        $this->assertDatabaseHas('tokens', [
            'id' => $token->id,
            'boesl_status' => 'submitted',
            'remarks' => 'Legacy record updated.',
            'received_by' => 'Legacy Officer',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'tokens',
            'record_id' => (string) $token->id,
            'reason' => 'BOESL submission completed',
        ]);
    }

    public function test_super_admin_can_change_a_tokens_company_and_agency(): void
    {
        [$administrator, $company, $agency, $category] = $this->createTokenDependencies();
        $newCompany = Company::create(['name' => 'Riverside Industries']);
        $newAgency = Agency::create(['name' => 'Global Staffing Agency']);
        $token = Token::create([
            'token_number' => 'BHC-2608-00001',
            'token_category_id' => $category->id,
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 60,
            'approved_workers' => 40,
            'boesl_status' => 'pending',
            'visa_status' => 'pending',
            'file_status' => 'active',
            'created_by' => $administrator->id,
            'updated_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)->put(route('tokens.update', $token), [
            'company_id' => $newCompany->id,
            'agency_id' => $newAgency->id,
            'token_category_id' => $category->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 60,
            'approved_workers' => 40,
            'boesl_status' => 'pending',
            'visa_status' => 'pending',
            'file_status' => 'active',
            'change_reason' => 'Correcting company and agency',
        ]);

        $response->assertRedirect(route('tokens.show', $token));
        $this->assertDatabaseHas('tokens', [
            'id' => $token->id,
            'company_id' => $newCompany->id,
            'agency_id' => $newAgency->id,
        ]);
    }

    public function test_administrator_cannot_change_a_tokens_company_or_agency(): void
    {
        [$administrator, $company, $agency, $category] = $this->createTokenDependencies('administrator');
        $newCompany = Company::create(['name' => 'Riverside Industries']);
        $newAgency = Agency::create(['name' => 'Global Staffing Agency']);
        $token = Token::create([
            'token_number' => 'BHC-2608-00001',
            'token_category_id' => $category->id,
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 60,
            'approved_workers' => 40,
            'boesl_status' => 'pending',
            'visa_status' => 'pending',
            'file_status' => 'active',
            'created_by' => $administrator->id,
            'updated_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)->put(route('tokens.update', $token), [
            'company_id' => $newCompany->id,
            'agency_id' => $newAgency->id,
            'token_category_id' => $category->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 60,
            'approved_workers' => 40,
            'boesl_status' => 'pending',
            'visa_status' => 'pending',
            'file_status' => 'active',
        ]);

        $response->assertRedirect(route('tokens.show', $token));
        $this->assertDatabaseHas('tokens', [
            'id' => $token->id,
            'company_id' => $company->id,
            'agency_id' => $agency->id,
        ]);
    }

    public function test_received_by_is_disabled_on_the_edit_form(): void
    {
        [$administrator, $company, $agency, $category] = $this->createTokenDependencies();
        $token = Token::create([
            'token_number' => 'BHC-2608-00001',
            'token_category_id' => $category->id,
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 60,
            'received_by' => 'Legacy Officer',
            'created_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)->get(route('tokens.edit', $token));

        $response->assertSee('value="Legacy Officer" disabled', false);
        $response->assertDontSee('name="received_by"', false);
        $response->assertSeeText('This imported record value cannot be changed.');
    }

    /**
     * @return array{User, Company, Agency, TokenCategory}
     */
    private function createTokenDependencies(string $roleName = 'super-admin'): array
    {
        $role = Role::create(['name' => $roleName, 'label' => ucwords(str_replace('-', ' ', $roleName))]);
        $administrator = User::factory()->create(['role_id' => $role->id]);
        $company = Company::create(['name' => 'Brunei Harbour Services']);
        $agency = Agency::create(['name' => 'Dhaka Workforce Agency']);
        $category = TokenCategory::create(['name' => 'Demand Letter', 'code' => 'DL']);

        return [$administrator, $company, $agency, $category];
    }
}
