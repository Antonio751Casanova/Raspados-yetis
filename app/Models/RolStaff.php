<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo: RolStaff
 *
 * Catálogo de roles/funciones del personal.
 * Módulo E — Recursos Humanos
 *
 * Roles predefinidos:
 *   - Administrador
 *   - Encargado
 *   - Operador de Carrito
 *   - Ayudante
 *   - Chofer
 */
class RolStaff extends Model
{
    use HasFactory;

    protected $table = 'roles_staff';

    protected $fillable = [
        'nombre',
        'descripcion',
        'color_hex',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────

    /**
     * Empleados que poseen este rol (N:M).
     */
    public function empleados(): BelongsToMany
    {
        return $this->belongsToMany(Empleado::class, 'empleado_rol', 'rol_id', 'empleado_id')
                    ->withPivot('es_rol_principal')
                    ->withTimestamps();
    }
}
