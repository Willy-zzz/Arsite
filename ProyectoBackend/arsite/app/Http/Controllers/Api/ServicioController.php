<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Servicio;
use App\Support\ApiAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\CarbonImmutable;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\Exportable;

class ServicioController extends BaseApiController
{
    use Exportable;

    /**
     * Constructor - Autorizar con policies
     */
    public function __construct()
    {
        //Autoriza automáticamente con policies
        $this->authorizeResource(Servicio::class, 'servicio', [
            'except' => ['publicServicios'],
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Eager loading de usuario para evitar N+1
            $query = Servicio::with('user:id,usu_nombre');

            // Aplicar filtros y ordenamiento
            $this->applyFilters($query, $request);
            $this->applySorting($query, $request);

            // Paginar y preservar query params
            $servicios = $query->paginate($request->per_page ?? 15)
                               ->appends($request->query());

            return response()->json([
                'success' => true,
                'data'    => $servicios,
                'message' => 'Servicios obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error index servicio: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Validación (pasamos null porque no hay servicio existente)
        $validator = $this->validateServicio($request, null);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        //Inicio de transacción: Todo o nada
        DB::beginTransaction();
        $newImage = null; // Para cleanup si falla

        try {
            $data = $validator->validated();

            // AUTO-INCREMENTO DE ORDEN: Si no se especifica ser_orden, asignarlo automáticamente
            if (empty($data['ser_orden']) || $data['ser_orden'] === 0) {
                // Obtener el máximo orden actual y sumarle 1
                $maxOrden = Servicio::max('ser_orden') ?? 0;
                $data['ser_orden'] = $maxOrden + 1;
            }

            // Subir imagen si existe
            if ($request->hasFile('ser_imagen')) {
                $newImage = $this->uploadImage($request->file('ser_imagen'));
                $data['ser_imagen'] = $newImage;
            }

            // Asignar usuario autenticado
            $data['user_id'] = auth()->id();

            // Crear registro y cargar relación
            $servicio = Servicio::create($data);
            $servicio->load('user:id,usu_nombre');

            //Confirmar transacción
            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => $servicio,
                'message' => 'Servicio creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            //Si falla algo, revertimos BD
            DB::rollBack();

            // IMPORTANTE: Si se subió imagen pero falló el insert en BD,
            // borramos el archivo físico para no dejar basura
            if ($newImage) $this->deleteImage($newImage);

            Log::error('Error store servicio: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Servicio $servicio): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $servicio->load('user:id,usu_nombre'),
            'message' => 'Servicio obtenido exitosamente'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Servicio $servicio): JsonResponse
    {
        // Validación dinámica (pasamos el modelo para contexto)
        $validator = $this->validateServicio($request, $servicio);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();
        $newImage = null;

        try {
            // Lock FOR UPDATE (bloqueo pesimista): previene modificaciones concurrentes
            $servicio = Servicio::where('ser_id', $servicio->ser_id)->lockForUpdate()->first();

            // Excluir imagen del validated para manejo manual
            $data     = $validator->safe()->except(['ser_imagen']);
            $oldImage = $servicio->ser_imagen;

            // Limpieza de campo imagen:
            // Si el frontend envía campo vacío, no actualizar imagen
            // blank() considera: null, '', '   ' como vacío
            if ($request->has('ser_imagen') && blank($request->ser_imagen)) {
                unset($data['ser_imagen']);
            }

            // Subir nueva imagen si existe
            if ($request->hasFile('ser_imagen')) {
                $newImage = $this->uploadImage($request->file('ser_imagen'));
                $data['ser_imagen'] = $newImage;
            }

            // Actualizar y recargar relación
            $servicio->update($data);
            $servicio->load('user:id,usu_nombre');

            // CRÍTICO: Commit ANTES de borrar archivos
            DB::commit();

            // Ahora es seguro borrar imagen vieja
            if ($newImage && $oldImage) {
                $this->deleteImage($oldImage);
            }

            return response()->json([
                'success' => true,
                'data'    => $servicio,
                'message' => 'Servicio actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // Si falló, borramos la imagen NUEVA que acabamos de subir.
            if ($newImage) $this->deleteImage($newImage);

            Log::error('Error update servicio: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Servicio $servicio): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Lock del registro para asegurar exclusividad durante el borrado
            $servicio = Servicio::where('ser_id', $servicio->ser_id)->lockForUpdate()->first();
            $oldImage = $servicio->ser_imagen;

            // Eliminar registro de BD
            $servicio->delete();

            // Eliminar archivo físico
            if ($oldImage) $this->deleteImage($oldImage);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Servicio eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error destroy servicio: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Get servicios for public display (published and within date range)
     */
    public function publicServicios(Request $request): JsonResponse
    {
        try {
            $now = CarbonImmutable::now();

            // Query builder con closures para condiciones OR
            $query = Servicio::where('ser_estatus', 'Publicado')
                ->where(fn($q) =>
                    // Mostrar si: no tiene fecha de publicación O ya se publicó
                    $q->whereNull('ser_fecha_publicacion')
                    ->orWhere('ser_fecha_publicacion', '<=', $now)
                )
                ->where(fn($q) =>
                    // Mostrar si: no tiene fecha de terminación O aún no termina
                    $q->whereNull('ser_fecha_terminacion')
                    ->orWhere('ser_fecha_terminacion', '>=', $now)
                );

            // Ordenamiento consistente con index()
            $allowed = ['ser_orden', 'ser_titulo', 'ser_fecha_publicacion', 'created_at'];
            $sortBy  = in_array($request->sort_by, $allowed) ? $request->sort_by : 'ser_orden';
            $sortDir = strtolower($request->sort_direction) === 'desc' ? 'desc' : 'asc';

            $servicios = $query->orderBy($sortBy, $sortDir)->get();

            return response()->json([
                'success' => true,
                'data'    => $servicios,
                'message' => 'Servicios públicos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error publicServicios: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Get servicios statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        // Verificar permiso para ver estadísticas
        $this->authorize('viewStatistics', Servicio::class);

        try {
            $now = CarbonImmutable::now();

            $stats = [
                'total'      => Servicio::count(),
                'publicados' => Servicio::where('ser_estatus', 'Publicado')->count(),
                'guardados'  => Servicio::where('ser_estatus', 'Guardado')->count(),
                'activos'    => Servicio::where('ser_estatus', 'Publicado')
                    ->where(fn($q) =>
                        $q->whereNull('ser_fecha_terminacion')
                        ->orWhere('ser_fecha_terminacion', '>=', $now)
                    )
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data'    => $stats,
                'message' => 'Estadísticas de servicios obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error statistics servicio: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Update servicios order
     */
    public function updateOrder(Request $request): JsonResponse
    {
        //Verificar permiso para reordenar
        $this->authorize('reorder', Servicio::class);

        $validator = Validator::make($request->all(), [
            'servicios'         => 'required|array|min:1',
            'servicios.*.id'    => 'required|exists:servicios,ser_id|distinct',
            'servicios.*.orden' => 'required|integer|min:0'
        ], [
            'servicios.required'      => 'Debe proporcionar al menos un servicio',
            'servicios.*.id.distinct' => 'No puede enviar el mismo servicio dos veces',
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        try {
            DB::transaction(function () use ($request) {
                // Extraer IDs para bloqueo preventivo
                $ids = collect($request->servicios)->pluck('id')->toArray();

                // Lock pesimista de todos los servicios a actualizar
                Servicio::whereIn('ser_id', $ids)->lockForUpdate()->get();

                foreach ($request->servicios as $item) {
                    Servicio::where('ser_id', $item['id'])
                        ->update(['ser_orden' => $item['orden']]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Orden de servicios actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updateOrder servicio: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Bulk delete servicios (funcionalidad adicional)
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        // Verificar permiso para operaciones en lote
        $this->authorize('bulkAction', Servicio::class);

        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|exists:servicios,ser_id|distinct'
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();
        try {
            // Lock de todos los registros a eliminar
            $servicios = Servicio::whereIn('ser_id', $request->ids)
                ->lockForUpdate()
                ->get();

            // Recolectar imágenes (filter elimina nulls)
            $imagePaths = $servicios->pluck('ser_imagen')->filter()->toArray();

            // Eliminar registros de BD
            Servicio::whereIn('ser_id', $request->ids)->delete();

            // Eliminar archivos físicos
            foreach ($imagePaths as $path) {
                $this->deleteImage($path);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => ['deleted_count' => count($request->ids)],
                'message' => count($request->ids) . ' servicios eliminados exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulkDelete servicio: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Export servicios to Excel or PDF
     */
    public function export(Request $request)
    {
        $this->authorize('export', Servicio::class);

        $query = Servicio::with('user:id,usu_nombre');
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);
        $servicios = $query->get();

        $headings = ['ID', 'Título', 'Descripción', 'Estado', 'Publicación', 'Terminación', 'Orden'];

        $mapping = function ($servicio) {
            // Usar badges HTML para estados
            $statusBadge = $servicio->ser_estatus === 'Publicado'
                ? '<span class="badge badge-success">Publicado</span>'
                : '<span class="badge badge-warning">Guardado</span>';

            return [
                $servicio->ser_id,
                $servicio->ser_titulo,
                $servicio->ser_descripcion,
                $statusBadge,
                $servicio->ser_fecha_publicacion ? $servicio->ser_fecha_publicacion->format('d/m/Y') : 'N/A',
                $servicio->ser_fecha_terminacion ? $servicio->ser_fecha_terminacion->format('d/m/Y') : 'N/A',
                $servicio->ser_orden,
            ];
        };

        $columnWidths = [
            'A' => 8, 'B' => 30, 'C' => 50, 'D' => 12, 'E' => 18, 'F' => 18, 'G' => 10,
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
            $activeFilters[] = "Solo mis servicios";
        }

        return $this->exportData(
            $request,
            $servicios,
            $headings,
            $mapping,
            'servicios',
            'Reporte de Servicios',
            $columnWidths,
        );
    }

    // ============= MÉTODOS PRIVADOS AUXILIARES ============= //

    /**
     * Validación dinámica de servicio
     *
     * Lógica inteligente:
     * - En CREATE: título único requerido, imagen requerida, estatus requerido
     * - En UPDATE: campos opcionales, título único ignorando registro actual
     * - Fecha terminación: valida contra fecha publicación (request o BD)
     *
     * @param Request $request
     * @param Servicio|null $servicio - Si es update, se pasa el modelo
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validateServicio(Request $request, ?Servicio $servicio = null)
    {
        $isUpdate = !is_null($servicio);

        $rules = [
            // Título: único (ignorando el registro actual en updates)
            'ser_titulo' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:100',
                Rule::unique('servicios', 'ser_titulo')->ignore($servicio?->ser_id, 'ser_id')
            ],

            // Descripción
            'ser_descripcion' => ($isUpdate ? 'sometimes|' : 'required|') . 'string',

            // Imagen: requerida en create, opcional en update
            'ser_imagen' => ($isUpdate ? 'nullable|sometimes|' : 'required|') .
                'image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',

            // Orden: opcional, entero >= 0
            'ser_orden' => 'nullable|integer|min:0',

            // Fechas
            'ser_fecha_publicacion' => 'nullable|date',
            'ser_fecha_terminacion' => 'nullable|date',

            // Estatus
            'ser_estatus' => ($isUpdate ? 'sometimes|' : 'required|') . 'in:Publicado,Guardado',
        ];

        // En CREATE, fecha publicación debe ser >= hoy
        if (!$isUpdate) {
            $rules['ser_fecha_publicacion'] .= '|after_or_equal:today';
        }

        // Validación con closure after() para lógica compleja de fechas
        return ApiAuditLogger::auditValidation(Validator::make($request->all(), $rules, [
            'ser_imagen.required' => 'La imagen del servicio es obligatoria.',
            'ser_imagen.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'ser_imagen.mimes' => 'La imagen debe estar en formato JPG, PNG, GIF, SVG o WEBP.',
            'ser_imagen.max' => 'La imagen no puede superar los 5 MB.',
            'ser_imagen.uploaded' => 'La imagen no se pudo subir al servidor. Verifica su tamaño e inténtalo nuevamente.',
        ])
            ->after(function ($validator) use ($request, $servicio, $isUpdate) {

                // CASO 1: Ambas fechas vienen en el request
                // Validar que terminación >= publicación
                if ($request->filled('ser_fecha_publicacion') &&
                    $request->filled('ser_fecha_terminacion')) {

                    $fechaInicio = Carbon::parse($request->ser_fecha_publicacion);
                    $fechaFin    = Carbon::parse($request->ser_fecha_terminacion);

                    if ($fechaFin->lt($fechaInicio)) {
                        $validator->errors()->add(
                            'ser_fecha_terminacion',
                            'La fecha de terminación debe ser mayor o igual a la fecha de publicación.'
                        );
                    }
                }

                // CASO 2: UPDATE con fecha terminación nueva pero conserva fecha publicación de BD
                // Validar que nueva terminación >= publicación existente
                if ($isUpdate &&
                    !$request->filled('ser_fecha_publicacion') &&
                    $servicio->ser_fecha_publicacion &&
                    $request->filled('ser_fecha_terminacion')) {

                    $fechaInicio = Carbon::parse($servicio->ser_fecha_publicacion);
                    $fechaFin    = Carbon::parse($request->ser_fecha_terminacion);

                    if ($fechaFin->lt($fechaInicio)) {
                        $validator->errors()->add(
                            'ser_fecha_terminacion',
                            'La fecha de terminación debe ser mayor o igual a la fecha de publicación actual (' .
                            $servicio->ser_fecha_publicacion . ').'
                        );
                    }
                }
            }),
            $request,
            'servicios.validation.failed',
            ['module' => 'servicios']
        );
    }

    /**
     * Sube imagen del servicio al storage
     *
     * Nomenclatura: YmdHis_uniqid.ext
     * Ejemplo: 20250124153045_abc123.jpg
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string - Path relativo en storage/app/public/servicios/
     */
    private function uploadImage($file): string
    {
        // Generar nombre único basado en fecha y uniqid
        $filename = date('YmdHis') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('servicios', $filename, 'public');
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
     * - mine: solo mis servicios
     * - estatus: Publicado/Guardado
     * - search: búsqueda en título o descripción
     * - fecha_desde: fecha publicación >= valor
     * - fecha_hasta: fecha terminación <= valor (fin del día)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    private function applyFilters($query, Request $request): void
    {
        // Filtro: solo mis servicios
        if ($request->boolean('mine')) {
            $query->where('user_id', auth()->id());
        }

        // Filtro: por estatus
        if ($request->filled('estatus')) {
            $query->where('ser_estatus', $request->estatus);
        }

        // Filtro: búsqueda en título o descripción
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) =>
                $q->where('ser_titulo', 'LIKE', "%{$s}%")
                ->orWhere('ser_descripcion', 'LIKE', "%{$s}%")
            );
        }

        // Filtro: fecha desde (>=)
        if ($request->filled('fecha_desde')) {
            $query->where('ser_fecha_publicacion', '>=', $request->fecha_desde);
        }

        // Filtro: fecha hasta (<=) - incluye todo el día
        if ($request->filled('fecha_hasta')) {
            $query->where(
                'ser_fecha_terminacion',
                '<=',
                CarbonImmutable::parse($request->fecha_hasta)->endOfDay()
            );
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
        $allowed = ['ser_orden', 'ser_titulo', 'ser_fecha_publicacion', 'created_at', 'ser_estatus'];

        // Validar campo solicitado, default: ser_orden
        $sortBy = in_array($request->sort_by, $allowed) ? $request->sort_by : 'ser_orden';

        // Validar dirección, default: asc
        $sortDirection = strtolower($request->sort_direction) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDirection);
    }
}
