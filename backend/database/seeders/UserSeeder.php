<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Admin
        User::firstOrCreate(
            ['email' => 'admin@cofund.com'],
            [
                'name' => 'Admin CoFund',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'balance' => 0.00,
                'email_verified_at' => now(),
            ]
        );

        // 2. Creator
        User::firstOrCreate(
            ['email' => 'creator@cofund.com'],
            [
                'name' => 'Creator CoFund (Ahmad)',
                'password' => Hash::make('password'),
                'role' => 'creator',
                'balance' => 1500000.00,
                'email_verified_at' => now(),
            ]
        );

        // 3. Backer 1
        User::firstOrCreate(
            ['email' => 'backer@cofund.com'],
            [
                'name' => 'Backer CoFund (Siti)',
                'password' => Hash::make('password'),
                'role' => 'backer',
                'balance' => 1000000.00,
                'email_verified_at' => now(),
            ]
        );

        // 4. Backer 2
        User::firstOrCreate(
            ['email' => 'budi@example.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'role' => 'backer',
                'balance' => 500000.00,
                'email_verified_at' => now(),
            ]
        );
    }
}
