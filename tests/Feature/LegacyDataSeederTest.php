<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Company;
use App\Models\Token;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_company_agency_and_token_details_are_seeded(): void
    {
        $this->seed();

        $this->assertSame(753, Company::count());
        $this->assertSame(67, Agency::count());
        $this->assertSame(1304, Token::count());
        $this->assertSame(0, Company::where('name', 'like', '%-%')->count());
        $this->assertSame(0, Agency::where('name', 'like', '%-%')->count());

        $this->assertDatabaseHas('companies', [
            'name' => 'ATK Perpetual Sdn Bhd',
            'email' => 'atkperpetual@gmail.com',
            'phone' => '+6738609911 and +6737111802',
        ]);
        $this->assertDatabaseHas('agencies', [
            'name' => 'Marya Indra Az Zahra Employment Agency',
            'email' => 'Miazea2427@gmail.com',
        ]);

        $token = Token::with(['company', 'agency', 'category'])
            ->where('token_number', 'DL-62497')
            ->firstOrFail();

        $this->assertSame('ATK Perpetual Sdn Bhd', $token->company->name);
        $this->assertSame('Marya Indra Az Zahra Employment Agency', $token->agency->name);
        $this->assertSame('Demand Letter Submission', $token->category->name);
        $this->assertSame(1, $token->demanded_workers);
        $this->assertSame(1, $token->approved_workers);
        $this->assertSame('BHC-619/2025; 27/10/2025', $token->bhc_number);
        $this->assertSame('Khairul Arefin', $token->received_by);
        $this->assertStringContainsString('Legacy required visa attestations: not recorded.', $token->remarks);
    }

    public function test_legacy_seeders_can_be_rerun_without_creating_duplicates(): void
    {
        $this->seed();

        $this->seed();

        $this->assertSame(753, Company::count());
        $this->assertSame(67, Agency::count());
        $this->assertSame(1304, Token::count());
    }
}
