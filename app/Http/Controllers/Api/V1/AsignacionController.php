<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAsignacionRequest;
use App\Models\AsignacionEvento;
use App\Models\Empleado;
use App\Models\Evento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controlador: AsignacionController
 *
 * Gestiona la asignación de empleados a eventos,
 * con validación automática de conflictos de horario.
 * Módulo E — Recursos Humanos
 */
class AsignacionController extends Controller
{
    // ─── Listar asignaciones de un evento ────────────────────────────────

    /**
     * GET /api/v1/eventos/{eventoId}/asignaciones
     */
    public function porEvento(int $eventoId): JsonResponse
    {
        $evento = Evento::findOrFail($eventoId);

        $asignaciones = AsignacionEvento::where('evento_id', $eventoId)
            ->with([
                'empleado:id,nombre,apellido_paterno,telefono,fotografia',
                'rolStaff:id,nombre,color_hex',
            ])
            ->orderBy('hora_inicio')
            ->get();

        // Añadir URLs de foto
        $asignaciones->each(function ($a) {
            if ($a->empleado) {
                $a->empleado->foto_url        = $a->empleado->foto_url;
                $a->empleado->nombre_completo = $a->empleado->nombre_completo;
            }
        });

        return response()->json([
            'ok'    => true,
            'datos' => [
                'evento'      => $evento->only(['id', 'cliente_nombre', 'fecha_evento', 'hora_inicio', 'hora_fin']),
                'asignaciones' => $asignaciones,
                'total'       => $asignaciones->count(),
            ],
        ]);
    }

    // ─── Crear asignación ────────────────────────────────────────────────

    /**
     * POST /api/v1/asignaciones
     *
     * Asigna un empleado a un evento validando:
     * 1. El empleado esté activo.
     * 2. El evento no esté cancelado.
     * 3. No haya conflicto de horario.
     * 4. No exista ya una asignación duplicada.
     */
    public function store(StoreAsignacionRequest $request): JsonResponse
    {
        $data = $request->validated();

        $empleado = Empleado::findOrFail($data['empleado_id']);
        $evento   = Evento::findOrFail($data['evento_id']);

        // ── Validación de negocio ────────────────────────────────────────

        // 1. Empleado activo
        if (!$empleado->estaActivo()) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'No se puede asignar a un empleado inactivo.',
                'codigo'  => 'EMPLEADO_INACTIVO',
            ], 422);
        }

        // 2. Evento no cancelado
        if ($evento->estado === 'Cancelado') {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'El evento está cancelado y no acepta asignaciones.',
                'codigo'  => 'EVENTO_CANCELADO',
            ], 422);
        }

        // 3. Duplicado
        $existe = AsignacionEvento::where('empleado_id', $data['empleado_id'])
            ->where('evento_id', $data['evento_id'])
            ->exists();

        if ($existe) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Este empleado ya está asignado a ese evento.',
                'codigo'  => 'DUPLICADO',
            ], 409);
        }

        // 4. Conflicto de horario (sólo si se proporcionan horas)
        if (!empty($data['hora_inicio']) && !empty($data['hora_fin'])) {
            $conflicto = $empleado->tieneConflictoHorario(
                $evento->fecha_evento->toDateString(),
                $data['hora_inicio'],
                $data['hora_fin']
            );

            if ($conflicto) {
                return response()->json([
                    'ok'      => false,
                    'mensaje' => "Conflicto de horario: el empleado ya tiene asignación en ese rango horario el {$evento->fecha_evento->format('d/m/Y')}.",
                    'codigo'  => 'CONFLICTO_HORARIO',
                ], 422);
            }
        }

        // ── Crear asignación ─────────────────────────────────────────────
        $asignacion = AsignacionEvento::create($data);
        $asignacion->load(['empleado', 'rolStaff']);

        return response()->json([
            'ok'      => true,
            'mensaje' => 'Empleado asignado al evento correctamente.',
            'datos'   => $asignacion,
        ], 201);
    }

    // ─── Actualizar estado de asistencia ─────────────────────────────────

    /**
     * PATCH /api/v1/asignaciones/{asignacion}/asistencia
     */
    public function actualizarAsistencia(Request $request, AsignacionEvento $asignacion): JsonResponse
    {
        $request->validate([
            'estado_asistencia' => ['required', Rule::in(AsignacionEvento::ESTADOS)],
            'observaciones'     => ['nullable', 'string', 'max:500'],
        ]);

        $asignacion->update([
            'estado_asistencia' => $request->estado_asistencia,
            'observaciones'     => $request->observaciones ?? $asignacion->observaciones,
        ]);

        return response()->json([
            'ok'      => true,
            'mensaje' => 'Estado de asistencia actualizado.',
            'datos'   => $asignacion->fresh(['empleado', 'rolStaff']),
        ]);
    }

    // ─── Eliminar asignación ─────────────────────────────────────────────

    /**
     * DELETE /api/v1/asignaciones/{asignacion}
     */
    public function destroy(AsignacionEvento $asignacion): JsonResponse
    {
        $asignacion->delete();

        return response()->json([
            'ok'      => true,
            'mensaje' => 'Asignación eliminada correctamente.',
        ]);
    }

    // ─── Calendario ──────────────────────────────────────────────────────

    /**
     * GET /api/v1/asignaciones/calendario?mes=2026-06
     *
     * Devuelve eventos con su staff para renderizar el calendario.
     */
    public function calendario(Request $request): JsonResponse
    {
        $mes = $request->input('mes', now()->format('Y-m'));

        [$year, $month] = explode('-', $mes);

        $eventos = Evento::whereYear('fecha_evento', $year)
            ->whereMonth('fecha_evento', $month)
            ->with([
                'asignaciones.empleado:id,nombre,apellido_paterno',
                'asignaciones.rolStaff:id,nombre,color_hex',
            ])
            ->orderBy('fecha_evento')
            ->get(['id', 'cliente_nombre', 'fecha_evento', 'hora_inicio', 'hora_fin', 'municipio', 'estado']);

        return response()->json([
            'ok'    => true,
            'datos' => [
                'mes'    => $mes,
                'eventos' => $eventos,
            ],
        ]);
    }
}
