<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Company;
use App\Models\Token;
use App\Models\TokenCategory;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\TeamFourWorkerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamFourWorkerSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_workers_by_bhc_or_token_reference_and_preserves_arrival_details(): void
    {
        $token = $this->createToken(['bhc_number' => 'BHC-619/2025; 27/10/2025']);

        $this->seedRecords([
            $this->record(['bhc_number' => ' bhc-619/2025 ', 'boesl_confirmation_date' => '2025-10-27']),
            $this->record(['passport_number' => 'A00000002', 'reference' => $token->token_number]),
            $this->record(['passport_number' => 'A00000003', 'token_number' => $token->token_number]),
        ]);

        $this->assertDatabaseCount('workers', 3);
        $this->assertDatabaseHas('workers', [
            'passport_number' => 'A00000001',
            'full_name' => 'Test Worker',
            'token_id' => $token->id,
            'phone' => '0123456',
            'flight_date' => null,
            'flight_status' => 'pending',
            'remarks' => 'Imported from TEAM 4 BHC.xlsx, Sheet1 row 2. Arrival date: 2026-08-01. BOESL confirmation date: 2025-10-27.',
            'created_by' => $token->created_by,
        ]);
    }

    public function test_skips_missing_unmatched_and_deleted_token_references_without_matching_by_company(): void
    {
        $token = $this->createToken();
        $deletedToken = $this->createToken(['token_number' => 'DL-DELETED']);
        $deletedToken->delete();

        $this->seedRecords([
            $this->record(['company_name' => $token->company->name, 'bhc_number' => '   ']),
            $this->record(['reference' => 'DL-UNKNOWN']),
            $this->record(['reference' => $deletedToken->token_number]),
        ]);

        $this->assertDatabaseCount('workers', 0);
        $this->assertDatabaseCount('tokens', 2);
    }

    public function test_skips_ambiguous_references_and_conflicting_token_matches(): void
    {
        $firstToken = $this->createToken(['bhc_number' => 'BHC-001']);
        $this->createToken(['bhc_number' => 'BHC-002']);

        $this->seedRecords([
            $this->record(['reference' => $firstToken->token_number]),
            $this->record(['bhc_number' => 'BHC-001', 'reference' => 'BHC-002']),
        ]);

        $this->assertDatabaseCount('workers', 0);
    }

    public function test_multiple_identifiers_for_the_same_token_import_once_and_reruns_preserve_edits(): void
    {
        $token = $this->createToken(['bhc_number' => 'BHC-001']);
        $record = $this->record(['bhc_number' => 'BHC-001', 'reference' => $token->token_number]);
        $this->seedRecords([$record, $record]);
        Worker::query()->sole()->update(['full_name' => 'Corrected Name', 'phone' => '7654321']);

        $this->seedRecords([$record]);

        $this->assertDatabaseCount('workers', 1);
        $this->assertDatabaseHas('workers', ['full_name' => 'Corrected Name', 'phone' => '7654321']);
    }

    public function test_existing_passports_are_not_reassigned_or_restored(): void
    {
        $originalToken = $this->createToken(['token_number' => 'DL-ORIGINAL']);
        $targetToken = $this->createToken(['token_number' => 'DL-TARGET']);
        $worker = Worker::create([
            'token_id' => $originalToken->id,
            'full_name' => 'Existing Worker',
            'passport_number' => 'A00000001',
            'created_by' => $originalToken->created_by,
        ]);
        $worker->delete();

        $this->seedRecords([$this->record(['reference' => $targetToken->token_number])]);

        $this->assertDatabaseCount('workers', 1);
        $this->assertSoftDeleted($worker);
        $this->assertDatabaseHas('workers', ['id' => $worker->id, 'token_id' => $originalToken->id, 'full_name' => 'Existing Worker']);
    }

    public function test_skips_workers_without_a_name_or_passport_and_accepts_missing_optional_details(): void
    {
        $token = $this->createToken();

        $this->seedRecords([
            $this->record(['reference' => $token->token_number, 'full_name' => ' ']),
            $this->record(['reference' => $token->token_number, 'passport_number' => ' ']),
            $this->record(['reference' => $token->token_number, 'phone' => null, 'arrival_date' => null]),
        ]);

        $this->assertDatabaseCount('workers', 1);
        $this->assertDatabaseHas('workers', [
            'passport_number' => 'A00000001',
            'phone' => null,
            'remarks' => 'Imported from TEAM 4 BHC.xlsx, Sheet1 row 2.',
        ]);
    }

    public function test_matches_bhc_prefixes_short_years_and_zero_padded_numbers(): void
    {
        $token = $this->createToken(['bhc_number' => '32/2026']);

        $this->seedRecords([$this->record(['bhc_number' => ' bhc-032/26 '])]);

        $this->assertDatabaseHas('workers', ['passport_number' => 'A00000001', 'token_id' => $token->id]);
    }

    public function test_does_not_match_different_or_missing_bhc_years_or_remove_token_reference_zeros(): void
    {
        $this->createToken(['bhc_number' => '032/2025']);
        $this->createToken(['bhc_number' => '032', 'token_number' => 'DL-54321']);

        $this->seedRecords([
            $this->record(['bhc_number' => 'BHC-032/26']),
            $this->record(['reference' => 'DL-012345']),
        ]);

        $this->assertDatabaseCount('workers', 0);
    }

    public function test_shared_bhc_references_use_the_latest_received_active_token(): void
    {
        $latestToken = $this->createToken(['token_number' => 'VA-11111', 'bhc_number' => '32/2026', 'received_on' => '2026-08-01']);
        $this->createToken(['token_number' => 'VA-22222', 'bhc_number' => 'BHC-032/26', 'received_on' => '2026-07-01']);
        $this->createToken(['token_number' => 'VA-33333', 'bhc_number' => '032/2026', 'received_on' => '2026-09-01'])->delete();

        $this->seedRecords([$this->record(['bhc_number' => 'BHC-032/26'])]);

        $this->assertDatabaseCount('workers', 1);
        $this->assertDatabaseHas('workers', ['passport_number' => 'A00000001', 'token_id' => $latestToken->id]);
    }

    public function test_shared_bhc_references_use_the_latest_id_when_received_dates_are_equal(): void
    {
        $this->createToken(['bhc_number' => '32/2026']);
        $latestToken = $this->createToken(['token_number' => 'VA-22222', 'bhc_number' => 'BHC-032/26']);

        $this->seedRecords([$this->record(['reference' => 'BHC-032/26'])]);

        $this->assertDatabaseHas('workers', ['passport_number' => 'A00000001', 'token_id' => $latestToken->id]);
    }

    public function test_default_seeding_imports_matching_workbook_workers_and_can_be_rerun(): void
    {
        $this->artisan('db:seed', ['--no-interaction' => true])
            ->expectsOutput('Team 4 workers: 105 imported, 0 already exist, 21 missing BHC/reference, 63 unmatched, 0 ambiguous, 0 invalid.')
            ->assertSuccessful();

        $this->assertDatabaseHas('workers', [
            'passport_number' => 'EL0069551',
            'full_name' => 'SOHEL',
            'token_id' => Token::where('token_number', 'VA-03943')->value('id'),
            'phone' => '7103272',
            'remarks' => 'Imported from TEAM 4 BHC.xlsx, Sheet1 row 14. Arrival date: 2026-06-19. BOESL confirmation date: 2025-03-11.',
        ]);
        $this->assertDatabaseMissing('workers', ['passport_number' => 'A12296447']);

        $this->artisan('db:seed', ['--class' => TeamFourWorkerSeeder::class, '--no-interaction' => true])
            ->expectsOutput('Team 4 workers: 0 imported, 105 already exist, 21 missing BHC/reference, 63 unmatched, 0 ambiguous, 0 invalid.')
            ->assertSuccessful();

        $this->assertDatabaseCount('workers', 105);
        $this->assertDatabaseCount('tokens', 1304);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createToken(array $attributes = []): Token
    {
        return Token::create(array_replace([
            'token_number' => 'DL-12345',
            'company_id' => Company::firstOrCreate(['name' => 'Test Company'])->id,
            'agency_id' => Agency::firstOrCreate(['name' => 'Test Agency'])->id,
            'token_category_id' => TokenCategory::firstOrCreate(['code' => 'DLS'], ['name' => 'Demand Letter Submission'])->id,
            'received_on' => '2026-07-01',
            'created_by' => User::factory()->create()->id,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{source_row: int, full_name: string, company_name: string, passport_number: string, arrival_date: ?string, phone: ?string, bhc_number: ?string, boesl_confirmation_date?: ?string, token_number?: ?string, reference?: ?string}
     */
    private function record(array $attributes = []): array
    {
        return array_replace([
            'source_row' => 2,
            'full_name' => 'Test Worker',
            'company_name' => 'Test Company',
            'passport_number' => 'A00000001',
            'arrival_date' => '2026-08-01',
            'phone' => '0123456',
            'bhc_number' => null,
        ], $attributes);
    }

    /**
     * @param  array<int, array{source_row: int, full_name: string, company_name: string, passport_number: string, arrival_date: ?string, phone: ?string, bhc_number: ?string, boesl_confirmation_date?: ?string, token_number?: ?string, reference?: ?string}>  $records
     */
    private function seedRecords(array $records): void
    {
        $seeder = new class($records) extends TeamFourWorkerSeeder
        {
            /**
             * @param  array<int, array{source_row: int, full_name: string, company_name: string, passport_number: string, arrival_date: ?string, phone: ?string, bhc_number: ?string, boesl_confirmation_date?: ?string, token_number?: ?string, reference?: ?string}>  $workerRecords
             */
            public function __construct(private array $workerRecords) {}

            protected function records(): array
            {
                return $this->workerRecords;
            }
        };

        $seeder->run();
    }
}
