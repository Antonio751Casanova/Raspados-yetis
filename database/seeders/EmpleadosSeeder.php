<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Models\RolStaff;
use Illuminate\Database\Seeder;

/**
 * Seeder: EmpleadosSeeder
 *
 * Carga empleados de ejemplo para desarrollo y pruebas.
 * Módulo E — Recursos Humanos
 */
class EmpleadosSeeder extends Seeder
{
    public function run(): void
    {
        $adminRol    = RolStaff::where('nombre', 'Administrador')->first();
        $encargadoRol = RolStaff::where('nombre', 'Encargado')->first();
        $ayudanteRol  = RolStaff::where('nombre', 'Ayudante')->first();

        $empleados = [
            [
                'nombre'             => 'Carlos',
                'apellido_paterno'   => 'García',
                'apellido_materno'   => 'López',
                'telefono'           => '6181234567',
                'correo'             => 'carlos.garcia@raspados.yeti',
                'fecha_ingreso'      => '2024-01-15',
                'puesto'             => 'Encargado de Eventos',
                'salario_por_evento' => 400.00,
                'estado'             => 'activo',
                'notas'              => 'Personal confiable, maneja vehículo propio.',
                'rol'                => $encargadoRol,
            ],
            [
                'nombre'             => 'María',
                'apellido_paterno'   => 'Rodríguez',
                'apellido_materno'   => 'Pérez',
                'telefono'           => '6189876543',
                'correo'             => 'maria.rodriguez@raspados.yeti',
                'fecha_ingreso'      => '2024-03-01',
                'puesto'             => 'Ayudante',
                'salario_por_evento' => 300.00,
                'estado'             => 'activo',
                'notas'              => null,
                'rol'                => $ayudanteRol,
            ],
            [
                'nombre'             => 'Juan',
                'apellido_paterno'   => 'Martínez',
                'apellido_materno'   => 'Soto',
                'telefono'           => '6181112233',
                'correo'             => 'juan.martinez@raspados.yeti',
                'fecha_ingreso'      => '2024-06-10',
                'puesto'             => 'Ayudante General',
                'salario_por_evento' => 200.00,
                'estado'             => 'activo',
                'notas'              => 'Estudiante, disponibilidad fines de semana.',
                'rol'                => $ayudanteRol,
            ],
            [
                'nombre'             => 'Roberto',
                'apellido_paterno'   => 'Herrera',
                'apellido_materno'   => 'Cruz',
                'telefono'           => '6184445566',
                'correo'             => 'roberto.herrera@raspados.yeti',
                'fecha_ingreso'      => '2023-11-20',
                'puesto'             => 'Administrador',
                'salario_por_evento' => 500.00,
                'estado'             => 'activo',
                'notas'              => 'Responsable de operaciones generales.',
                'rol'                => $adminRol,
            ],
            [
                'nombre'             => 'Ana',
                'apellido_paterno'   => 'Flores',
                'apellido_materno'   => 'Vega',
                'telefono'           => '6187778899',
                'correo'             => 'ana.flores@raspados.yeti',
                'fecha_ingreso'      => '2025-02-14',
                'puesto'             => 'Ayudante',
                'salario_por_evento' => 200.00,
                'estado'             => 'inactivo',
                'notas'              => 'Baja temporal por estudios.',
                'rol'                => $ayudanteRol,
            ],
        ];

        foreach ($empleados as $data) {
            $rol = $data['rol'];
            unset($data['rol']);

            $empleado = Empleado::firstOrCreate(
                ['correo' => $data['correo']],
                $data
            );

            if ($rol) {
                $empleado->roles()->syncWithoutDetaching([
                    $rol->id => ['es_rol_principal' => true],
                ]);
            }
        }

        $this->command->info('✅ Empleados de ejemplo cargados correctamente.');
    }
}
