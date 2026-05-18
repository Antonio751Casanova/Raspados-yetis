<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEmpleadoRequest;
use App\Http\Requests\Api\V1\UpdateEmpleadoRequest;
use App\Models\Empleado;
use App\Models\AsignacionEvento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Controlador: EmpleadoController
 *
 * CRUD completo de empleados + endpoints auxiliares para
 * consultar disponibilidad e historial de participación.
 * Módulo E — Recursos Humanos
 */
class EmpleadoController extends Controller
{
    // ─── Listar ──────────────────────────────────────────────────────────

    /**
     * GET /api/v1/empleados
     *
     * Parámetros opcionales:
     *   ?estado=activo|inactivo
     *   ?buscar=Carlos
     *   ?rol=1
     *   ?per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $query = Empleado::with('roles')
            ->when($request->estado, fn($q, $e) => $q->where('estado', $e))
            ->when($request->buscar, function ($q, $buscar) {
                $q->where(function ($inner) use ($buscar) {
                    $inner->where('nombre', 'like', "%{$buscar}%")
                          ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                          ->orWhere('correo', 'like', "%{$buscar}%");
                });
            })
            ->when($request->rol, function ($q, $rolId) {
                $q->whereHas('roles', fn($r) => $r->where('roles_staff.id', $rolId));
            })
            ->orderByDesc('created_at')
            ->orderBy('nombre');

        $empleados = $query->paginate($request->input('per_page', 20));

        // Añadir atributos virtuales
        $empleados->getCollection()->transform(function ($empleado) {
            $empleado->nombre_completo = $empleado->nombre_completo;
            $empleado->foto_url        = $empleado->foto_url;
            return $empleado;
        });

        return response()->json([
            'ok'    => true,
            'datos' => $empleados,
        ]);
    }

    // ─── Crear ───────────────────────────────────────────────────────────

    /**
     * POST /api/v1/empleados
     */
    public function store(StoreEmpleadoRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Manejo de fotografía
        if ($request->hasFile('fotografia')) {
            $data['fotografia'] = $request->file('fotografia')
                ->store('empleados/fotos', 'public');
        }

        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        $empleado = DB::transaction(function () use ($data, $roles) {
            $empleado = Empleado::create($data);

            if (!empty($roles)) {
                $sync = [];
                foreach ($roles as $index => $rolId) {
                    $sync[$rolId] = ['es_rol_principal' => $index === 0];
                }
                $empleado->roles()->sync($sync);
            }

            return $empleado;
        });

        $empleado->load('roles');
        $empleado->nombre_completo = $empleado->nombre_completo;
        $empleado->foto_url        = $empleado->foto_url;

        return response()->json([
            'ok'      => true,
            'mensaje' => 'Empleado registrado correctamente.',
            'datos'   => $empleado,
        ], 201);
    }

    // ─── Detalle ─────────────────────────────────────────────────────────

    /**
     * GET /api/v1/empleados/{empleado}
     */
    public function show(Empleado $empleado): JsonResponse
    {
        $empleado->load(['roles', 'asignaciones.evento', 'asignaciones.rolStaff']);
        $empleado->nombre_completo = $empleado->nombre_completo;
        $empleado->foto_url        = $empleado->foto_url;

        return response()->json([
            'ok'    => true,
            'datos' => $empleado,
        ]);
    }

    // ─── Actualizar ──────────────────────────────────────────────────────

