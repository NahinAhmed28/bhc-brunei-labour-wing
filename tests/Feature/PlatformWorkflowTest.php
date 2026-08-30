<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Applicant;
use App\Models\Company;
use App\Models\Role;
use App\Models\Token;
use App\Models\TokenCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_administrator_can_create_token_and_history(): void
    {
        $user = User::where('email', 'mission.bandarseribegawan@mofa.gov.bd')->first();
        $response = $this->actingAs($user)->post('/tokens', ['company_id' => Company::first()->id, 'agency_id' => Agency::first()->id, 'token_category_id' => TokenCategory::first()->id, 'received_on' => today()->format('Y-m-d'), 'demanded_workers' => 5, 'approved_workers' => 3, 'boesl_status' => 'pending', 'visa_status' => 'pending', 'file_status' => 'active']);
        $response->assertRedirect();
        $token = Token::latest('id')->first();
        $this->assertStringStartsWith('BHC-', $token->token_number);
        $this->assertSame($user->id, $token->current_holder_id);
        $this->assertSame($user->name, $token->received_by);
        $this->assertCount(1, $token->transferHistories);
    }

    public function test_administrator_cannot_change_protected_token_fields(): void
    {
        $user = User::where('email', 'mission.bandarseribegawan@mofa.gov.bd')->first();
        $token = Token::first();
        $original = $token->company_id;
        $other = Company::whereKeyNot($original)->first();
        $this->actingAs($user)->put('/tokens/'.$token->id, ['company_id' => $other->id, 'agency_id' => $token->agency_id, 'token_category_id' => $token->token_category_id, 'received_on' => $token->received_on->format('Y-m-d'), 'demanded_workers' => 999, 'approved_workers' => $token->approved_workers, 'boesl_status' => $token->boesl_status, 'visa_status' => $token->visa_status, 'file_status' => $token->file_status])->assertRedirect();
        $this->assertSame($original, $token->fresh()->company_id);
        $this->assertNotSame(999, $token->fresh()->demanded_workers);
    }

    public function test_company_create_rejects_hyphens(): void
    {
        $user = User::where('email', 'mission.bandarseribegawan@mofa.gov.bd')->first();
        $response = $this->actingAs($user)->post('/companies', ['name' => 'Invalid-Company', 'is_active' => 1]);

        $response->assertSessionHasErrors(['name' => 'Names may not contain hyphens or dots.']);
        $this->assertDatabaseMissing('companies', ['name' => 'Invalid-Company']);
    }

    public function test_agency_create_rejects_unicode_hyphens(): void
    {
        $user = User::where('email', 'mission.bandarseribegawan@mofa.gov.bd')->first();
        $response = $this->actingAs($user)->post('/agencies', ['name' => 'Invalid–Agency', 'is_active' => 1]);

        $response->assertSessionHasErrors(['name' => 'Names may not contain hyphens or dots.']);
        $this->assertDatabaseMissing('agencies', ['name' => 'Invalid–Agency']);
    }

    public function test_company_edit_rejects_unicode_minus_as_a_hyphen(): void
    {
        $user = User::where('email', 'mission.bandarseribegawan@mofa.gov.bd')->first();
        $company = Company::firstOrFail();
        $originalName = $company->name;
        $response = $this->actingAs($user)->put(route('companies.update', $company), ['name' => 'Invalid−Company', 'is_active' => 1]);

        $response->assertSessionHasErrors(['name' => 'Names may not contain hyphens or dots.']);
        $this->assertSame($originalName, $company->fresh()->name);
    }

    public function test_agency_edit_rejects_hyphens(): void
    {
        $user = User::where('email', 'mission.bandarseribegawan@mofa.gov.bd')->first();
        $agency = Agency::firstOrFail();
        $originalName = $agency->name;
        $response = $this->actingAs($user)->put(route('agencies.update', $agency), ['name' => 'Invalid-Agency', 'is_active' => 1]);

        $response->assertSessionHasErrors(['name' => 'Names may not contain hyphens or dots.']);
        $this->assertSame($originalName, $agency->fresh()->name);
    }

    public function test_applicant_limit_is_enforced(): void
    {
        $role = Role::where('name', 'administrator')->first();
        $user = User::factory()->create(['role_id' => $role->id]);
        $token = Token::first();
        $token->update(['approved_workers' => 1]);
        Applicant::where('token_id', $token->id)->delete();
        Applicant::create(['token_id' => $token->id, 'full_name' => 'First Worker', 'passport_number' => 'A1000001', 'nationality' => 'Bangladeshi', 'tracking_status' => 'pending', 'visa_status' => 'pending', 'flight_status' => 'pending', 'insurance_status' => 'pending', 'ic_status' => 'pending', 'medical_status' => 'pending', 'boesl_status' => 'pending', 'created_by' => $user->id]);
        $this->actingAs($user)->post('/applicants', ['token_id' => $token->id, 'full_name' => 'Second Worker', 'passport_number' => 'A1000002', 'nationality' => 'Bangladeshi', 'tracking_status' => 'pending', 'visa_status' => 'pending', 'flight_status' => 'pending', 'insurance_status' => 'pending', 'ic_status' => 'pending', 'medical_status' => 'pending', 'boesl_status' => 'pending'])->assertSessionHasErrors('token_id');
    }
}
