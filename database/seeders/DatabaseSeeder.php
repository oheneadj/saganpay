<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@saganpay.com'],
            [
                'name' => 'SaganPay Admin',
                'password' => bcrypt('password'), // You should change this after first login
            ]
        );
    }
}
