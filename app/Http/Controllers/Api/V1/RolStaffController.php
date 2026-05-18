<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RolStaff;
use Illuminate\Http\JsonResponse;

/**
 * Controlador: RolStaffController
 *
 * Catálogo de roles del personal (lectura).
 * Módulo E — Recursos Humanos
 */
class RolStaffController extends Controller
{
    /**
     * GET /api/v1/roles-staff
     *
     * Devuelve todos los roles activos con conteo de empleados.
     */
    public function index(): JsonResponse
    {
        $roles = RolStaff::where('activo', true)
            ->withCount('empleados')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'ok'    => true,
            'datos' => $roles,
        ]);
    }
}
