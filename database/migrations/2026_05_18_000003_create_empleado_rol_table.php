<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla pivote empleado_rol (N:M)
 *
 * Un empleado puede tener múltiples roles y
 * un rol puede pertenecer a muchos empleados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleado_rol', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->foreignId('rol_id')->constrained('roles_staff')->cascadeOnDelete();
            $table->boolean('es_rol_principal')->default(false);
            $table->timestamps();

            $table->unique(['empleado_id', 'rol_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_rol');
    }
};
