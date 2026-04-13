<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Exports\BannersExport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\Exportable;

class BannerController extends BaseApiController
{
    use Exportable;
    /**
     * Constructor - Autorizar con policies
     */
    public function __construct()
    {
        //Autoriza automáticamente con policies
        $this->authorizeResource(Banner::class, 'banner');
    }

    /**
     * Display a listing of banners
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Eager loading de usuario para evitar N+1. Cargamos la relación 'user' para saber quién creó el banner.
            // Seleccionamos solo columnas necesarias para optimizar la respuesta JSON.
            $query = Banner::with('user:id,usu_nombre');
            
            // Aplicar filtros dinámicos
            $this->applyFilters($query, $request);
            $this->applySorting($query, $request);

            // Paginar y preservar query params en links
            $banners = $query->paginate($request->per_page ?? 15)
                            ->appends($request->query());

            return response()->json([
                'success' => true,
                'data' => $banners,
                'message' => 'Banners obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error index banner: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Store a newly created banner
     */
    public function store(Request $request): JsonResponse
    {
        // Validación de la entrada (pasamos null porque es creación)
        $validator = $this->validateBanner($request, null);
        if ($validator->fails()) return $this->failValidation($validator);

        // Transacción de Base de Datos
        // Necesaria porque involucra subir archivos y crear registros. Si uno falla, revertimos.
        DB::beginTransaction();
        $newImage = null; // Variable para rastrear la imagen subida en caso de error

        try {
            $data = $validator->validated();

            // AUTO-INCREMENTO DE ORDEN: Si no se especifica ban_orden, asignarlo automáticamente
            if (empty($data['ban_orden']) || $data['ban_orden'] === 0) {
                // Obtener el máximo orden actual y sumarle 1
                $maxOrden = Banner::max('ban_orden') ?? 0;
                $data['ban_orden'] = $maxOrden + 1;
            }

            // Subir imagen si existe
            if ($request->hasFile('ban_imagen')) {
                $newImage = $this->uploadImage($request->file('ban_imagen'));
                $data['ban_imagen'] = $newImage;
            }

            // Asignar usuario autenticado
            $data['user_id'] = auth()->id();
            
            // Crear y cargar relación de registro en BD
            $banner = Banner::create($data)->load('user:id,usu_nombre');

            //Confirmar transacción
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $banner,
                'message' => 'Banner creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            // Si algo falla, deshacemos cambios en BD
            DB::rollBack();
            
            // Limpiar imagen subida si hubo error para no llenar el servidor de archivos huerfanos
            if ($newImage) $this->deleteImage($newImage);
            
            Log::error('Error store banner: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Display the specified banner
     */
    public function show(Banner $banner): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $banner->load('user:id,usu_nombre'),
            'message' => 'Banner obtenido exitosamente'
        ]);
    }

