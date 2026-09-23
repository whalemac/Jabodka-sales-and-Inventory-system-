<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
<<<<<<< HEAD
use Illuminate\Support\Facades\Hash;
=======
>>>>>>> e30c199068b93b642068a39e0e94a172dda70cf0

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

<<<<<<< HEAD
    public function run(): void
    {
        // Core accounts — always seed these first
        $accounts = [
            [
                'username' => 'superadmin',
                'password' => Hash::make('password'),
                'role'     => 'super_admin',
            ],
            [
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role'     => 'admin',
            ],
            [
                'username' => 'staff',
                'password' => Hash::make('password'),
                'role'     => 'staff',
            ],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['username' => $account['username']],
                $account,
            );
        }
=======
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
>>>>>>> e30c199068b93b642068a39e0e94a172dda70cf0
    }
}
