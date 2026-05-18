<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Ejecuta todos los seeders en orden:
     *   1. MasterSeeder → Insumos, Productos, Cotizaciones, Eventos
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@yeticontrol.com'],
            [
                'name' => 'Juan Antonio Casanova',
                'password' => Hash::make('AdminYeti2026!'),
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            MasterSeeder::class,
        ]);
    }
}
