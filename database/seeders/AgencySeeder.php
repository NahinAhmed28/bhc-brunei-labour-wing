<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Seeder;
use JsonException;
use LogicException;

class AgencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $administratorId = User::query()
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['administrator', 'super-admin']))
            ->value('id')
            ?? throw new LogicException('Seed an administrator account before seeding agencies.');

        foreach (array_chunk($this->records(), 200) as $records) {
            Agency::upsert(
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
            file_get_contents(database_path('seeders/data/agencies.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
