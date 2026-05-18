<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla empleados
 *
 * Almacena el personal/staff de Raspados Yeti.
 * Módulo E — Recursos Humanos
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();

            // Datos personales
            $table->string('nombre', 100);
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('correo', 150)->nullable()->unique();
            $table->text('direccion')->nullable();

            // Datos laborales
            $table->date('fecha_ingreso');
            $table->string('puesto', 100)->nullable();
            $table->decimal('salario_por_evento', 10, 2)->default(0);
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');

            // Multimedia y notas
            $table->string('fotografia')->nullable();  // ruta relativa en storage
            $table->text('notas')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('estado');
            $table->index('fecha_ingreso');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
