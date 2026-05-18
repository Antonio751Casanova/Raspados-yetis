<?php

namespace Database\Seeders;

use App\Models\RolStaff;
use Illuminate\Database\Seeder;

/**
 * Seeder: RolesStaffSeeder
 *
 * Carga los roles predefinidos del sistema de RR. HH.
 * Módulo E — Recursos Humanos
 */
class RolesStaffSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'nombre'      => 'Administrador',
                'descripcion' => 'Gestión general del evento y coordinación del equipo.',
                'color_hex'   => '#7c3aed', // violeta
            ],
            [
                'nombre'      => 'Encargado',
                'descripcion' => 'Responsable operativo in-situ del evento.',
                'color_hex'   => '#2563eb', // azul
            ],
            [
                'nombre'      => 'Ayudante',
                'descripcion' => 'Apoyo en preparación y servicio durante el evento.',
                'color_hex'   => '#d97706', // ámbar
            ],
        ];

        foreach ($roles as $rol) {
            RolStaff::firstOrCreate(
                ['nombre' => $rol['nombre']],
                array_merge($rol, ['activo' => true])
            );
        }

        $this->command->info('✅ Roles de staff cargados correctamente.');
    }
}
