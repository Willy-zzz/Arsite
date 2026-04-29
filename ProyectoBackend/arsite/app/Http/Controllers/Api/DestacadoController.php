<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Destacado;
use App\Support\ApiAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\Exportable;

class DestacadoController extends BaseApiController
{
    use Exportable;
    /**
     * Constructor - Autorizar con policies
     */
    public function __construct()
    {
        //Autoriza automáticamente con policies
        $this->authorizeResource(Destacado::class, 'destacado');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Eager loading de usuario para evitar N+1
            $query = Destacado::with('user:id,usu_nombre');
            
            // Aplicar filtros y ordenamiento
            $this->applyFilters($query, $request);
            $this->applySorting($query, $request);

            // Paginar y preservar query params
            $destacados = $query->paginate($request->per_page ?? 15)
                                ->appends($request->query());

            return response()->json([
                'success' => true,
                'data' => $destacados,
                'message' => 'Destacados obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error index destacado: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Validación (pasamos null porque no hay destacado existente)
        $validator = $this->validateDestacado($request, null);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        //Inicio de transacción: Todo o nada
        DB::beginTransaction();
        $newImage = null; // Para cleanup si falla

        try {
            $data = $validator->validated();

            // AUTO-INCREMENTO DE ORDEN: Si no se especifica des_orden, asignarlo automáticamente
            if (empty($data['des_orden']) || $data['des_orden'] === 0) {
                // Obtener el máximo orden actual y sumarle 1
                $maxOrden = Destacado::max('des_orden') ?? 0;
                $data['des_orden'] = $maxOrden + 1;
            }

            // Subir imagen si existe
            if ($request->hasFile('des_imagen')) {
                $newImage = $this->uploadImage($request->file('des_imagen'));
                $data['des_imagen'] = $newImage;
            }

            // Asignar usuario autenticado
            $data['user_id'] = auth()->id();

            // Crear registro y cargar relación
            $destacado = Destacado::create($data);
            $destacado->load('user:id,usu_nombre');

            //Confirmar transacción
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $destacado,
                'message' => 'Destacado creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            //Si falla algo, revertimos BD
            DB::rollBack();
            
            // IMPORTANTE: Si se subió imagen pero falló el insert en BD,
            // borramos el archivo físico para no dejar basura
            if ($newImage) $this->deleteImage($newImage);

            Log::error('Error store destacado: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Destacado $destacado): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $destacado->load('user:id,usu_nombre'),
            'message' => 'Destacado obtenido exitosamente'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Destacado $destacado): JsonResponse
    {
        // Validación dinámica (pasamos el modelo para contexto)
        $validator = $this->validateDestacado($request, $destacado);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();
        $newImage = null;

        try {
            // Lock FOR UPDATE (bloqueo pesimista): previene modificaciones concurrentes
            $destacado = Destacado::where('des_id', $destacado->des_id)->lockForUpdate()->first();

            // Excluir imagen del validated para manejo manual
            $data = $validator->safe()->except(['des_imagen']);
            $oldImage = $destacado->des_imagen;

            // Limpieza de campo imagen:
            //  Si el frontend envía campo vacío, no actualizar imagen
            // blank() considera: null, '', '   ' como vacío
            if ($request->has('des_imagen') && blank($request->des_imagen)) {
                unset($data['des_imagen']);
            }

            // Subir nueva imagen si existe
            if ($request->hasFile('des_imagen')) {
                $newImage = $this->uploadImage($request->file('des_imagen'));
                $data['des_imagen'] = $newImage;
            }

            // Actualizar y recargar relación
            $destacado->update($data);
            $destacado->load('user:id,usu_nombre');

            // CRÍTICO: Commit ANTES de borrar archivos
            DB::commit();

            // Ahora es seguro borrar imagen vieja
            if ($newImage && $oldImage) {
                $this->deleteImage($oldImage);
            }

            return response()->json([
                'success' => true,
                'data' => $destacado,
                'message' => 'Destacado actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Si falló, borramos la imagen NUEVA que acabamos de subir.
            if ($newImage) $this->deleteImage($newImage);

            Log::error('Error update destacado: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Destacado $destacado): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Lock del registro para asegurar exclusividad durante el borrado
            $destacado = Destacado::where('des_id', $destacado->des_id)->lockForUpdate()->first();
            $oldImage = $destacado->des_imagen;

            // Eliminar registro de BD
            $destacado->delete();
            
            // Eliminar archivo físico
            if ($oldImage) $this->deleteImage($oldImage);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Destacado eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error destroy destacado: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Get destacados for public display (published and within date range)
     */
    public function publicDestacados(Request $request): JsonResponse
    {
        try {
            $now = CarbonImmutable::now();
            
            // Query builder con closures para condiciones OR
            $query = Destacado::where('des_estatus', 'Publicado')
                ->where(fn($q) =>
                    // Mostrar si: no tiene fecha de publicación O ya se publicó
                    $q->whereNull('des_fecha_publicacion')
                    ->orWhere('des_fecha_publicacion', '<=', $now)
                )
                ->where(fn($q) =>
                    // Mostrar si: no tiene fecha de terminación O aún no termina
                    $q->whereNull('des_fecha_terminacion')
                    ->orWhere('des_fecha_terminacion', '>=', $now)
                );

            // Ordenamiento consistente con index()
            $allowedSort = ['des_orden','des_titulo','des_fecha_publicacion','created_at','des_estatus'];
            $sortBy = in_array($request->sort_by, $allowedSort) ? $request->sort_by : 'des_orden';
            $sortDirection = strtolower($request->sort_direction) === 'desc' ? 'desc' : 'asc';
            
            $destacados = $query->orderBy($sortBy, $sortDirection)->get();

            return response()->json([
                'success' => true,
                'data' => $destacados,
                'message' => 'Destacados públicos obtenidos exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error publicDestacados: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Update destacados order
     */
    public function updateOrder(Request $request): JsonResponse
    {
        // Verificar permiso para reordenar
        $this->authorize('reorder', Destacado::class);

        $validator = Validator::make($request->all(), [
            'destacados' => 'required|array|min:1',
            'destacados.*.id' => 'required|exists:destacados,des_id|distinct',
            'destacados.*.orden' => 'required|integer|min:0'
        ], [
            'destacados.required' => 'Debe proporcionar al menos un slider',
            'destacados.*.id.distinct' => 'No puede enviar el mismo slider dos veces',
        ]
    );

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        try {
            DB::transaction(function () use ($request) {
                // Extraer IDs para bloqueo preventivo
                $ids = collect($request->destacados)->pluck('id')->toArray();

                // Lock pesimista de todos los destacados a actualizar
                Destacado::whereIn('des_id', $ids)->lockForUpdate()->get();

                foreach ($request->destacados as $destacadoData) {
                    Destacado::where('des_id', $destacadoData['id'])
                    ->update(['des_orden' => $destacadoData['orden']]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Orden de destacados actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updateOrder destacado: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Bulk delete destacados
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        //Verificar permiso para operaciones en lote
        $this->authorize('bulkAction', Destacado::class);

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|exists:destacados,des_id|distinct'
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();
        try {
            // Lock de todos los registros a eliminar
            $destacados = Destacado::whereIn('des_id', $request->ids)
                ->lockForUpdate()
                ->get();

            // Recolectar imágenes (filter elimina nulls)
            $imagePaths = $destacados->pluck('des_imagen')->filter()->toArray();

            // Eliminar registros de BD
            Destacado::whereIn('des_id', $request->ids)->delete();

            // Eliminar archivos físicos
            foreach ($imagePaths as $path) {
                $this->deleteImage($path);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => ['deleted_count' => count($request->ids)],
                'message' => count($request->ids) . ' destacados eliminados exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulkDelete destacado: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    } 

    /**
     * Export destacados to Excel or PDF
     */
    public function export(Request $request)
    {
        $this->authorize('export', Destacado::class);
        
        $query = Destacado::with('user:id,usu_nombre');
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);
        $destacados = $query->get();
        
        $headings = ['ID', 'Título', 'Subtítulo', 'Estado', 'Publicación', 'Terminación', 'Orden'];
        
        $mapping = function($destacado) {
            // Usar badges HTML para estados
            $statusBadge = $destacado->des_estatus === 'Publicado' 
                ? '<span class="badge badge-success">Publicado</span>'
                : '<span class="badge badge-warning">Guardado</span>';
            
            return [
                $destacado->des_id,
                $destacado->des_titulo,
                $destacado->des_subtitulo,
                $statusBadge,
                $destacado->des_fecha_publicacion ? $destacado->des_fecha_publicacion->format('d/m/Y') : 'N/A',
                $destacado->des_fecha_terminacion ? $destacado->des_fecha_terminacion->format('d/m/Y') : 'N/A',
                $destacado->des_orden
            ];
        };
        
        $columnWidths = [
            'A' => 8, 'B' => 30, 'C' => 40, 'D' => 12, 'E' => 18, 'F' => 18, 'G' => 10,
        ];
        
        // Calcular resumen/estadísticas
        $totalPublicados = $destacados->where('des_estatus', 'Publicado')->count();
        $totalGuardados = $destacados->where('des_estatus', 'Guardado')->count();
        
        // Filtros activos
        $activeFilters = [];
        if ($request->filled('estatus')) {
            $activeFilters[] = "Estado: {$request->estatus}";
        }
        if ($request->filled('search')) {
            $activeFilters[] = "Búsqueda: {$request->search}";
        }
        if ($request->boolean('mine')) {
            $activeFilters[] = "Solo mis destacados";
        }
        
        return $this->exportData(
            $request,
            $destacados,
            $headings,
            $mapping,
            'destacados',
            'Reporte de Destacados del Slider',
            $columnWidths,
        );
    }

    // ============= MÉTODOS PRIVADOS AUXILIARES ============= //

    /**
     * Validación dinámica de destacado
     */
    private function validateDestacado(Request $request, ?Destacado $destacado = null)
    {
        $isUpdate = !is_null($destacado);

        $rules = [
            'des_titulo'        => ($isUpdate ? 'sometimes|' : 'required|') . 'string|max:50',
            'des_subtitulo'     => ($isUpdate ? 'sometimes|' : 'required|') . 'string|max:80',
            'des_texto_boton'   => ($isUpdate ? 'sometimes|' : 'required|') . 'string|max:40',
            'des_enlace_boton'  => ($isUpdate ? 'sometimes|' : 'required|') . 'url|max:255',
            'des_imagen'        => ($isUpdate ? 'nullable|sometimes|' : 'required|') . 'image|mimes:jpeg,png,jpg,gif,svg|max:3072|dimensions:max_width=3000,max_height=3000',
            'des_fecha_publicacion' => 'nullable|date',
            'des_fecha_terminacion' => 'nullable|date',
            //'des_orden' => 'nullable|integer|min:0',
            'des_estatus' => ($isUpdate ? 'sometimes|' : 'required|') . 'in:Publicado,Guardado'
        ];

        // VALIDACIÓN DE ORDEN ÚNICO: regla de unicidad
        if ($isUpdate) {
            // En actualización, el orden debe ser único excepto para el destacado actual
            $rules['des_orden'] = [
                'nullable',
                'integer',
                'min:0',
                \Illuminate\Validation\Rule::unique('destacados', 'des_orden')->ignore($destacado->des_id, 'des_id')
            ];
        } else {
            // En creación, el orden debe ser único si se proporciona
            $rules['des_orden'] = 'nullable|integer|min:0|unique:destacados,des_orden';
        }

        // En CREATE, fecha publicación debe ser >= hoy
        if (!$isUpdate) {
            $rules['des_fecha_publicacion'] .= '|after_or_equal:today';
        }

        // Validación con closure after() para lógica compleja
        return ApiAuditLogger::auditValidation(
            Validator::make($request->all(), $rules, [
                'des_imagen.mimes' => 'La imagen debe estar en formato JPG, PNG, GIF o SVG.',
                'des_imagen.max' => 'La imagen no puede superar los 3 MB.',
                'des_imagen.uploaded' => 'La imagen no se pudo subir al servidor. Verifica su tamaño e inténtalo nuevamente.',
            ])->after(function ($validator) use ($request, $destacado, $isUpdate) {

                // CASO 1: Ambas fechas vienen en el request (Inicio y fin)
                // Validar que terminación >= publicación
                if ($request->filled('des_fecha_publicacion') &&
                    $request->filled('des_fecha_terminacion')) {
                    
                    $fechaInicio = Carbon::parse($request->des_fecha_publicacion);
                    $fechaFin = Carbon::parse($request->des_fecha_terminacion);
                    
                    if ($fechaFin->lt($fechaInicio)) {
                        $validator->errors()->add(
                            'des_fecha_terminacion',
                            'La fecha de terminación debe ser mayor o igual a la fecha de publicación.'
                        );
                    }
                }

                // CASO 2: UPDATE con fecha terminación nueva pero conserva fecha publicación de BD
                // Validar que nueva terminación >= publicación existente
                if ($isUpdate &&
                    !$request->filled('des_fecha_publicacion') && // No viene fecha inicio nueva
                    $destacado->des_fecha_publicacion && // Existe fecha inicio en BD
                    $request->filled('des_fecha_terminacion')) { // Viene fecha fin nueva
                    
                    $fechaInicio = Carbon::parse($destacado->des_fecha_publicacion);
                    $fechaFin = Carbon::parse($request->des_fecha_terminacion);
                    
                    if ($fechaFin->lt($fechaInicio)) {
                        $validator->errors()->add(
                            'des_fecha_terminacion',
                            'La fecha de terminación debe ser mayor o igual a la fecha de publicación actual (' .
                            $destacado->des_fecha_publicacion . ').'
                        );
                    }
                }
            }),
            $request,
            'destacados.validation.failed',
            ['module' => 'destacados']
        );
    }

    /**
     * Sube imagen del destacado al storage
     * 
     * Nomenclatura: YmdHis_uniqid.ext
     * Ejemplo: 20250124153045_abc123.jpg
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return string 
     */
    private function uploadImage($file): string
    {
        // Generar nombre único basado en fecha y uniqid
        $filename = date('YmdHis') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('destacados', $filename, 'public');
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
            'errors' => $validator->errors(),
            'message' => 'Error de validación'
        ], 422);
    }

    /**
     * Aplica filtros dinámicos a la query
     * 
     * Filtros soportados:
     * - mine: solo mis destacados
     * - estatus: Publicado/Guardado
     * - search: búsqueda en título/subtítulo
     * - fecha_desde: fecha publicación >= valor
     * - fecha_hasta: fecha terminación <= valor (fin del día)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    private function applyFilters($query, Request $request): void
    {
        // Filtro: solo mis destacados
        if ($request->boolean('mine')) {
            $query->where('user_id', auth()->id());
        }
        
        // Filtro: por estatus
        if ($request->filled('estatus')) {
            $query->where('des_estatus', $request->estatus);
        }

        // Filtro: búsqueda en título o subtítulo
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) =>
                $q->where('des_titulo', 'LIKE', "%{$s}%")
                ->orWhere('des_subtitulo', 'LIKE', "%{$s}%")
            );
        }

        // Filtro: fecha desde (>=)
        if ($request->filled('fecha_desde')) {
            $query->where('des_fecha_publicacion', '>=', $request->fecha_desde);
        }

        // Filtro: fecha hasta (<=) - incluye todo el día
        if ($request->filled('fecha_hasta')) {
            $query->where(
                'des_fecha_terminacion',
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
        $allowed = ['des_orden','des_titulo','des_fecha_publicacion','created_at','des_estatus'];

        // Validar campo solicitado, default: des_orden
        $sortBy = in_array($request->sort_by, $allowed) ? $request->sort_by : 'des_orden';
        
        // Validar dirección, default: asc
        $sortDirection = strtolower($request->sort_direction) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDirection);
    }
}
