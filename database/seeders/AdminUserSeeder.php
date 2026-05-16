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
        $user = User::updateOrCreate(
            ['email' => 'admin@wakalinelogistics.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'phone' => '+234 810 066 5758',
                'is_admin' => true,
            ]
        );

        if ($user->wasRecentlyCreated) {
            echo "Admin user created successfully!\n";
        } else {
            echo "Admin user already exists, updated successfully!\n";
        }
        echo "Email: admin@wakalinelogistics.com\n";
        echo "Password: password\n";
    }
}
