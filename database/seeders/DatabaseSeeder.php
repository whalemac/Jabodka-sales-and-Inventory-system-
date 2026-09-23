<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

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
    }
}
