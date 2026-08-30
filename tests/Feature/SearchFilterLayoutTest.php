<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Company;
use App\Models\Role;
use App\Models\Token;
use App\Models\TokenCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SearchFilterLayoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $fieldNames
     */
    #[DataProvider('registerFilterPages')]
    public function test_register_page_renders_aligned_filter_controls_for_every_supported_field(string $path, string $gridClass, array $fieldNames): void
    {
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Administrator']);
        $administrator = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($administrator)->get($path);

        $response->assertSee('class="'.$gridClass.'"', false);
        foreach ($fieldNames as $fieldName) {
            $response->assertSee('name="'.$fieldName.'"', false);
        }
    }

    public static function registerFilterPages(): array
    {
        return [
            'tokens' => ['/tokens', 'filter-grid', ['q', 'company_name', 'agency_name', 'category_id', 'holder_id', 'boesl_status', 'bhc_status', 'created', 'pre_selected']],
            'applicants' => ['/applicants', 'filter-grid', ['q', 'visa_status', 'flight_status', 'insurance_status', 'ic_status']],
            'companies' => ['/companies', 'filter-grid filter-grid-compact', ['q']],
            'agencies' => ['/agencies', 'filter-grid filter-grid-compact', ['q']],
            'audit' => ['/audit', 'filter-grid filter-grid-compact', ['q']],
        ];
    }

    public function test_token_company_and_agency_filters_accept_partial_names(): void
    {
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Administrator']);
        $administrator = User::factory()->create(['role_id' => $role->id]);
        $matchingCompany = Company::create(['name' => 'Brunei Harbour Services']);
        $matchingAgency = Agency::create(['name' => 'Dhaka Workforce Agency']);
        $otherCompany = Company::create(['name' => 'Riverside Industries']);
        $otherAgency = Agency::create(['name' => 'Global Staffing Agency']);
        $category = TokenCategory::create(['name' => 'Demand Letter', 'code' => 'DL']);
        Token::create([
            'token_number' => 'BHC-2608-00001',
            'token_category_id' => $category->id,
            'company_id' => $matchingCompany->id,
            'agency_id' => $matchingAgency->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 10,
            'created_by' => $administrator->id,
        ]);
        Token::create([
            'token_number' => 'BHC-2608-00002',
            'token_category_id' => $category->id,
            'company_id' => $otherCompany->id,
            'agency_id' => $otherAgency->id,
            'received_on' => '2026-08-30',
            'demanded_workers' => 10,
            'created_by' => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)->get(route('tokens.index', [
            'company_name' => 'Harbour',
            'agency_name' => 'Dhaka',
        ]));

        $response->assertSeeText('BHC-2608-00001');
        $response->assertDontSeeText('BHC-2608-00002');
    }

    public function test_token_company_and_agency_filters_render_as_search_inputs(): void
    {
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Administrator']);
        $administrator = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($administrator)->get(route('tokens.index'));

        $response->assertSee('name="company_name" type="search"', false);
        $response->assertSee('list="company-filter-options"', false);
        $response->assertSee('name="agency_name" type="search"', false);
        $response->assertSee('list="agency-filter-options"', false);
    }

    public function test_company_search_finds_legacy_contact_fields(): void
    {
        $this->seed();
        $administrator = User::where('email', 'mission.bandarseribegawan@mofa.gov.bd')->firstOrFail();

        $response = $this->actingAs($administrator)->get('/companies?q=atkperpetual%40gmail.com');

        $response->assertSee('ATK Perpetual Sdn Bhd');
    }

    public function test_agency_search_finds_legacy_contact_fields(): void
    {
        $this->seed();
        $administrator = User::where('email', 'mission.bandarseribegawan@mofa.gov.bd')->firstOrFail();

        $response = $this->actingAs($administrator)->get('/agencies?q=%2B6738212701');

        $response->assertSee('Marya Indra AzZahra Employment Agency');
    }
}
