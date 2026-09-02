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
        $administratorId = User::query()
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['administrator', 'super-admin']))
            ->value('id')
            ?? throw new LogicException('Seed an administrator account before seeding tokens.');

        TokenCategory::upsert([
            ['name' => 'Demand Letter Submission', 'code' => 'DLS', 'is_active' => true, 'display_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Change Pre Worker', 'code' => 'CPA', 'is_active' => true, 'display_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Visa Attestation', 'code' => 'VA', 'is_active' => true, 'display_order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ], ['code'], ['name', 'is_active', 'display_order', 'updated_at']);

        $companyIds = Company::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Company $company): array => [strtolower($company->name) => $company->id])
            ->all();
        $agencyIds = Agency::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Agency $agency): array => [strtolower($agency->name) => $agency->id])
            ->all();
        $categoryIds = TokenCategory::pluck('id', 'name')->all();
        $userIds = User::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (User $user): array => [trim($user->name) => $user->id])
            ->all();

        foreach (array_chunk($this->records(), 100) as $records) {
            $tokens = array_map(function (array $record) use ($administratorId, $companyIds, $agencyIds, $categoryIds, $userIds): array {
                $companyName = $this->normalizeName($record['company_name']);
                $agencyName = $this->normalizeName($record['agency_name']);
                $categoryName = $record['category_name'];
                unset($record['company_name'], $record['agency_name'], $record['category_name']);

                $creatorName = trim((string) ($record['received_by'] ?? ''));
                $holderName = trim((string) ($record['file_status'] ?? ''));
                $creatorId = $userIds[$creatorName] ?? $administratorId;

                $record['company_id'] = $companyIds[strtolower($companyName)]
                    ?? throw new LogicException("Legacy company [{$companyName}] was not seeded.");
                $record['agency_id'] = $agencyIds[strtolower($agencyName)]
                    ?? throw new LogicException("Legacy agency [{$agencyName}] was not seeded.");
                $record['token_category_id'] = $categoryIds[$categoryName]
                    ?? throw new LogicException("Legacy token category [{$categoryName}] was not seeded.");
                $record['current_holder_id'] = $userIds[$holderName] ?? null;
                $record['created_by'] = $creatorId;
                $record['updated_by'] = $creatorId;
                $record['approved_workers'] = $record['approved_workers'] ?? 0;
                $record['pre_selected'] = (bool) ($record['pre_selected'] ?? false);
                $record['site_visit_required'] = (bool) ($record['site_visit_required'] ?? false);
                $record['required_visa_attestation'] = $record['required_visa_attestation'] ?? $this->requiredVisaAttestationFromRemarks($record['remarks'] ?? null);
                $record['required_worker_changes'] = $categoryName === 'Change Pre Worker'
                    ? $record['demanded_workers']
                    : null;
                $record['demanded_workers'] = $categoryName === 'Demand Letter Submission'
                    ? $record['demanded_workers']
                    : null;
                $record['boesl_status'] = $record['boesl_status'] ? 'submitted' : 'pending';
                $record['visa_status'] = $record['visa_status'] ?: 'pending';
                $record['file_status'] = 'active';
                $record['deleted_at'] = null;

                return $record;
            }, $records);

            foreach ($tokens as $token) {
                Token::updateOrCreate(
                    [
                        'token_number' => $token['token_number'],
                        'token_category_id' => $token['token_category_id'],
                        'company_id' => $token['company_id'],
                        'agency_id' => $token['agency_id'],
                        'received_on' => $token['received_on'],
                    ],
                    $token,
                );
            }
        }
    }

    private function normalizeName(string $name): string
    {
        return trim((string) preg_replace('/\s+/', ' ', str_replace('-', '', $name)));
    }

    private function requiredVisaAttestationFromRemarks(?string $remarks): ?int
    {
        if ($remarks === null || preg_match('/Legacy required visa attestations: (\d+)\./', $remarks, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
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
