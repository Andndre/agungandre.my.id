<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'admin@gmail.com';
        $password = env('ADMIN_PASSWORD', 'admin123');

        if (User::where('email', $email)->doesntExist()) {
            User::factory()->create([
                'name' => 'Admin',
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        }
    }
}
