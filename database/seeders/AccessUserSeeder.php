<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccessUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@bendahara.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin123'),
                'role' => 'super_admin',
                'is_super_permanent' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'adminanggota@bendahara.local'],
            [
                'name' => 'Admin Anggota',
                'password' => Hash::make('adminanggota123'),
                'role' => 'admin_anggota',
                'is_super_permanent' => false,
            ]
        );
    }
}
