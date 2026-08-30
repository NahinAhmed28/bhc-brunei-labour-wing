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
        $administratorId = User::where('email', 'admin@bhcbrunei.gov.bd')->value('id')
            ?? throw new LogicException('Seed the administrator account before seeding companies.');

        foreach (array_chunk($this->records(), 200) as $records) {
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