    /**
     * PUT /api/v1/empleados/{empleado}
     */
    public function update(UpdateEmpleadoRequest $request, Empleado $empleado): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('fotografia')) {
            // Eliminar foto anterior
            if ($empleado->fotografia) {
                Storage::disk('public')->delete($empleado->fotografia);
            }
            $data['fotografia'] = $request->file('fotografia')
                ->store('empleados/fotos', 'public');
        }

        $roles = $data['roles'] ?? null;
        unset($data['roles']);

        DB::transaction(function () use ($empleado, $data, $roles) {
            $empleado->update($data);

            if ($roles !== null) {
                $sync = [];
                foreach ($roles as $index => $rolId) {
                    $sync[$rolId] = ['es_rol_principal' => $index === 0];
                }
                $empleado->roles()->sync($sync);
            }
        });

        $empleado->load('roles');
        $empleado->nombre_completo = $empleado->nombre_completo;
        $empleado->foto_url        = $empleado->foto_url;

        return response()->json([
            'ok'      => true,
            'mensaje' => 'Empleado actualizado correctamente.',
            'datos'   => $empleado,
        ]);
    }

    // ─── Eliminar ────────────────────────────────────────────────────────

    /**
     * DELETE /api/v1/empleados/{empleado}
     * (Soft delete — el registro no se borra físicamente)
     */
    public function destroy(Empleado $empleado): JsonResponse
    {
        $empleado->delete();

        return response()->json([
            'ok'      => true,
            'mensaje' => 'Empleado eliminado correctamente.',
        ]);
    }

    // ─── Endpoints auxiliares ─────────────────────────────────────────────

    /**
     * GET /api/v1/empleados/{empleado}/historial
     *
     * Historial de participaciones en eventos.
     */
    public function historial(Empleado $empleado): JsonResponse
    {
        $asignaciones = $empleado->asignaciones()
            ->with('evento:id,cliente_nombre,fecha_evento,hora_inicio,hora_fin,municipio,estado')
            ->with('rolStaff:id,nombre,color_hex')
            ->orderByDesc('created_at')
            ->get();

        $totalAsistencias = $asignaciones->where('estado_asistencia', AsignacionEvento::ESTADO_ASISTIO)->count();
        $totalFaltas      = $asignaciones->where('estado_asistencia', AsignacionEvento::ESTADO_FALTO)->count();

        return response()->json([
            'ok'    => true,
            'datos' => [
                'empleado'         => $empleado->only(['id', 'nombre_completo', 'foto_url']),
                'asignaciones'     => $asignaciones,
                'total_asistencias' => $totalAsistencias,
                'total_faltas'     => $totalFaltas,
                'tasa_asistencia'  => $asignaciones->count() > 0
                    ? round(($totalAsistencias / $asignaciones->count()) * 100, 1)
                    : null,
            ],
        ]);
    }

    /**
     * GET /api/v1/empleados/{empleado}/disponibilidad?fecha=2026-06-15&hora_inicio=10:00&hora_fin=16:00
     *
     * Verifica si el empleado está disponible en el rango horario dado.
     */
    public function disponibilidad(Request $request, Empleado $empleado): JsonResponse
    {
        $request->validate([
            'fecha'      => ['required', 'date'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin'   => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ]);

        if (!$empleado->estaActivo()) {
            return response()->json([
                'ok'           => true,
                'disponible'   => false,
                'razon'        => 'El empleado está inactivo.',
                'conflictos'   => [],
            ]);
        }

        $conflictos = $empleado->asignaciones()
            ->whereHas('evento', fn($q) => $q->where('fecha_evento', $request->fecha))
            ->where('hora_inicio', '<', $request->hora_fin)
            ->where('hora_fin',    '>', $request->hora_inicio)
            ->with('evento:id,cliente_nombre,fecha_evento,hora_inicio,hora_fin')
            ->get();

        return response()->json([
            'ok'         => true,
            'disponible' => $conflictos->isEmpty(),
            'razon'      => $conflictos->isNotEmpty() ? 'Conflicto de horario detectado.' : null,
            'conflictos' => $conflictos,
        ]);
    }

    /**
     * GET /api/v1/empleados/dashboard
     *
     * Métricas para el panel de Recursos Humanos.
     */
    public function dashboard(): JsonResponse
    {
        $totalActivos   = Empleado::where('estado', 'activo')->count();
        $totalInactivos = Empleado::where('estado', 'inactivo')->count();

        // Próximos eventos con asignaciones (hoy + 7 días)
        $hoy         = now()->toDateString();
        $proximos    = \App\Models\Evento::whereBetween('fecha_evento', [$hoy, now()->addDays(7)->toDateString()])
            ->withCount('asignaciones')
            ->orderBy('fecha_evento')
            ->limit(5)
            ->get(['id', 'cliente_nombre', 'fecha_evento', 'hora_inicio', 'municipio', 'estado']);

        // Staff asignado hoy
        $staffHoy = AsignacionEvento::whereHas('evento', fn($q) => $q->where('fecha_evento', $hoy))
            ->count();

        // Top 5 empleados con más participaciones (asistió)
        $topEmpleados = Empleado::withCount([
            'asignaciones as total_participaciones' => fn($q) =>
                $q->where('estado_asistencia', AsignacionEvento::ESTADO_ASISTIO),
        ])
            ->orderByDesc('total_participaciones')
            ->limit(5)
            ->get(['id', 'nombre', 'apellido_paterno', 'puesto']);

        // Eventos próximos sin staff suficiente (menos de 2 asignaciones)
        $alertas = \App\Models\Evento::whereBetween('fecha_evento', [$hoy, now()->addDays(14)->toDateString()])
            ->whereNotIn('estado', ['Cancelado', 'Completado'])
            ->withCount('asignaciones')
            ->having('asignaciones_count', '<', 2)
            ->orderBy('fecha_evento')
            ->get(['id', 'cliente_nombre', 'fecha_evento', 'municipio']);

        return response()->json([
            'ok'    => true,
            'datos' => [
                'total_activos'     => $totalActivos,
                'total_inactivos'   => $totalInactivos,
                'staff_hoy'         => $staffHoy,
                'proximos_eventos'  => $proximos,
                'top_empleados'     => $topEmpleados,
                'alertas_personal'  => $alertas,
            ],
        ]);
    }
}
