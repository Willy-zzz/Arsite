<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Noticia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;

class NoticiaController extends BaseApiController
{
    /**
     * Constructor - Autorizar con policies
     */
    public function __construct()
    {
        //Autoriza automáticamente con policies
        $this->authorizeResource(Noticia::class, 'noticia');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Eager loading de usuario para evitar N+1. Se cargan solo datos necesarios del usuario
            $query = Noticia::with('user:id,usu_nombre');
            
            // Aplicar filtros y ordenamiento con métodos privados
            $this->applyFilters($query, $request);
            $this->applySorting($query, $request);

            // Paginar y preservar query params
            $noticias = $query->paginate($request->per_page ?? 15)
                                ->appends($request->query());

            return response()->json([
                'success' => true,
                'data' => $noticias,
                'message' => 'Noticias obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error index noticia: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Validación inicial
        $validator = $this->validateNoticia($request, null);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        //Inicio de transacciób
        DB::beginTransaction();

        // Variables para rastrear archivos subidos y para cleanup si falla
        $newPortada = null; 
        $newImagen = null;

        try {
            $data = $validator->validated();

            // Manejar subida de portada (imagen principal)
            if ($request->hasFile('not_portada')) {
                //Se guarda en subcarpeta específica
                $newPortada = $this->uploadImage($request->file('not_portada'), 'noticias/portadas');
                $data['not_portada'] = $newPortada;
            }

            // Manejar subida de imagen secundaria (cuerpo)
            if ($request->hasFile('not_imagen')) {
                $newImagen = $this->uploadImage($request->file('not_imagen'), 'noticias/imagenes');
                $data['not_imagen'] = $newImagen;
            }

            // Si no se especifica fecha de publicación, usar la actual
            // Permite publicar inmediatamente o programar para el futuro
            if (!isset($data['not_publicacion'])) {
                $data['not_publicacion'] = now();
            }

            // Asignar usuario autenticado (autor)
            $data['user_id'] = auth()->id();

            // Crear y cargar relación
            $noticia = Noticia::create($data);
            $noticia->load('user:id,usu_nombre');

            //Confirmar transacción
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $noticia,
                'message' => 'Noticia creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            //Revertir cambios en BD
            DB::rollBack();
            
            // Limpieza de archivo huérfanos: Limpiar imágenes subidas si hubo error
            if ($newPortada) $this->deleteImage($newPortada);
            if ($newImagen) $this->deleteImage($newImagen);

            Log::error('Error store noticia: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Noticia $noticia): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $noticia->load('user:id,usu_nombre'),
            'message' => 'Noticia obtenida exitosamente'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Noticia $noticia): JsonResponse
    {
        // Validación dinámica
        $validator = $this->validateNoticia($request, $noticia);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();
        $newPortada = null;
        $newImagen = null;

        try {
            // Lock FOR UPDATE: previene modificaciones concurrentes
            $noticia = Noticia::where('not_id', $noticia->not_id)->lockForUpdate()->first();

            // Excluir imágenes del validated para manejo manual
            $data = $validator->safe()->except(['not_portada', 'not_imagen']);

            //Guardamos referencias a imagenes viejas
            $oldPortada = $noticia->not_portada;
            $oldImagen = $noticia->not_imagen;

            // Si el frontend envía campo vacío, no actualizar portada
            // blank() considera: null, '', '   ' como vacío
            if ($request->has('not_portada') && blank($request->not_portada)) {
                unset($data['not_portada']);
            }

            // Lo mismo para imagen secundaria
            if ($request->has('not_imagen') && blank($request->not_imagen)) {
                unset($data['not_imagen']);
            }

            // Manejar subida de nueva portada
            if ($request->hasFile('not_portada')) {
                $newPortada = $this->uploadImage($request->file('not_portada'), 'noticias/portadas');
                $data['not_portada'] = $newPortada;
            }

            // Manejar subida de nueva imagen
            if ($request->hasFile('not_imagen')) {
                $newImagen = $this->uploadImage($request->file('not_imagen'), 'noticias/imagenes');
                $data['not_imagen'] = $newImagen;
            }

            // Actualizar y recargar relación
            $noticia->update($data);
            $noticia->load('user:id,usu_nombre');

            // CRÍTICO: Commit ANTES de borrar archivos
            DB::commit();

            // Ahora es seguro borrar imágenes viejas
            if ($newPortada && $oldPortada) {
                $this->deleteImage($oldPortada);
            }
            if ($newImagen && $oldImagen) {
                $this->deleteImage($oldImagen);
            }

            return response()->json([
                'success' => true,
                'data' => $noticia,
                'message' => 'Noticia actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Limpiar imágenes NUEVAS si falló
            if ($newPortada) $this->deleteImage($newPortada);
            if ($newImagen) $this->deleteImage($newImagen);

            Log::error('Error update noticia: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Noticia $noticia): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Lock del registro para asegurar borrador exclusivo
            $noticia = Noticia::where('not_id', $noticia->not_id)->lockForUpdate()->first();
            $oldPortada = $noticia->not_portada;
            $oldImagen = $noticia->not_imagen;

            // Eliminar de BD
            $noticia->delete();

            // Eliminar archivos físicos asociados
            if ($oldPortada) $this->deleteImage($oldPortada);
            if ($oldImagen) $this->deleteImage($oldImagen);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Noticia eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error destroy noticia: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }
    /**
     * Get noticias for public display (published only)
     */
    public function publicNoticias(Request $request): JsonResponse
    {
        try {
            $now = CarbonImmutable::now();
            
            // Query con filtros de publicación
            $query = Noticia::where('not_estatus', 'Publicado')
                ->whereNotNull('not_publicacion')
                ->where('not_publicacion', '<=', $now); //Filtro temporal clave

            // Filtro por búsqueda si se proporciona
            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(fn($q) =>
                    $q->where('not_titulo', 'LIKE', "%{$s}%")
                    ->orWhere('not_subtitulo', 'LIKE', "%{$s}%")
                );
            }

            // Aplicar ordenamiento específico para noticias públicas
            $allowedSort = ['not_publicacion','not_titulo','created_at'];
            $sortBy = in_array($request->sort_by, $allowedSort) ? $request->sort_by : 'not_publicacion';
            $sortDirection = strtolower($request->sort_direction) === 'desc' ? 'desc' : 'asc';
            $query->orderBy($sortBy, $sortDirection);

            // Paginar (default 10 para blog)
            $noticias = $query->paginate($request->per_page ?? 10);

            return response()->json([
                'success' => true,
                'data' => $noticias,
                'message' => 'Noticias públicas obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error publicNoticias: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Get featured noticias (últimas X noticias)
     */
    public function featuredNoticias(Request $request): JsonResponse
    {
        try {
            $now = CarbonImmutable::now();
            
            // Límite configurable con máximo de seguridad
            $limit = min($request->get('limit', 5), 50);

            // Top N noticias más recientes
            $noticias = Noticia::where('not_estatus', 'Publicado')
                ->whereNotNull('not_publicacion')
                ->where('not_publicacion', '<=', $now)
                ->orderBy('not_publicacion', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $noticias,
                'message' => 'Noticias destacadas obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error featuredNoticias: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Get noticias statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        // Verificar permiso explícito para ver estadísticas
        $this->authorize('viewStatistics', Noticia::class);

        try {
            $now = CarbonImmutable::now();

            $stats = [
                'total' => Noticia::count(),
                'publicadas' => Noticia::where('not_estatus', 'Publicado')->count(),
                'guardadas' => Noticia::where('not_estatus', 'Guardado')->count(),
                
                // Estadísticas temporales
                'este_mes' => Noticia::where('not_estatus', 'Publicado')
                    ->whereMonth('not_publicacion', $now->month)
                    ->whereYear('not_publicacion', $now->year)
                    ->count(),
                    
                'esta_semana' => Noticia::where('not_estatus', 'Publicado')
                    ->whereBetween('not_publicacion', [$now->startOfWeek(), $now->endOfWeek()])
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas de noticias obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error statistics noticia: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        // Verificar permiso para operaciones en lote
        $this->authorize('bulkAction', Noticia::class);

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|distinct|exists:noticias,not_id',
            'estatus' => 'required|in:Publicado,Guardado'
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        try {
            // Closure para transacción automática con Closure
            DB::transaction(function () use ($request) {
                Noticia::whereIn('not_id', $request->ids)
                    ->update(['not_estatus' => $request->estatus]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Estatus de noticias actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulkUpdateStatus noticia: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    // ============= MÉTODOS PRIVADOS AUXILIARES ============= //

    /**
     * Validación dinámica de noticia
     * 
     * Lógica inteligente:
     * - En CREATE: título y descripción requeridos, portada opcional
     * - En UPDATE: todos los campos opcionales
     * - Fecha publicación: >= hoy solo en CREATE
     * 
     * @param Request $request
     * @param Noticia|null $noticia - Si es update, se pasa el modelo
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validateNoticia(Request $request, ?Noticia $noticia = null)
    {
        $isUpdate = !is_null($noticia);

        $rules = [
            // Campos de texto
            'not_titulo' => ($isUpdate ? 'sometimes|' : 'required|') . 'string|max:100',
            'not_subtitulo' => 'sometimes|nullable|string|max:300',
            'not_descripcion' => ($isUpdate ? 'sometimes|' : 'required|') . 'string',
            
            // Imágenes (ambas opcionales)
            'not_portada' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096|dimensions:max_width=4000,max_height=4000',
            'not_imagen' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096|dimensions:max_width=4000,max_height=4000',
            
            // Video (URL de YouTube/Vimeo/Google Drive/etc)
            'not_video' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^https?:\/\/.+/'  // Valida formato de URL básico
            ],
            
            // Fecha y estatus
            'not_publicacion' => 'nullable|date',
            'not_estatus' => ($isUpdate ? 'sometimes|' : 'required|') . 'in:Publicado,Guardado'
        ];

        // Validación de fecha solo en creación (debe ser futura o actual)
        if (!$isUpdate) {
            $rules['not_publicacion'] .= '|after_or_equal:today';
        }

        return Validator::make($request->all(), $rules);
    }

    /**
     * Sube imagen de noticia al storage
     * 
     * Soporta múltiples carpetas para organizar imágenes
     * Nomenclatura: YmdHis_uniqid.ext
     * Ejemplo: 20250124153045_abc123.jpg
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folder - Carpeta destino (noticias/imagenes)
     * @return string - Path relativo en storage
     */
    private function uploadImage($file, string $folder = 'noticias'): string
    {
        $filename = date('YmdHis') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($folder, $filename, 'public');
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
     * - mine: solo mis noticias
     * - estatus: Publicado/Guardado
     * - search: búsqueda en título, subtítulo y descripción
     * - fecha_desde: fecha publicación >= valor
     * - fecha_hasta: fecha publicación <= valor
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    private function applyFilters($query, Request $request): void
    {
        // Filtro: solo mis noticias
        if ($request->boolean('mine')) {
            $query->where('user_id', auth()->id());
        }
        
        // Filtro: por estatus
        if ($request->filled('estatus')) {
            $query->where('not_estatus', $request->estatus);
        }

        // Filtro profundo en 3 campos: búsqueda en título, subtítulo y descripción
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) =>
                $q->where('not_titulo', 'LIKE', "%{$s}%")
                ->orWhere('not_subtitulo', 'LIKE', "%{$s}%")
                ->orWhere('not_descripcion', 'LIKE', "%{$s}%")
            );
        }

        // Filtro: fecha desde (>=)
        if ($request->filled('fecha_desde')) {
            $query->whereDate('not_publicacion', '>=', $request->fecha_desde);
        }

        // Filtro: fecha hasta (<=)
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('not_publicacion', '<=', $request->fecha_hasta);
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
        $allowed = ['not_publicacion', 'not_titulo', 'created_at', 'not_estatus'];

        // Validar campo solicitado, default: not_publicacion
        $sortBy = in_array($request->sort_by, $allowed) ? $request->sort_by : 'not_publicacion';
        
        // Validar dirección, default: asc
        $sortDirection = strtolower($request->sort_direction) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDirection);
    }
}
