<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::insert([
            [
                'id'    => (string) Str::uuid(),
                'name'  => 'admin',
                'label' => 'Administrator',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'    => (string) Str::uuid(),
                'name'  => 'user',
                'label' => 'Standard User',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
