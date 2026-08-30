<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use LogicException;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleMapping = [
            'super-admin' => 'super-admin',
            'admin' => 'administrator',
            'boesel-admin' => 'data-entry',
            'brhc-admin' => 'administrator',
        ];
        $roleIds = Role::whereIn('name', array_values($roleMapping))->pluck('id', 'name');

        if ($roleIds->count() !== count(array_unique($roleMapping))) {
            throw new LogicException('Seed roles before seeding users.');
        }

        foreach ($this->records() as $record) {
            $roleName = $roleMapping[$record['role']] ?? throw new LogicException('Unknown imported role: '.$record['role']);
            if (password_get_info($record['password'])['algoName'] !== 'bcrypt') {
                throw new LogicException('Invalid imported password hash for '.$record['email'].'.');
            }

            $user = User::firstOrNew(['email' => $record['email']]);
            $user->forceFill([
                'name' => $record['name'],
                'email' => $record['email'],
                'email_verified_at' => $record['email_verified_at'],
                'role_id' => $roleIds[$roleName],
                'is_active' => $record['status'] === 'active',
                'remember_token' => null,
                'created_at' => $record['created_at'],
                'updated_at' => $record['updated_at'],
            ]);
            $user->setRawAttributes(array_merge($user->getAttributes(), ['password' => $record['password']]));
            $user->timestamps = false;
            $user->save();
        }
    }

    /**
     * @return array<int, array{
     *     name: string,
     *     email: string,
     *     email_verified_at: ?string,
     *     password: string,
     *     role: string,
     *     status: string,
     *     created_at: ?string,
     *     updated_at: ?string
     * }>
     */
    private function records(): array
    {
        return [
            [
                'name' => 'Admin User',
                'email' => 'mission.bandarseribegawan@mofa.gov.bd',
                'email_verified_at' => '2025-10-19 04:47:38',
                'password' => '$2y$12$Ij8Nfa60B3X8WVB96kGuGuDKxRhKQC.EnxZyju41YV.zVIFaev3i.',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => '2025-10-19 04:47:38',
                'updated_at' => '2025-10-19 04:47:38',
            ],
            [
                'name' => 'BoeselAdmins User',
                'email' => 'boeseladmin@gmail.com',
                'email_verified_at' => '2025-10-19 04:47:39',
                'password' => '$2y$12$j8ETpvFiGPlT87yPOdixhuIFCMEtIMPjWFwsK5jV.i3gE.pQygP8C',
                'role' => 'boesel-admin',
                'status' => 'active',
                'created_at' => '2025-10-19 04:47:39',
                'updated_at' => '2025-10-19 04:47:39',
            ],
            [
                'name' => 'Super Admin User',
                'email' => 'superadmin@gmail.com',
                'email_verified_at' => '2025-10-19 04:47:39',
                'password' => '$2y$12$CB1W2OfdNXjXihazki8a2ebPxlw0vnU8FsDrmeJehIQ.IvoqY3jWe',
                'role' => 'super-admin',
                'status' => 'active',
                'created_at' => '2025-10-19 04:47:39',
                'updated_at' => '2025-10-19 04:47:39',
            ],
            [
                'name' => 'BRHC Admin User',
                'email' => 'brhcadmin@gmail.com',
                'email_verified_at' => '2025-10-19 04:47:39',
                'password' => '$2y$12$Kg4k2V9Uuq7TCadodP9K.e.z/qi3CXIy9twV0bb3BD2/EsmpgRanO',
                'role' => 'brhc-admin',
                'status' => 'active',
                'created_at' => '2025-10-19 04:47:39',
                'updated_at' => '2025-10-19 04:47:39',
            ],
            [
                'name' => 'Khairul Arefin',
                'email' => 'khairularifinsabir@gmail.com',
                'email_verified_at' => '2025-10-19 04:47:39',
                'password' => '$2y$12$Ij8Nfa60B3X8WVB96kGuGuDKxRhKQC.EnxZyju41YV.zVIFaev3i.',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => '2025-10-19 04:47:39',
                'updated_at' => '2025-10-19 04:47:39',
            ],
            [
                'name' => 'Fateema ',
                'email' => 'fateema.labourwing@gmail.com',
                'email_verified_at' => '2025-10-19 04:47:39',
                'password' => '$2y$12$NfbMoOufPVQR3zN19N7k3.3os0m7fn63fNiD8FQxlm6Mb/To.BXU2',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => '2025-10-22 14:29:25',
                'updated_at' => '2025-10-22 07:00:40',
            ],
            [
                'name' => 'Joynal Abedin',
                'email' => 'Bhcgov9@gmail.com',
                'email_verified_at' => '2025-10-19 04:47:39',
                'password' => '$2y$12$Ij8Nfa60B3X8WVB96kGuGuDKxRhKQC.EnxZyju41YV.zVIFaev3i.',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'name' => 'Rashama Karmakar',
                'email' => 'rashama2010@gmail.com',
                'email_verified_at' => '2025-10-19 04:47:39',
                'password' => '$2y$12$Ij8Nfa60B3X8WVB96kGuGuDKxRhKQC.EnxZyju41YV.zVIFaev3i.',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'name' => 'High Commissioner',
                'email' => 'hc.bandarseribegawan@mofa.gov.bd',
                'email_verified_at' => '2025-10-19 04:47:39',
                'password' => '$2y$12$Ij8Nfa60B3X8WVB96kGuGuDKxRhKQC.EnxZyju41YV.zVIFaev3i.',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'name' => 'Md Abu Bakkar Siddique',
                'email' => 'labourwingbrunei@gmail.com',
                'email_verified_at' => null,
                'password' => '$2y$12$ywltDMeVsHWeD8LTtgFCt.wBOs4h6mAmbhQcfYPIdqgSaOCScu7Q2',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => '2025-11-24 09:34:22',
                'updated_at' => '2026-04-13 00:50:55',
            ],
            [
                'name' => 'Md Ekhlas Uddin',
                'email' => 'eklassd@gmail.com',
                'email_verified_at' => null,
                'password' => '$2y$12$ApOjNYcTSWJXLT8TtJ7diesXMcOYYY.nMY0O3TV7XVG7yT7EO8cea',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => '2025-12-05 08:03:20',
                'updated_at' => '2025-12-05 08:03:20',
            ],
            [
                'name' => 'NAUREEN TABASSUM',
                'email' => 'naureentabassum12@gmail.com',
                'email_verified_at' => null,
                'password' => '$2y$12$xmsa7eyJc78YYY9AXXj5w.AO61z6UIFacHRaLqbgHhbxjCbCCJfNW',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => '2026-05-13 08:07:48',
                'updated_at' => '2026-05-13 08:07:48',
            ],
            [
                'name' => 'Sagor',
                'email' => 'sagor@bd2bn.com',
                'email_verified_at' => null,
                'password' => '$2y$12$UUZTVo6XXAe237CxwqTABOneqmuKWdJNadJQepPdjfFBmtkGpEvN6',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => '2026-07-20 18:10:31',
                'updated_at' => '2026-07-20 18:10:31',
            ],
            [
                'name' => 'aman',
                'email' => 'aman@gmail.com',
                'email_verified_at' => null,
                'password' => '$2y$12$UdtLZMNkIwIvDcgaXTharO9kqxeW.r6R0iLiVLaKXdp1NCEu2AzKO',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => '2026-07-31 16:20:37',
                'updated_at' => '2026-07-31 16:20:37',
            ],
        ];
    }
}
