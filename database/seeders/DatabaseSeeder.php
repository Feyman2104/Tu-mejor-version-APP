<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin de prueba
        User::create([
            'name'     => 'Admin TMV',
            'email'    => 'admin@tumejorversion.app',
            'password' => Hash::make('password'),
            'role'     => 'admin',
            'onboarding_completed_at' => now(),
        ]);

        // Student de prueba
        User::create([
            'name'     => 'Estudiante Demo',
            'email'    => 'demo@tumejorversion.app',
            'password' => Hash::make('password'),
            'role'     => 'student',
            'level'    => 'beginner',
            'goal'     => 'fat_loss',
            'equipment' => ['dumbbells', 'bands'],
            'injuries'  => [],
            'onboarding_completed_at' => now(),
        ]);

        $this->call([
            ExerciseSeeder::class,
            ColombianFoodsSeeder::class,
        ]);
    }
}
