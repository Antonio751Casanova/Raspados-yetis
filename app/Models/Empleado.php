<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo: Empleado
 *
 * Representa al personal/staff de Raspados Yeti.
 * Módulo E — Recursos Humanos
 *
 * @property int         $id
 * @property string      $nombre
 * @property string      $apellido_paterno
 * @property string|null $apellido_materno
 * @property string|null $telefono
 * @property string|null $correo
 * @property string|null $direccion
 * @property \Carbon\Carbon $fecha_ingreso
 * @property string|null $puesto
 * @property float       $salario_por_evento
 * @property string      $estado            activo|inactivo
 * @property string|null $fotografia
 * @property string|null $notas
 */
class Empleado extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'empleados';

    // ─── Constantes ───────────────────────────────────────────────────────

    public const ESTADO_ACTIVO   = 'activo';
    public const ESTADO_INACTIVO = 'inactivo';

    // ─── Fillable ─────────────────────────────────────────────────────────

    protected $fillable = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'correo',
        'direccion',
        'fecha_ingreso',
        'puesto',
        'salario_por_evento',
        'estado',
        'fotografia',
        'notas',
    ];

    // ─── Casts ────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'fecha_ingreso'     => 'date',
            'salario_por_evento' => 'decimal:2',
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────

    /**
     * Roles que posee este empleado (N:M).
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RolStaff::class, 'empleado_rol', 'empleado_id', 'rol_id')
                    ->withPivot('es_rol_principal')
                    ->withTimestamps();
    }

    /**
     * Eventos a los que ha sido asignado (N:M a través de asignaciones_evento).
     */
    public function eventos(): BelongsToMany
    {
        return $this->belongsToMany(Evento::class, 'asignaciones_evento', 'empleado_id', 'evento_id')
                    ->withPivot([
                        'rol_staff_id',
                        'rol_asignado',
                        'hora_inicio',
                        'hora_fin',
                        'estado_asistencia',
                        'observaciones',
                    ])
                    ->withTimestamps();
    }

    /**
     * Asignaciones directas (para consultas detalladas).
     */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionEvento::class, 'empleado_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    /** Sólo empleados activos. */
    public function scopeActivos($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    // ─── Accessors ────────────────────────────────────────────────────────

    /** Nombre completo formateado. */
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido_paterno} {$this->apellido_materno}");
    }

    /** URL pública de la fotografía (o placeholder). */
    public function getFotoUrlAttribute(): string
    {
        if ($this->fotografia && file_exists(storage_path("app/public/{$this->fotografia}"))) {
            return asset("storage/{$this->fotografia}");
        }
        return "https://ui-avatars.com/api/?name=" . urlencode($this->nombre_completo) . "&background=6366f1&color=fff&size=128";
    }

    // ─── Métodos de negocio ───────────────────────────────────────────────

    /** Indica si el empleado está disponible (activo). */
    public function estaActivo(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO;
    }

    /**
     * Verifica si el empleado tiene conflicto de horario con un rango dado.
     *
     * @param string $fecha        Fecha del evento (Y-m-d)
     * @param string $horaInicio   HH:MM
     * @param string $horaFin      HH:MM
     * @param int|null $excluirEventoId Excluir asignación de este evento (edición)
     * @return bool
     */
    public function tieneConflictoHorario(
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?int $excluirEventoId = null
    ): bool {
        return $this->asignaciones()
            ->whereHas('evento', function ($q) use ($fecha) {
                $q->where('fecha_evento', $fecha)
                  ->whereNotIn('estado', ['Cancelado']);
            })
            ->when($excluirEventoId, fn($q) => $q->where('evento_id', '!=', $excluirEventoId))
            ->where(function ($q) use ($horaInicio, $horaFin) {
                // Solapamiento: [inicio1, fin1] ∩ [inicio2, fin2] ≠ ∅
                $q->where(function ($inner) use ($horaInicio, $horaFin) {
                    $inner->where('hora_inicio', '<', $horaFin)
                          ->where('hora_fin',    '>', $horaInicio);
                });
            })
            ->exists();
    }
}
