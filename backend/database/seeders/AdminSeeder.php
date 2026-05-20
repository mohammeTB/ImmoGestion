<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'talibi',
                'role' => 'admin',
                'password' => 'admin81',
            ]
        );
        User::updateOrCreate(
            ['email' => 'locataire@locataire.com'],
            [
                'name' => 'ghandour',
                'role' => 'locataire',
                'password' => 'locataire81',
            ]
        );
        User::updateOrCreate(
            ['email' => 'proprietaire@proprietaire.com'],
            [
                'name' => 'idrissi',
                'role' => 'proprietaire',
                'password' => 'proprietaire81',
            ]
        );
    }
}
