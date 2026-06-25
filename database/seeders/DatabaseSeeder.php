<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@inventory.local'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'status' => UserStatus::Active,
            ]
        );

        $admin->assignRole(Role::findByName('Super Administrator', 'api'));
    }
}
