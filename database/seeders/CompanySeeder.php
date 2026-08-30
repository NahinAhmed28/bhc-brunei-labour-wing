<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use JsonException;
use LogicException;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $administratorId = User::query()
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['administrator', 'super-admin']))
            ->value('id')
            ?? throw new LogicException('Seed an administrator account before seeding companies.');

        foreach (array_chunk($this->normalizedRecords(), 200) as $records) {
            Company::upsert(
                array_map(fn (array $record): array => $record + [
                    'is_active' => true,
                    'created_by' => $administratorId,
                    'updated_by' => $administratorId,
                    'deleted_at' => null,
                ], $records),
                ['name'],
                ['email', 'phone', 'remarks', 'is_active', 'updated_by', 'updated_at', 'deleted_at'],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizedRecords(): array
    {
        $normalizedRecords = [];

        foreach ($this->records() as $record) {
            $record = array_replace([
                'email' => null,
                'phone' => null,
                'remarks' => null,
                'created_at' => null,
                'updated_at' => null,
            ], $record);
            $record['name'] = $this->normalizeName($record['name']);
            $normalizedKey = strtolower($record['name']);
            $existingRecord = $normalizedRecords[$normalizedKey] ?? [];

            foreach ($record as $key => $value) {
                if ($value !== null) {
                    $existingRecord[$key] = $value;
                }
            }

            $normalizedRecords[$normalizedKey] = array_replace($record, $existingRecord);
        }

        return array_values($normalizedRecords);
    }

    private function normalizeName(string $name): string
    {
        return trim((string) preg_replace('/\s+/', ' ', str_replace('-', '', $name)));
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws JsonException
     */
    private function records(): array
    {
        return json_decode(
            file_get_contents(database_path('seeders/data/companies.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
