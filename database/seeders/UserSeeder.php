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
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@kalaexcel.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@kalaexcel.com',
                'password' => Hash::make('password'),
                'phone' => '+255123456789',
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        // Optionally create other role users for testing
        User::firstOrCreate(
            ['email' => 'manager@kalaexcel.com'],
            [
                'name' => 'Manager User',
                'email' => 'manager@kalaexcel.com',
                'password' => Hash::make('password'),
                'phone' => '+255123456790',
                'role' => 'manager',
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'cashier@kalaexcel.com'],
            [
                'name' => 'Cashier User',
                'email' => 'cashier@kalaexcel.com',
                'password' => Hash::make('password'),
                'phone' => '+255123456791',
                'role' => 'cashier',
                'status' => 'active',
            ]
        );
    }
}
