<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@wakalinelogistics.com',
            'password' => Hash::make('password'),
            'phone' => '+234 810 066 5758',
            'is_admin' => true,
        ]);

        echo "Admin user created successfully!\n";
        echo "Email: admin@wakalinelogistics.com\n";
        echo "Password: password\n";
    }
}
