<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FakeUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing users (optional - remove if you don't want to delete old data)
        // DB::table('users')->truncate();

        for ($i = 1; $i <= 10; $i++) {
            $name = fake()->name();
            $email = fake()->unique()->safeEmail();

            DB::table('users')->insert([
                'name'       => $name,
                'email'      => $email,
                'password'   => Hash::make('password'),     // password = "password"
                'created_at' => now()->subDays(rand(0, 30)), // random date in last 30 days
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✅ 10 fake users created successfully!');
    }
}