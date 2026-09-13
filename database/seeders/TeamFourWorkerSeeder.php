<?php

namespace Database\Seeders;

use App\Models\Token;
use App\Models\Worker;
use App\Services\BhcReferenceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamFourWorkerSeeder extends Seeder
{
    public function run(): void
    {
        $counts = DB::transaction(function (): array {
            $tokensByReference = [];

            foreach (Token::query()->get(['id', 'token_number', 'bhc_number', 'received_on', 'created_by']) as $token) {
                foreach ([$token->token_number, $token->bhc_number] as $value) {
                    $reference = BhcReferenceService::normalize($value);

                    if ($reference !== '') {
                        $tokensByReference[$reference][$token->id] = $token;
                    }
                }
            }

            $counts = ['imported' => 0, 'existing' => 0, 'missing_reference' => 0, 'unmatched' => 0, 'ambiguous' => 0, 'invalid' => 0];

            foreach ($this->records() as $record) {
                $references = array_filter(array_unique(array_map(
                    BhcReferenceService::normalize(...),
                    [$record['bhc_number'] ?? null, $record['token_number'] ?? null, $record['reference'] ?? null],
                )), fn (string $reference): bool => $reference !== '');

                if ($references === []) {
                    $counts['missing_reference']++;

                    continue;
                }

                $matches = [];

                foreach ($references as $reference) {
                    $matches += $tokensByReference[$reference] ?? [];
                }

                if ($matches === []) {
                    $counts['unmatched']++;

                    continue;
                }

                if (count($matches) > 1) {
                    $bhcNumbers = array_unique(array_map(
                        fn (Token $token): string => BhcReferenceService::normalize($token->bhc_number),
                        $matches,
                    ));

                    if (count($bhcNumbers) !== 1 || ! in_array(reset($bhcNumbers), $references, true)) {
                        $counts['ambiguous']++;

                        continue;
                    }
                }

                $fullName = trim($record['full_name']);
                $passportNumber = strtoupper(trim($record['passport_number']));

                if ($fullName === '' || $passportNumber === '') {
                    $counts['invalid']++;

                    continue;
                }

                $token = collect($matches)->sortBy([['received_on', 'desc'], ['id', 'desc']])->first();
                $remarks = 'Imported from TEAM 4 BHC.xlsx, Sheet1 row '.$record['source_row'].'.';

                if ($record['arrival_date'] !== null) {
                    $remarks .= ' Arrival date: '.$record['arrival_date'].'.';
                }

                if (($record['boesl_confirmation_date'] ?? null) !== null) {
                    $remarks .= ' BOESL confirmation date: '.$record['boesl_confirmation_date'].'.';
                }

                $worker = Worker::withTrashed()->firstOrCreate(
                    ['passport_number' => $passportNumber],
                    [
                        'token_id' => $token->id,
                        'full_name' => $fullName,
                        'phone' => $record['phone'],
                        'remarks' => $remarks,
                        'created_by' => $token->created_by,
                    ],
                );

                $counts[$worker->wasRecentlyCreated ? 'imported' : 'existing']++;
            }

            return $counts;
        });

        $this->command?->info(sprintf(
            'Team 4 workers: %d imported, %d already exist, %d missing BHC/reference, %d unmatched, %d ambiguous, %d invalid.',
            ...array_values($counts),
        ));
    }

    /**
     * @return array<int, array{source_row: int, full_name: string, company_name: string, passport_number: string, arrival_date: ?string, phone: ?string, bhc_number: ?string, boesl_confirmation_date?: ?string, token_number?: ?string, reference?: ?string}>
     */
    protected function records(): array
    {
        return json_decode(
            file_get_contents(database_path('seeders/data/team_4_workers.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
