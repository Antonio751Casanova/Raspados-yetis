<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla roles_staff
 *
 * Catálogo de roles/funciones del personal.
 * Módulo E — Recursos Humanos
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles_staff', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();           // ej: Administrador, Chofer
            $table->string('descripcion', 255)->nullable();
            $table->string('color_hex', 7)->default('#6366f1'); // color para UI/calendario
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_staff');
    }
};
