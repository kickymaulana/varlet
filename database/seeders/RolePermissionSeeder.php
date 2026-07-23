<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);

        $existingUser = User::where('email', 'dedimaulana@gmail.com')->first();
        if ($existingUser) {
            $existingUser->assignRole('admin');
            $this->command->info("Role 'admin' assigned to {$existingUser->email}");
        } else {
            $user = User::create([
                'name' => 'Dedi Maulana',
                'email' => 'dedimaulana@gmail.com',
                'password' => bcrypt(\Str::random(32)),
            ]);
            $user->assignRole('admin');
            $this->command->info("User {$user->email} created and assigned 'admin' role");
        }

        $adminUser = User::where('email', 'kickymaulana@gmail.com')->first();
        if ($adminUser && !$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
            $this->command->info("Role 'admin' assigned to {$adminUser->email}");
        }
    }
}
