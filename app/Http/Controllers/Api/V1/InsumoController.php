<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Insumo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Controlador API: Insumos (Materia Prima)
 *
 * Gestiona las operaciones CRUD para los insumos a granel
 * utilizados en la producción de snacks de Raspados Yeti.
 *
 * Prefijo de rutas: /api/v1/insumos
 */
class InsumoController extends Controller
{
    /**
     * Listar todos los insumos con paginación.
     *
     * Devuelve una lista paginada de todos los insumos registrados.
     * Por defecto retorna 15 registros por página.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * GET /api/v1/insumos
     * GET /api/v1/insumos?page=2
     * GET /api/v1/insumos?per_page=25
     */
    public function index(Request $request): JsonResponse
    {
        $categoria = $request->input('categoria');
        $search = $request->input('search');
        $sort = $request->input('sort', 'nombre');
        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        $columnasOrdenables = [
            'nombre',
            'unidad_medida',
            'stock_actual',
            'stock_minimo',
            'costo_adquisicion',
            'categoria',
            'id',
        ];

        $query = Insumo::query();

        if (!empty($categoria) && $categoria !== 'Todos') {
            $query->where('categoria', $categoria);
        }

        if (!empty($search)) {
            $query->where(function ($builder) use ($search) {
                if (is_numeric($search)) {
                    $builder->where('id', $search)
                        ->orWhere('nombre', 'like', "%{$search}%");
                } else {
                    $builder->where('nombre', 'like', "%{$search}%");
                }
            });
        }

        if (!in_array($sort, $columnasOrdenables, true)) {
            $sort = 'nombre';
        }

        $perPage = min((int) $request->input('per_page', 15), 1000);

        $insumos = $query
            ->orderByRaw('stock_actual <= stock_minimo DESC')
            ->orderBy($sort, $order)
            ->paginate($perPage)
            ->appends($request->query());

        $categorias = Insumo::query()
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        return response()->json([
            'exito'   => true,
            'mensaje' => 'Lista de insumos obtenida correctamente.',
            'datos'   => $insumos,
            'categorias' => $categorias,
        ]);
    }

    /**
     * Registrar un nuevo insumo.
     *
     * Valida los datos recibidos y crea un nuevo registro de materia prima.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * POST /api/v1/insumos
     * Body: { "nombre": "Jarabe de Fresa", "unidad_medida": "litros", "costo_adquisicion": 50.00 }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $datosValidados = $request->validate([
                'nombre'             => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('insumos')->where(fn ($query) => $query->where('marca', $request->input('marca'))),
                ],
                'unidad_medida'      => 'required|string|max:50',
                'costo_adquisicion'  => 'required|numeric|min:0',
                'stock_actual'       => 'required|numeric|min:0',
                'stock_minimo'       => 'required|numeric|min:0',
                'categoria'          => 'required|string|max:80',
                'marca'              => 'required|string|max:100',
            ]);

            $insumo = Insumo::create($datosValidados);

            return response()->json([
                'exito'   => true,
                'mensaje' => "Insumo '{$insumo->nombre}' creado correctamente.",
                'datos'   => $insumo,
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'exito'   => false,
                'mensaje' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar un insumo específico.
     *
     * Busca un insumo por su ID e incluye los productos que lo utilizan
     * en sus recetas (relación belongsToMany).
     *
     * @param int $id
     * @return JsonResponse
     *
     * GET /api/v1/insumos/{id}
     */
    public function show(int $id): JsonResponse
    {
        $insumo = Insumo::with('productos')->find($id);

        if (!$insumo) {
            return response()->json([
                'exito'   => false,
                'mensaje' => 'Insumo no encontrado.',
            ], 404);
        }

        return response()->json([
            'exito'   => true,
            'mensaje' => 'Insumo encontrado.',
            'datos'   => $insumo,
        ]);
    }

    /**
     * Actualizar un insumo existente.
     *
     * Valida los datos recibidos y actualiza el registro del insumo.
     * Todos los campos son opcionales en la actualización.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     *
     * PUT /api/v1/insumos/{id}
     * Body: { "costo_adquisicion": 55.00 }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $insumo = Insumo::find($id);

        if (!$insumo) {
            return response()->json([
                'exito'   => false,
                'mensaje' => 'Insumo no encontrado.',
            ], 404);
        }

        try {
            $datosValidados = $request->validate([
                'nombre'             => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('insumos')
                        ->where(fn ($query) => $query->where('marca', $request->input('marca')))
                        ->ignore($insumo->id),
                ],
                'unidad_medida'      => 'required|string|max:50',
                'costo_adquisicion'  => 'required|numeric|min:0',
                'stock_actual'       => 'required|numeric|min:0',
                'stock_minimo'       => 'required|numeric|min:0',
                'categoria'          => 'required|string|max:80',
                'marca'              => 'required|string|max:100',
            ]);

            $insumo->update($datosValidados);

            return response()->json([
                'exito'   => true,
                'mensaje' => "Insumo '{$insumo->nombre}' actualizado correctamente.",
                'datos'   => $insumo,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'exito'   => false,
                'mensaje' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un insumo.
     *
     * Elimina el registro del insumo. Gracias al onDelete('cascade')
     * en la tabla pivote, las recetas asociadas se eliminan automáticamente.
     *
     * @param int $id
     * @return JsonResponse
     *
     * DELETE /api/v1/insumos/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $insumo = Insumo::with('productos')->find($id);

        if (!$insumo) {
            return response()->json([
                'exito'   => false,
                'mensaje' => 'Insumo no encontrado.',
            ], 404);
        }

        $tieneRelaciones = $insumo->productos()->exists();
        $forzarEliminacion = $request->boolean('force');

        if ($tieneRelaciones && !$forzarEliminacion) {
            $productos = $insumo->productos()->pluck('nombre')->all();
            $listaProductos = implode(', ', $productos);

            return response()->json([
                'exito'   => false,
                'mensaje' => "No se puede eliminar. Este insumo es ingrediente de: {$listaProductos}.",
                'productos' => $productos,
            ], 409);
        }

        if ($tieneRelaciones && $forzarEliminacion) {
            $insumo->productos()->detach();
        }

        $nombreInsumo = $insumo->nombre;
        $insumo->delete();

        return response()->json([
            'exito'   => true,
            'mensaje' => "Insumo '{$nombreInsumo}' eliminado correctamente.",
        ]);
    }
}
