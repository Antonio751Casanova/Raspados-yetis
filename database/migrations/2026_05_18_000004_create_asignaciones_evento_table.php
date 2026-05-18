<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla asignaciones_evento
 *
 * Relaciona empleados con eventos (asignación de staff).
 * Incluye campos de horario para detección de conflictos.
 * Módulo E — Recursos Humanos
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_evento', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('empleado_id')
                  ->constrained('empleados')
                  ->cascadeOnDelete();

            $table->foreignId('evento_id')
                  ->constrained('eventos')
                  ->cascadeOnDelete();

            // Rol asignado para este evento específico
            $table->foreignId('rol_staff_id')
                  ->nullable()
                  ->constrained('roles_staff')
                  ->nullOnDelete();

            $table->string('rol_asignado', 80)->nullable(); // copia textual del rol

            // Horario de participación
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();

            // Estado de asistencia
            $table->enum('estado_asistencia', [
                'pendiente',
                'confirmado',
                'asistio',
                'falto',
            ])->default('pendiente');

            $table->text('observaciones')->nullable();

            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index(['empleado_id', 'evento_id']);
            $table->index('estado_asistencia');

            // Un empleado no puede estar asignado dos veces al mismo evento
            $table->unique(['empleado_id', 'evento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_evento');
    }
};
