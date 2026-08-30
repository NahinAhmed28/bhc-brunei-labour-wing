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

    public function test_administrator_can_open_the_token_create_page(): void
    {
        $role = Role::create(['name' => 'administrator', 'label' => 'Administrator']);
        $administrator = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($administrator)->get(route('tokens.create'));

        $response->assertOk();
        $response->assertSeeText('Create Token');
        $response->assertSee('action="'.route('tokens.store').'"', false);
    }

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

        $response->assertRedirect(route('tokens.edit', $token));
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

        $response->assertRedirect(route('tokens.edit', $token));
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

        $response->assertRedirect(route('tokens.edit', $token));
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

    public function test_edit_transfers_the_file_between_users_and_returns_to_the_edit_page(): void
    {
        [$administrator, $company, $agency, $category] = $this->createTokenDependencies();
        $previousHolder = User::factory()->create(['role_id' => $administrator->role_id, 'name' => 'Previous Officer', 'is_active' => true]);
        $newHolder = User::factory()->create(['role_id' => $administrator->role_id, 'name' => 'New Officer', 'is_active' => true]);
        $token = Token::create([
            'token_number' => 'BHC-2608-00001',
            'token_category_id' => $category->id,
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'current_holder_id' => $previousHolder->id,
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
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'token_category_id' => $category->id,
            'current_holder_id' => $newHolder->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 60,
            'approved_workers' => 40,
            'boesl_status' => 'pending',
            'visa_status' => 'pending',
            'file_status' => 'active',
            'change_reason' => 'Transferred for review',
        ]);

        $response->assertRedirect(route('tokens.edit', $token));
        $this->assertDatabaseHas('tokens', ['id' => $token->id, 'current_holder_id' => $newHolder->id]);
        $this->assertDatabaseHas('token_transfer_histories', [
            'token_id' => $token->id,
            'previous_holder_id' => $previousHolder->id,
            'new_holder_id' => $newHolder->id,
            'transferred_by' => $administrator->id,
            'remarks' => 'Transferred for review',
        ]);
    }

    public function test_edit_form_uses_active_users_and_omits_amount_and_receipt_fields(): void
    {
        [$administrator, $company, $agency, $category] = $this->createTokenDependencies();
        $holder = User::factory()->create(['role_id' => $administrator->role_id, 'name' => 'File Review Officer', 'is_active' => true]);
        $token = Token::create([
            'token_number' => 'BHC-2608-00001',
            'token_category_id' => $category->id,
            'company_id' => $company->id,
            'agency_id' => $agency->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 60,
            'created_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)->get(route('tokens.edit', $token));

        $response->assertSee('name="current_holder_id"', false);
        $response->assertSeeText('File Review Officer');
        $response->assertDontSee('name="current_desk_id"', false);
        $response->assertDontSee('name="amount"', false);
        $response->assertDontSee('name="receipt_number"', false);
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
