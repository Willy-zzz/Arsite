<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hito;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\CarbonImmutable;

class HitoController extends BaseApiController
{
    /**
     * Constructor — autorización automática via policies
     */
    public function __construct()
    {
        $this->authorizeResource(Hito::class, 'hito');
    }

    /**
     * Listado paginado con filtros y ordenamiento
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Hito::with('user:id,usu_nombre');

            $this->applyFilters($query, $request);
            $this->applySorting($query, $request);

            $hitos = $query->paginate($request->per_page ?? 15)
                        ->appends($request->query());

            return response()->json([
                'success' => true,
                'data'    => $hitos,
                'message' => 'Hitos obtenidos exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error index hito: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Crear nuevo hito
     */
    public function store(Request $request): JsonResponse
    {
        $validator = $this->validateHito($request, null);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();
        $newImagen = null;

        try {
            $data = $validator->validated();

            // Auto-incremento de orden si no se especifica
            if (empty($data['hit_orden'])) {
                $data['hit_orden'] = (Hito::max('hit_orden') ?? 0) + 1;
            }

            // Subir imagen si existe
            if ($request->hasFile('hit_imagen')) {
                $newImagen         = $this->uploadImagen($request->file('hit_imagen'));
                $data['hit_imagen'] = $newImagen;
            }

            $data['user_id'] = auth()->id();

            $hito = Hito::create($data);
            $hito->load('user:id,usu_nombre');

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => $hito,
                'message' => 'Hito creado exitosamente',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($newImagen) $this->deleteImagen($newImagen);

            Log::error('Error store hito: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Mostrar hito específico
     */
    public function show(Hito $hito): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $hito->load('user:id,usu_nombre'),
            'message' => 'Hito obtenido exitosamente',
        ]);
    }

