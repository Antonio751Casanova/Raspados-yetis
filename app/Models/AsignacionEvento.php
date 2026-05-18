<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo: AsignacionEvento
 *
 * Relaciona un empleado con un evento específico.
 * Incluye rol asignado, horario y estado de asistencia.
 * Módulo E — Recursos Humanos
 *
 * @property int         $id
 * @property int         $empleado_id
 * @property int         $evento_id
 * @property int|null    $rol_staff_id
 * @property string|null $rol_asignado
 * @property string|null $hora_inicio
 * @property string|null $hora_fin
 * @property string      $estado_asistencia  pendiente|confirmado|asistio|falto
 * @property string|null $observaciones
 */
class AsignacionEvento extends Model
{
    use HasFactory;

    protected $table = 'asignaciones_evento';

    // ─── Constantes de estado ─────────────────────────────────────────────

    public const ESTADO_PENDIENTE  = 'pendiente';
    public const ESTADO_CONFIRMADO = 'confirmado';
    public const ESTADO_ASISTIO    = 'asistio';
    public const ESTADO_FALTO      = 'falto';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_CONFIRMADO,
        self::ESTADO_ASISTIO,
        self::ESTADO_FALTO,
    ];

    // ─── Fillable ─────────────────────────────────────────────────────────

    protected $fillable = [
        'empleado_id',
        'evento_id',
        'rol_staff_id',
        'rol_asignado',
        'hora_inicio',
        'hora_fin',
        'estado_asistencia',
        'observaciones',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────

    /** Empleado asignado. */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    /** Evento al que pertenece la asignación. */
    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }

    /** Rol de staff seleccionado. */
    public function rolStaff(): BelongsTo
    {
        return $this->belongsTo(RolStaff::class, 'rol_staff_id');
    }

    // ─── Accessors ────────────────────────────────────────────────────────

    /** Etiqueta legible del estado. */
    public function getEstadoEtiquetaAttribute(): string
    {
        return match ($this->estado_asistencia) {
            self::ESTADO_PENDIENTE  => 'Pendiente',
            self::ESTADO_CONFIRMADO => 'Confirmado',
            self::ESTADO_ASISTIO    => 'Asistió',
            self::ESTADO_FALTO      => 'Faltó',
            default                 => $this->estado_asistencia,
        };
    }
}
