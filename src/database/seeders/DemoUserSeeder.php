<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('DEMO_USER_EMAIL', 'demo@example.com')],
            [
                'name' => 'Demo User',
                'password' => Hash::make(env('DEMO_USER_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ]
        );
    }
}