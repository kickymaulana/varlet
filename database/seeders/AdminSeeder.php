<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'email' => 'kickymaulana@gmail.com',
            'name' => 'Kicky Maulana',
        ]);
    }
}
