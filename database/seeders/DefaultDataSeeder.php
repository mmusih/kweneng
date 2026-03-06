<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\ClassModel;

class DefaultDataSeeder extends Seeder
{
    public function run()
    {
        // Create default admin user
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@school.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create sample academic year
        $academicYear = AcademicYear::create([
            'year_name' => '2024/2025',
            'active' => true,
        ]);

        // Create sample classes
        ClassModel::insert([
            ['name' => 'Form 1A', 'level' => 1],
            ['name' => 'Form 1B', 'level' => 1],
            ['name' => 'Form 2A', 'level' => 2],
            ['name' => 'Form 2B', 'level' => 2],
            ['name' => 'Form 3A', 'level' => 3],
            ['name' => 'Form 3B', 'level' => 3],
        ]);
    }
}