    /**
     * Actualizar hito existente
     */
    public function update(Request $request, Hito $hito): JsonResponse
    {
        $validator = $this->validateHito($request, $hito);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();
        $newImagen = null;

        try {
            $hito      = Hito::where('hit_id', $hito->hit_id)->lockForUpdate()->first();
            $data      = $validator->safe()->except(['hit_imagen']);
            $oldImagen = $hito->hit_imagen;

            // Campo vacío = no actualizar imagen
            if ($request->has('hit_imagen') && blank($request->hit_imagen)) {
                unset($data['hit_imagen']);
            }

            // Nueva imagen
            if ($request->hasFile('hit_imagen')) {
                $newImagen         = $this->uploadImagen($request->file('hit_imagen'));
                $data['hit_imagen'] = $newImagen;
            }

            $hito->update($data);
            $hito->load('user:id,usu_nombre');

            // Commit ANTES de borrar archivo viejo
            DB::commit();

            if ($newImagen && $oldImagen) {
                $this->deleteImagen($oldImagen);
            }

            return response()->json([
                'success' => true,
                'data'    => $hito,
                'message' => 'Hito actualizado exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($newImagen) $this->deleteImagen($newImagen);

            Log::error('Error update hito: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Eliminar hito
     */
    public function destroy(Hito $hito): JsonResponse
    {
        DB::beginTransaction();
        try {
            $hito      = Hito::where('hit_id', $hito->hit_id)->lockForUpdate()->first();
            $oldImagen = $hito->hit_imagen;

            $hito->delete();

            if ($oldImagen) $this->deleteImagen($oldImagen);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hito eliminado exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error destroy hito: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Hitos públicos para el frontend (timeline)
     * Ordenados por fecha ascendente (cronológico)
     */
    public function publicHitos(Request $request): JsonResponse
    {
        try {
            $query = Hito::where('hit_estatus', 'Publicado')
                        ->orderBy('hit_fecha', 'asc');

            // Filtro por categoría
            if ($request->filled('categoria')) {
                $query->where('hit_categoria', $request->categoria);
            }

            $hitos = $query->get();

            return response()->json([
                'success' => true,
                'data'    => $hitos,
                'message' => 'Hitos públicos obtenidos exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error publicHitos: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Actualizar orden de los hitos (drag & drop)
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $this->authorize('reorder', Hito::class);

        $validator = Validator::make($request->all(), [
            'items'         => 'required|array|min:1',
            'items.*.id'    => 'required|exists:hitos,hit_id',
            'items.*.orden' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        try {
            DB::transaction(function () use ($request) {
                foreach ($request->items as $item) {
                    Hito::where('hit_id', $item['id'])
                        ->update(['hit_orden' => $item['orden']]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Orden actualizado exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error updateOrder hito: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Cambio masivo de estado
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $this->authorize('bulkAction', Hito::class);

        $validator = Validator::make($request->all(), [
            'ids'      => 'required|array|min:1',
            'ids.*'    => 'required|distinct|exists:hitos,hit_id',
            'estatus'  => 'required|in:Publicado,Guardado',
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        try {
            DB::transaction(function () use ($request) {
                Hito::whereIn('hit_id', $request->ids)
                    ->update(['hit_estatus' => $request->estatus]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Estatus actualizado exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulkUpdateStatus hito: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Eliminación masiva
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $this->authorize('bulkAction', Hito::class);

        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|distinct|exists:hitos,hit_id',
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();
        try {
            $hitos = Hito::whereIn('hit_id', $request->ids)->get();

            foreach ($hitos as $hito) {
                if ($hito->hit_imagen) $this->deleteImagen($hito->hit_imagen);
                $hito->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($request->ids) . ' hito(s) eliminado(s) exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulkDelete hito: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Estadísticas del módulo
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewStatistics', Hito::class);

        try {
            $stats = [
                'total'      => Hito::count(),
                'publicados' => Hito::where('hit_estatus', 'Publicado')->count(),
                'guardados'  => Hito::where('hit_estatus', 'Guardado')->count(),
                'este_anio'  => Hito::whereYear('hit_fecha', now()->year)->count(),
                'categorias' => Hito::whereNotNull('hit_categoria')
                                    ->distinct('hit_categoria')
                                    ->count('hit_categoria'),
            ];

            return response()->json([
                'success' => true,
                'data'    => $stats,
                'message' => 'Estadísticas obtenidas exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error statistics hito: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    // ======================================================
    // MÉTODOS PRIVADOS AUXILIARES
    // ======================================================

    private function validateHito(Request $request, ?Hito $hito = null)
    {
        $isUpdate = !is_null($hito);

        $rules = [
            'hit_titulo'      => ($isUpdate ? 'sometimes|' : 'required|') . 'string|max:150',
            'hit_descripcion' => 'sometimes|nullable|string',
            'hit_fecha'       => ($isUpdate ? 'sometimes|' : 'required|date'),
            'hit_imagen'      => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096|dimensions:max_width=4000,max_height=4000',
            'hit_categoria'   => 'sometimes|nullable|string|max:50',
            'hit_orden'       => [
                'nullable', 'integer', 'min:0',
                $isUpdate
                    ? Rule::unique('hitos', 'hit_orden')->ignore($hito?->hit_id, 'hit_id')
                    : Rule::unique('hitos', 'hit_orden'),
            ],
            'hit_estatus'     => ($isUpdate ? 'sometimes|' : 'required|') . 'in:Publicado,Guardado',
        ];

        return Validator::make($request->all(), $rules);
    }

    private function uploadImagen($file): string
    {
        $filename = date('YmdHis') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('hitos', $filename, 'public');
    }

    private function deleteImagen(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function failValidation($validator): JsonResponse
    {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors(),
            'message' => 'Error de validación',
        ], 422);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->boolean('mine')) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('estatus')) {
            $query->where('hit_estatus', $request->estatus);
        }

        if ($request->filled('categoria')) {
            $query->where('hit_categoria', $request->categoria);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) =>
                $q->where('hit_titulo', 'LIKE', "%{$s}%")
                ->orWhere('hit_descripcion', 'LIKE', "%{$s}%")
                ->orWhere('hit_categoria', 'LIKE', "%{$s}%")
            );
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('hit_fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('hit_fecha', '<=', $request->fecha_hasta);
        }
    }

    private function applySorting($query, Request $request): void
    {
        $allowed = ['hit_titulo', 'hit_fecha', 'hit_orden', 'hit_estatus', 'created_at'];

        $sortBy        = in_array($request->sort_by, $allowed) ? $request->sort_by : 'hit_orden';
        $sortDirection = strtolower($request->sort_direction) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDirection);
    }
}