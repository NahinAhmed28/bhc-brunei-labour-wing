<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Company;
use App\Models\Token;
use App\Models\TokenCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use JsonException;
use LogicException;

class TokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $administratorId = User::where('email', 'admin@bhcbrunei.gov.bd')->value('id')
            ?? throw new LogicException('Seed the administrator account before seeding tokens.');

        TokenCategory::upsert([
            ['name' => 'Demand Letter Submission', 'code' => 'DLS', 'is_active' => true, 'display_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Change Pre Applicant', 'code' => 'CPA', 'is_active' => true, 'display_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Visa Attestation', 'code' => 'VA', 'is_active' => true, 'display_order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ], ['code'], ['name', 'is_active', 'display_order', 'updated_at']);

        $companyIds = Company::pluck('id', 'name')->all();
        $agencyIds = Agency::pluck('id', 'name')->all();
        $categoryIds = TokenCategory::pluck('id', 'name')->all();

        foreach (array_chunk($this->records(), 100) as $records) {
            $tokens = array_map(function (array $record) use ($administratorId, $companyIds, $agencyIds, $categoryIds): array {
                $companyName = $record['company_name'];
                $agencyName = $record['agency_name'];
                $categoryName = $record['category_name'];
                unset($record['company_name'], $record['agency_name'], $record['category_name']);

                $record['company_id'] = $companyIds[$companyName]
                    ?? throw new LogicException("Legacy company [{$companyName}] was not seeded.");
                $record['agency_id'] = $agencyIds[$agencyName]
                    ?? throw new LogicException("Legacy agency [{$agencyName}] was not seeded.");
                $record['token_category_id'] = $categoryIds[$categoryName]
                    ?? throw new LogicException("Legacy token category [{$categoryName}] was not seeded.");
                $record['created_by'] = $administratorId;
                $record['updated_by'] = $administratorId;
                $record['deleted_at'] = null;

                return $record;
            }, $records);

            Token::upsert(
                $tokens,
                ['token_number'],
                [
                    'token_category_id',
                    'company_id',
                    'agency_id',
                    'received_on',
                    'demanded_workers',
                    'approved_workers',
                    'pre_selected',
                    'bhc_number',
                    'boesl_status',
                    'boesl_date',
                    'received_by',
                    'site_visit_required',
                    'site_visit_date',
                    'site_visit_by',
                    'visa_status',
                    'file_status',
                    'remarks',
                    'updated_by',
                    'updated_at',
                    'deleted_at',
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws JsonException
     */
    private function records(): array
    {
        return json_decode(
            file_get_contents(database_path('seeders/data/tokens.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
