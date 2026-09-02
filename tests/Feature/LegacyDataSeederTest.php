<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Company;
use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_company_agency_and_token_details_are_seeded(): void
    {
        $this->seed();

        $this->assertSame(751, Company::count());
        $this->assertSame(65, Agency::count());
        $this->assertSame(1304, Token::count());
        $this->assertSame(0, Company::where('name', 'like', '%-%')->count());
        $this->assertSame(0, Agency::where('name', 'like', '%-%')->count());

        $this->assertDatabaseHas('companies', [
            'name' => 'ATK Perpetual Sdn Bhd',
            'email' => 'atkperpetual@gmail.com',
            'phone' => '+6738609911 and +6737111802',
        ]);
        $this->assertDatabaseHas('agencies', [
            'name' => 'Marya Indra AzZahra Employment Agency',
            'email' => 'Miazea2427@gmail.com',
        ]);

        $token = Token::with(['company', 'agency', 'category'])
            ->where('token_number', 'DL-62497')
            ->firstOrFail();

        $this->assertSame('ATK Perpetual Sdn Bhd', $token->company->name);
        $this->assertSame('Marya Indra AzZahra Employment Agency', $token->agency->name);
        $this->assertSame('Demand Letter Submission', $token->category->name);
        $this->assertSame(1, $token->demanded_workers);
        $this->assertSame(1, $token->approved_workers);
        $this->assertSame('BHC-619/2025; 27/10/2025', $token->bhc_number);
        $this->assertSame('Khairul Arefin', $token->received_by);
        $this->assertSame('Khairul Arefin', $token->creator->name);
        $this->assertSame('Rashama Karmakar', $token->currentHolder->name);
        $this->assertSame('active', $token->file_status);

        $changePreWorkerToken = Token::with('category')
            ->where('token_number', 'DL-20258')
            ->whereHas('category', fn ($query) => $query->where('code', 'CPA'))
            ->firstOrFail();

        $this->assertSame(1, $changePreWorkerToken->required_worker_changes);
        $this->assertNull($changePreWorkerToken->demanded_workers);

        $this->assertDatabaseHas('tokens', [
            'token_number' => 'DL-42038',
            'created_by' => User::where('name', 'Sagor')->value('id'),
        ]);
        $this->assertSame(851, Token::whereBelongsTo(User::where('name', 'Khairul Arefin')->firstOrFail(), 'creator')->count());
        $this->assertSame(156, Token::whereBelongsTo(User::where('name', 'Sagor')->firstOrFail(), 'creator')->count());
        $this->assertSame(977, Token::whereBelongsTo(User::where('name', 'Md Ekhlas Uddin')->firstOrFail(), 'currentHolder')->count());
        $this->assertSame(75, Token::whereBelongsTo(User::where('name', 'Rashama Karmakar')->firstOrFail(), 'currentHolder')->count());
        $this->assertSame(252, Token::whereNull('current_holder_id')->count());
    }

    public function test_legacy_seeders_can_be_rerun_without_creating_duplicates(): void
    {
        $this->seed();

        $this->seed();

        $this->assertSame(751, Company::count());
        $this->assertSame(65, Agency::count());
        $this->assertSame(1304, Token::count());
    }
}