    /**
     * Update the specified banner
     */
    public function update(Request $request, Banner $banner): JsonResponse
    {
        // Validación dinámica (pasamos el modelo para contexto)
        $validator = $this->validateBanner($request, $banner);
        if ($validator->fails()) return $this->failValidation($validator);

        DB::beginTransaction();
        $newImage = null;

        try {
            // Lock FOR UPDATE (Pessimistic locking): previene modificaciones concurrentes
            // Evita conflictos si dos administradores editan el mismo banner simultáneamente.
            $banner = Banner::where('ban_id', $banner->ban_id)->lockForUpdate()->first();

            // Excluir imagen del validated para manejo manual
            $data = $validator->safe()->except(['ban_imagen']);
            $oldImage = $banner->ban_imagen;

            // Limpieza de campon: Si el frontend envía campo vacío, no actualizar imagen
            // blank() considera: null, '', '   ' como vacío
            // para evitar que Laravel intente setear null en la columna de la imagen y borre la actual
            if ($request->has('ban_imagen') && blank($request->ban_imagen)) {
                unset($data['ban_imagen']);
            }

            // Subir nueva imagen si existe
            if ($request->hasFile('ban_imagen')) {
                $newImage = $this->uploadImage($request->file('ban_imagen'));
                $data['ban_imagen'] = $newImage;
            }

            // Actualizar y recargar relación
            $banner->update($data);
            $banner->load('user:id,usu_nombre');

            // CRÍTICO: Commit ANTES de borrar archivos para evitar corrupción de datos
            DB::commit();

            // Ahora es seguro borrar la imagen anterior del disco
            if ($newImage && $oldImage) $this->deleteImage($oldImage);

            return response()->json([
                'success' => true,
                'data' => $banner,
                'message' => 'Banner actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Si falló el proceso, borramos la imagen NUEVA que acabamos de subir.
            // La vieja no se toca.
            if ($newImage) $this->deleteImage($newImage);
            
            Log::error('Error update banner: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Remove the specified banner
     */
    public function destroy(Banner $banner): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Lock del registro. Bloqueamos para asegurar exclusividad en el borrado
            $banner = Banner::where('ban_id', $banner->ban_id)->lockForUpdate()->first();
            $oldImage = $banner->ban_imagen;

            // Eliminar de BD
            $banner->delete();
            
            // Eliminar archivo físico
            if ($oldImage) $this->deleteImage($oldImage);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Banner eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error destroy banner: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Get banners for public display (published and within date range)
     */
    public function publicBanners(Request $request): JsonResponse
    {
        try {
            $now = CarbonImmutable::now()->endOfDay();
            
            // Query builder con closures para condiciones OR
            $query = Banner::where('ban_estatus', 'Publicado')
                ->where(fn($q) => 
                    // Mostrar si: no tiene fecha de publicación O ya se publicó
                    $q->whereNull('ban_fecha_publicacion')
                    ->orWhere('ban_fecha_publicacion', '<=', $now)
                )
                ->where(fn($q) => 
                    // Mostrar si: no tiene fecha de terminación O aún no termina
                    $q->whereNull('ban_fecha_terminacion')
                    ->orWhere('ban_fecha_terminacion', '>=', $now)
                );

            // Aplicar ordenamiento (por defecto: ban_orden)
            $this->applySorting($query, $request);

            $banners = $query->get();

            return response()->json([
                'success' => true,
                'data' => $banners,
                'message' => 'Banners públicos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error publicBanners: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Update banner order
     */
    public function updateOrder(Request $request): JsonResponse
    {
        // Verificar permiso para reordenar
        $this->authorize('reorder', Banner::class);

        // Validación de array de objetos
        $validator = Validator::make($request->all(), [
            'banners' => 'required|array|min:1',
            'banners.*.id' => 'required|exists:banners,ban_id|distinct',
            'banners.*.orden' => 'required|integer|min:0'
        ], [
            'banners.required' => 'Debe proporcionar al menos un banner',
            'banners.*.id.distinct' => 'No puede enviar el mismo banner dos veces',
        ]);

        if ($validator->fails()) return $this->failValidation($validator);

        try {
            DB::transaction(function () use ($request) {
                // Lock pesimista de todos los banners a actualizar
                $ids = collect($request->banners)->pluck('id')->toArray();
                Banner::whereIn('ban_id', $ids)->lockForUpdate()->get();
                
                foreach ($request->banners as $bannerData) {
                    Banner::where('ban_id', $bannerData['id'])
                    ->update(['ban_orden' => $bannerData['orden']]);
                }
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Orden actualizado exitosamente'
            ]);
        
        } catch (\Exception $e) {
            Log::error('Error updateOrder banner: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Bulk delete banners (funcionalidad adicional)
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        // Verificar permiso para operaciones en lote
        $this->authorize('bulkAction', Banner::class);

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|exists:banners,ban_id|distinct'
        ]);

        if ($validator->fails()) return $this->failValidation($validator);

        DB::beginTransaction();
        try {
            // Lock de todos los registros a eliminar
            $banners = Banner::whereIn('ban_id', $request->ids)->lockForUpdate()->get();
            
            // Recolectar imágenes (filter elimina nulls)
            $imagePaths = $banners->pluck('ban_imagen')->filter()->toArray();

            // Eliminar registros de BD
            Banner::whereIn('ban_id', $request->ids)->delete();

            // Eliminar archivos físicos
            foreach ($imagePaths as $path) $this->deleteImage($path);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => ['deleted_count' => count($request->ids)],
                'message' => count($request->ids) . ' banners eliminados exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulkDelete banner: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    } 

    /**
     * Export banners to PDF and Excel (funcionalidad adicional)
     */
    public function export(Request $request)
    {
        $this->authorize('export', Banner::class);
        
        $query = Banner::with('user:id,usu_nombre');
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);
        $banners = $query->get();
        
        $headings = ['ID', 'Título', 'Subtítulo', 'Estado', 'Publicación', 'Terminación', 'Orden'];
        
        $mapping = function($banner) {
            // Usar badges HTML para estados
            $statusBadge = $banner->ban_estatus === 'Publicado' 
                ? '<span class="badge badge-success">Publicado</span>'
                : '<span class="badge badge-warning">Guardado</span>';
            
            return [
                $banner->ban_id,
                $banner->ban_titulo,
                $banner->ban_subtitulo,
                $statusBadge,
                $banner->ban_fecha_publicacion ? $banner->ban_fecha_publicacion->format('d/m/Y') : 'N/A',
                $banner->ban_fecha_terminacion ? $banner->ban_fecha_terminacion->format('d/m/Y') : 'N/A',
                $banner->ban_orden
            ];
        };
        
        $columnWidths = [
            'A' => 8, 'B' => 30, 'C' => 40, 'D' => 12, 'E' => 18, 'F' => 18, 'G' => 10,
        ];
        
        // Calcular resumen/estadísticas
        $totalPublicados = $banners->where('ban_estatus', 'Publicado')->count();
        $totalGuardados = $banners->where('ban_estatus', 'Guardado')->count();
        
        // Filtros activos
        $activeFilters = [];
        if ($request->filled('estatus')) {
            $activeFilters[] = "Estado: {$request->estatus}";
        }
        if ($request->filled('search')) {
            $activeFilters[] = "Búsqueda: {$request->search}";
        }
        if ($request->boolean('mine')) {
            $activeFilters[] = "Solo mis banners";
        }
        
        return $this->exportData(
            $request,
            $banners,
            $headings,
            $mapping,
            'banners',
            'Reporte de Banners del Carrusel',
            $columnWidths,
        );
}

    // ============= MÉTODOS PRIVADOS AUXILIARES ============= //

    /**
     * Validar datos del banner con lógica dinámica para fechas
     */
    private function validateBanner(Request $request, ?Banner $banner = null)
    {
        $isUpdate = !is_null($banner);

        $rules = [
            'ban_titulo'       => ($isUpdate ? 'sometimes|' : 'required|') . 'string|max:200',
            'ban_subtitulo'    => ($isUpdate ? 'sometimes|' : 'required|') . 'string|max:300',
            'ban_texto_boton'  => ($isUpdate ? 'sometimes|' : 'required|') . 'string|max:50',
            'ban_enlace_boton' => ($isUpdate ? 'sometimes|' : 'required|') . 'url|max:255',
            'ban_imagen'       => ($isUpdate ? 'nullable|sometimes|' : 'required|') . 'image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120|dimensions:max_width=5000,max_height=5000',
            'ban_fecha_publicacion' => ['nullable', 'date'],
            //'ban_orden'        => 'nullable|integer|min:0',
            'ban_estatus'      => ($isUpdate ? 'sometimes|' : 'required|') . 'in:Publicado,Guardado'
        ];

        // VALIDACIÓN DE ORDEN ÚNICO: regla de unicidad
        if ($isUpdate) {
            // En actualización, el orden debe ser único excepto para el banner actual
            $rules['ban_orden'] = [
                'nullable',
                'integer',
                'min:0',
                \Illuminate\Validation\Rule::unique('banners', 'ban_orden')->ignore($banner->ban_id, 'ban_id')
            ];
        } else {
            // En creación, el orden debe ser único si se proporciona
            $rules['ban_orden'] = 'nullable|integer|min:0|unique:banners,ban_orden';
        }

        // En CREATE, fecha publicación debe ser >= hoy
        if (!$isUpdate) {
            $rules['ban_fecha_publicacion'][] = function ($attribute, $value, $fail) {
                if (Carbon::parse($value)->lt(now()->startOfDay())) {
                $fail('La fecha debe ser hoy o futura.');
                }
            };
        }

        $messages = [
            'ban_fecha_terminacion.after_or_equal' => 'La fecha de terminación debe ser mayor o igual a la fecha de publicación.',
            'ban_orden.unique' => 'Este número de orden ya está siendo utilizado por otro banner. Por favor, elige un número diferente.'
        ];

        // --- VALIDACIÓN DE FECHAS CRUZADAS (Cross-field validation) ---

        // Caso 1: Se envía nueva fecha de publicación -> comparar contra ella
        if ($request->filled('ban_fecha_publicacion')) {
            $rules['ban_fecha_terminacion'] = 'nullable|date|after_or_equal:ban_fecha_publicacion';
            return Validator::make($request->all(), $rules, $messages);
        }

        // Caso 2: Es UPDATE y NO se envía fecha de publicación -> comparar contra BD
        if ($isUpdate && $banner->ban_fecha_publicacion) {
            $rules['ban_fecha_terminacion'] = 'nullable|date';
            
            // Hook manual para comparar request vs valor en BD
            return Validator::make($request->all(), $rules, $messages)
                ->after(function ($validator) use ($request, $banner) {
                    if ($request->filled('ban_fecha_terminacion')) {
                        // Parsear con Carbon para comparación robusta
                        $fechaFin = Carbon::parse($request->ban_fecha_terminacion);
                        $fechaInicio = Carbon::parse($banner->ban_fecha_publicacion);
                        
                        if ($fechaFin->lt($fechaInicio)) {
                            $validator->errors()->add(
                                'ban_fecha_terminacion',
                                'La fecha de terminación debe ser mayor o igual a la fecha de publicación actual (' .
                                $banner->ban_fecha_publicacion . ').'
                            );
                        }
                    }
                });
        }

        // Caso 3: No hay fecha de publicación ni en request ni en BD
        $rules['ban_fecha_terminacion'] = 'nullable|date';
        return Validator::make($request->all(), $rules, $messages);
    }

    // private function uploadImage($file): string
    // {
    //     // Nomenclatura: FechaHora_UniqID.Extensión
    //     $filename = date('YmdHis') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
    //     return $file->storeAs('banners', $filename, 'public');
    // }

    private function uploadImage($file): string
    {
        $filename = date('YmdHis') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        // Guardar directamente en la carpeta public/storage/banners
        $file->move(public_path('storage/banners'), $filename);
        return 'banners/' . $filename; // Devuelve 'banners/archivo.jpg'
    }

    private function deleteImage(?string $path): void
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
            'message' => 'Error de validación'
        ], 422);
    }

    private function applyFilters($query, Request $request): void
    {
        // Filtro: solo mis banners
        if ($request->boolean('mine')) {
            $query->where('user_id', auth()->id());
        }
        
        // Filtro: por estatus
        if ($request->filled('estatus')) {
            $query->where('ban_estatus', $request->estatus);
        }

        // Filtro: búsqueda en título o subtítulo
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) =>
                $q->where('ban_titulo', 'LIKE', "%{$s}%")
                ->orWhere('ban_subtitulo', 'LIKE', "%{$s}%")
            );
        }

        // Filtro: fecha desde (>=)
        if ($request->filled('fecha_desde')) {
            $query->where('ban_fecha_publicacion', '>=', $request->fecha_desde);
        }
        
        // Filtro: fecha hasta (<=) - incluye todo el día
        if ($request->filled('fecha_hasta')) {
            $query->where('ban_fecha_terminacion', '<=', 
                CarbonImmutable::parse($request->fecha_hasta)->endOfDay()
            );
        }
    }

    private function applySorting($query, Request $request): void
    {
        // Whitelist de campos ordenables (seguridad)
        $allowed = ['ban_orden','ban_titulo','ban_fecha_publicacion','created_at','ban_estatus'];
        
        // Validar campo solicitado, default: ban_orden
        $sortBy = in_array($request->sort_by, $allowed) ? $request->sort_by : 'ban_orden';
        
        // Validar dirección, default: asc
        $sortDirection = strtolower($request->sort_direction) === 'desc' ? 'desc' : 'asc';
        
        $query->orderBy($sortBy, $sortDirection);
    }
}
