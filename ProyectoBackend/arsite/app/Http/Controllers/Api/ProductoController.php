<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\Exportable;

class ProductoController extends BaseApiController
{
    use Exportable;

    /**
     * Constructor - Autorizar con policies
     */
    public function __construct()
    {
        //Autoriza automáticamente con policies
        $this->authorizeResource(Producto::class, 'producto');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Eager loading de usuario para evitar N+1
            $query = Producto::with('user:id,usu_nombre');

            // Aplicar filtros y ordenamiento
            $this->applyFilters($query, $request);
            $this->applySorting($query, $request);

            // Paginar y preservar query params
            $productos = $query->paginate($request->per_page ?? 15)
                               ->appends($request->query());

            return response()->json([
                'success' => true,
                'data'    => $productos,
                'message' => 'Productos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error index producto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Validación (pasamos null porque no hay producto existente)
        $validator = $this->validateProducto($request, null);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        //Inicio de transacción: Todo o nada
        DB::beginTransaction();
        $newImage = null; // Para cleanup si falla

        try {
            $data = $validator->validated();

            // AUTO-INCREMENTO DE ORDEN: Si no se especifica pro_orden, asignarlo automáticamente
            if (empty($data['pro_orden']) || $data['pro_orden'] === 0) {
                // Obtener el máximo orden actual y sumarle 1
                $maxOrden = Producto::max('pro_orden') ?? 0;
                $data['pro_orden'] = $maxOrden + 1;
            }

            // Subir imagen si existe
            if ($request->hasFile('pro_imagen')) {
                $newImage = $this->uploadImage($request->file('pro_imagen'));
                $data['pro_imagen'] = $newImage;
            }

            // Asignar usuario autenticado
            $data['user_id'] = auth()->id();

            // Crear registro y cargar relación
            $producto = Producto::create($data);
            $producto->load('user:id,usu_nombre');

            //Confirmar transacción
            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => $producto,
                'message' => 'Producto creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            //Si falla algo, revertimos BD
            DB::rollBack();

            // IMPORTANTE: Si se subió imagen pero falló el insert en BD,
            // borramos el archivo físico para no dejar basura
            if ($newImage) $this->deleteImage($newImage);

            Log::error('Error store producto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Producto $producto): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $producto->load('user:id,usu_nombre'),
            'message' => 'Producto obtenido exitosamente'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Producto $producto): JsonResponse
    {
        // Validación dinámica (pasamos el modelo para contexto)
        $validator = $this->validateProducto($request, $producto);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();
        $newImage = null;

        try {
            // Lock FOR UPDATE (bloqueo pesimista): previene modificaciones concurrentes
            $producto = Producto::where('pro_id', $producto->pro_id)->lockForUpdate()->first();

            // Excluir imagen del validated para manejo manual
            $data     = $validator->safe()->except(['pro_imagen']);
            $oldImage = $producto->pro_imagen;

            // Limpieza de campo imagen:
            // Si el frontend envía campo vacío, no actualizar imagen
            // blank() considera: null, '', '   ' como vacío
            if ($request->has('pro_imagen') && blank($request->pro_imagen)) {
                unset($data['pro_imagen']);
            }

            // Subir nueva imagen si existe
            if ($request->hasFile('pro_imagen')) {
                $newImage = $this->uploadImage($request->file('pro_imagen'));
                $data['pro_imagen'] = $newImage;
            }

            // Actualizar y recargar relación
            $producto->update($data);
            $producto->load('user:id,usu_nombre');

            // CRÍTICO: Commit ANTES de borrar archivos
            DB::commit();

            // Ahora es seguro borrar imagen vieja
            if ($newImage && $oldImage) {
                $this->deleteImage($oldImage);
            }

            return response()->json([
                'success' => true,
                'data'    => $producto,
                'message' => 'Producto actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // Si falló, borramos la imagen NUEVA que acabamos de subir.
            if ($newImage) $this->deleteImage($newImage);

            Log::error('Error update producto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Producto $producto): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Lock del registro para asegurar exclusividad durante el borrado
            $producto = Producto::where('pro_id', $producto->pro_id)->lockForUpdate()->first();
            $oldImage = $producto->pro_imagen;

            // Eliminar registro de BD
            $producto->delete();

            // Eliminar archivo físico
            if ($oldImage) $this->deleteImage($oldImage);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error destroy producto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Get productos for public display (published only)
     */
    public function publicProductos(Request $request): JsonResponse
    {
        try {
            // Ordenamiento consistente con index()
            $allowed = ['pro_nombre', 'pro_orden', 'created_at'];
            $sortBy  = in_array($request->sort_by, $allowed) ? $request->sort_by : 'pro_orden';
            $sortDir = strtolower($request->sort_direction) === 'desc' ? 'desc' : 'asc';

            $productos = Producto::where('pro_estatus', 'Publicado')
                ->orderBy($sortBy, $sortDir)
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $productos,
                'message' => 'Productos públicos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error publicProductos: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Get productos statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        // Verificar permiso para ver estadísticas
        $this->authorize('viewStatistics', Producto::class);

        try {
            $stats = [
                'total'      => Producto::count(),
                'publicados' => Producto::where('pro_estatus', 'Publicado')->count(),
                'guardados'  => Producto::where('pro_estatus', 'Guardado')->count(),
            ];

            return response()->json([
                'success' => true,
                'data'    => $stats,
                'message' => 'Estadísticas de productos obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error statistics producto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Update productos order
     */
    public function updateOrder(Request $request): JsonResponse
    {
        //Verificar permiso para reordenar
        $this->authorize('reorder', Producto::class);

        $validator = Validator::make($request->all(), [
            'productos'         => 'required|array|min:1',
            'productos.*.id'    => 'required|exists:productos,pro_id|distinct',
            'productos.*.orden' => 'required|integer|min:0'
        ], [
            'productos.required'      => 'Debe proporcionar al menos un producto',
            'productos.*.id.distinct' => 'No puede enviar el mismo producto dos veces',
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        try {
            DB::transaction(function () use ($request) {
                // Extraer IDs para bloqueo preventivo
                $ids = collect($request->productos)->pluck('id')->toArray();

                // Lock pesimista de todos los productos a actualizar
                Producto::whereIn('pro_id', $ids)->lockForUpdate()->get();

                foreach ($request->productos as $item) {
                    Producto::where('pro_id', $item['id'])
                        ->update(['pro_orden' => $item['orden']]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Orden de productos actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updateOrder producto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Bulk delete productos (funcionalidad adicional)
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        // Verificar permiso para operaciones en lote
        $this->authorize('bulkAction', Producto::class);

        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|exists:productos,pro_id|distinct'
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();
        try {
            // Lock de todos los registros a eliminar
            $productos = Producto::whereIn('pro_id', $request->ids)
                ->lockForUpdate()
                ->get();

            // Recolectar imágenes (filter elimina nulls)
            $imagePaths = $productos->pluck('pro_imagen')->filter()->toArray();

            // Eliminar registros de BD
            Producto::whereIn('pro_id', $request->ids)->delete();

            // Eliminar archivos físicos
            foreach ($imagePaths as $path) {
                $this->deleteImage($path);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => ['deleted_count' => count($request->ids)],
                'message' => count($request->ids) . ' productos eliminados exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulkDelete producto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Export productos to Excel or PDF
     */
    public function export(Request $request)
    {
        $this->authorize('export', Producto::class);

        $query = Producto::with('user:id,usu_nombre');
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);
        $productos = $query->get();

        $headings = ['ID', 'Nombre', 'Estado', 'Orden', 'Creado por', 'Fecha Creación'];

        $mapping = function ($producto) {
            // Usar badges HTML para estados
            $statusBadge = $producto->pro_estatus === 'Publicado'
                ? '<span class="badge badge-success">Publicado</span>'
                : '<span class="badge badge-warning">Guardado</span>';

            return [
                $producto->pro_id,
                $producto->pro_nombre,
                $statusBadge,
                $producto->pro_orden ?? '—',
                $producto->user?->usu_nombre ?? '—',
                $producto->created_at?->format('d/m/Y') ?? 'N/A',
            ];
        };

        $columnWidths = [
            'A' => 8, 'B' => 35, 'C' => 14, 'D' => 10, 'E' => 25, 'F' => 18,
        ];

        // Filtros activos
        $activeFilters = [];
        if ($request->filled('estatus')) {
            $activeFilters[] = "Estado: {$request->estatus}";
        }
        if ($request->filled('search')) {
            $activeFilters[] = "Búsqueda: {$request->search}";
        }
        if ($request->boolean('mine')) {
            $activeFilters[] = "Solo mis productos";
        }

        return $this->exportData(
            $request,
            $productos,
            $headings,
            $mapping,
            'productos',
            'Reporte de Productos',
            $columnWidths,
        );
    }

    // ============= MÉTODOS PRIVADOS AUXILIARES ============= //

    /**
     * Validación dinámica de producto
     *
     * Lógica inteligente:
     * - En CREATE: nombre único requerido, imagen requerida, estatus requerido
     * - En UPDATE: campos opcionales, nombre único ignorando registro actual
     *
     * @param Request $request
     * @param Producto|null $producto - Si es update, se pasa el modelo
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validateProducto(Request $request, ?Producto $producto = null)
    {
        $isUpdate = !is_null($producto);

        $rules = [
            // Nombre: único (ignorando el registro actual en updates)
            'pro_nombre' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
                Rule::unique('productos', 'pro_nombre')->ignore($producto?->pro_id, 'pro_id')
            ],

            // Imagen: requerida en create, opcional en update
            'pro_imagen' => ($isUpdate ? 'nullable|sometimes|' : 'required|') .
                'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048|dimensions:max_width=3000,max_height=3000',

            // Orden: opcional, único (ignorando registro actual en updates)
            'pro_orden' => $isUpdate
                ? ['nullable', 'integer', 'min:0',
                    Rule::unique('productos', 'pro_orden')->ignore($producto?->pro_id, 'pro_id')]
                : 'nullable|integer|min:0|unique:productos,pro_orden',

            // Estatus
            'pro_estatus' => ($isUpdate ? 'sometimes|' : 'required|') . 'in:Publicado,Guardado',
        ];

        return Validator::make($request->all(), $rules);
    }

    /**
     * Sube imagen del producto al storage
     *
     * Nomenclatura: YmdHis_uniqid.ext
     * Ejemplo: 20250124153045_abc123.jpg
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string - Path relativo en storage/app/public/productos/
     */
    private function uploadImage($file): string
    {
        // Generar nombre único basado en fecha y uniqid
        $filename = date('YmdHis') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('productos', $filename, 'public');
    }

    /**
     * Elimina imagen del storage
     *
     * Verifica existencia antes de borrar
     *
     * @param string|null $path
     * @return void
     */
    private function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Respuesta estandarizada de error de validación
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return JsonResponse - Status 422
     */
    private function failValidation($validator): JsonResponse
    {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors(),
            'message' => 'Error de validación'
        ], 422);
    }

    /**
     * Aplica filtros dinámicos a la query
     *
     * Filtros soportados:
     * - mine: solo mis productos
     * - estatus: Publicado/Guardado
     * - search: búsqueda en nombre
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    private function applyFilters($query, Request $request): void
    {
        // Filtro: solo mis productos
        if ($request->boolean('mine')) {
            $query->where('user_id', auth()->id());
        }

        // Filtro: por estatus
        if ($request->filled('estatus')) {
            $query->where('pro_estatus', $request->estatus);
        }

        // Filtro: búsqueda por nombre
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('pro_nombre', 'LIKE', "%{$s}%");
        }
    }

    /**
     * Aplica ordenamiento dinámico a la query
     *
     * Solo permite ordenar por campos en whitelist
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    private function applySorting($query, Request $request): void
    {
        // Whitelist de campos ordenables (seguridad)
        $allowed = ['pro_nombre', 'pro_orden', 'pro_estatus', 'created_at'];

        // Validar campo solicitado, default: pro_orden
        $sortBy = in_array($request->sort_by, $allowed) ? $request->sort_by : 'pro_orden';

        // Validar dirección, default: asc
        $sortDirection = strtolower($request->sort_direction) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDirection);
    }
}