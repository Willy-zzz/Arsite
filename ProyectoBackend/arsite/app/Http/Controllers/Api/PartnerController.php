<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partner;
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

class PartnerController extends BaseApiController
{
    use Exportable;

    /**
     * Constructor - Autorizar con policies
     */
    public function __construct()
    {
        //Autoriza automáticamente con policies
        $this->authorizeResource(Partner::class, 'partner', [
            'except' => ['publicPartners'],
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Eager loading de usuario para evitar N+1
            $query = Partner::with('user:id,usu_nombre');
            
            // Aplicar filtros y ordenamiento
            $this->applyFilters($query, $request);
            $this->applySorting($query, $request);

            // Paginar y preservar query params
            $partners = $query->paginate($request->per_page ?? 15)
                              ->appends($request->query());

            return response()->json([
                'success' => true,
                'data'    => $partners,
                'message' => 'Partners obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error index partner: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Validación (pasamos null porque no hay partner existente)
        $validator = $this->validatePartner($request, null);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();
        $newLogo = null; // Para cleanup si falla

        try {
            $data = $validator->validated();

            // AUTO-INCREMENTO DE ORDEN: Si no se especifica par_orden, asignarlo automáticamente
            if (empty($data['par_orden']) || $data['par_orden'] === 0) {
                // Obtener el máximo orden actual y sumarle 1
                $maxOrden = Partner::max('par_orden') ?? 0;
                $data['par_orden'] = $maxOrden + 1;
            }

            // Subir logo si existe
            if ($request->hasFile('par_logo')) {
                $newLogo = $this->uploadLogo($request->file('par_logo'));
                $data['par_logo'] = $newLogo;
            }

            // Asignar usuario autenticado
            $data['user_id'] = auth()->id();

            // Crear y cargar relación
            $partner = Partner::create($data);
            $partner->load('user:id,usu_nombre');

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => $partner,
                'message' => 'Partner creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Limpiar logo subido si hubo error
            if ($newLogo) $this->deleteLogo($newLogo);

            Log::error('Error store partner: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Partner $partner): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $partner->load('user:id,usu_nombre'),
            'message' => 'Partner obtenido exitosamente'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Partner $partner): JsonResponse
    {
        // Validación dinámica (pasamos el modelo para contexto)
        $validator = $this->validatePartner($request, $partner);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();
        $newLogo = null;

        try {
            // Lock FOR UPDATE: previene modificaciones concurrentes
            $partner = Partner::where('par_id', $partner->par_id)->lockForUpdate()->first();

            // Excluir logo del validated para manejo manual
            $data    = $validator->safe()->except(['par_logo']);
            $oldLogo = $partner->par_logo;

            // Si el frontend envía campo vacío, no actualizar logo
            // blank() considera: null, '', '   ' como vacío
            if ($request->has('par_logo') && blank($request->par_logo)) {
                unset($data['par_logo']);
            }

            // Subir nuevo logo si existe
            if ($request->hasFile('par_logo')) {
                $newLogo = $this->uploadLogo($request->file('par_logo'));
                $data['par_logo'] = $newLogo;
            }

            // Actualizar y recargar relación
            $partner->update($data);
            $partner->load('user:id,usu_nombre');

            // CRÍTICO: Commit ANTES de borrar archivos
            DB::commit();

            // Ahora es seguro borrar logo viejo
            if ($newLogo && $oldLogo) {
                $this->deleteLogo($oldLogo);
            }

            return response()->json([
                'success' => true,
                'data'    => $partner,
                'message' => 'Partner actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Limpiar logo NUEVO si falló
            if ($newLogo) $this->deleteLogo($newLogo);

            Log::error('Error update partner: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Partner $partner): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Lock del registro
            $partner = Partner::where('par_id', $partner->par_id)->lockForUpdate()->first();
            $oldLogo = $partner->par_logo;

            // Eliminar de BD
            $partner->delete();
            
            // Eliminar archivo físico
            if ($oldLogo) $this->deleteLogo($oldLogo);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Partner eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error destroy partner: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Get partners for public display (published and within date range)
     */
    public function publicPartners(Request $request): JsonResponse
    {
        try {
            $now = CarbonImmutable::now();

            // Query builder con closures para condiciones OR
            $query = Partner::where('par_estatus', 'Publicado')
                ->where(fn($q) =>
                    // Mostrar si: no tiene fecha de publicación O ya se publicó
                    $q->whereNull('par_fecha_publicacion')
                    ->orWhere('par_fecha_publicacion', '<=', $now)
                )
                ->where(fn($q) =>
                    // Mostrar si: no tiene fecha de terminación O aún no termina
                    $q->whereNull('par_fecha_terminacion')
                    ->orWhere('par_fecha_terminacion', '>=', $now)
                );

            // Ordenamiento consistente con index()
            $allowedSort = ['par_nombre', 'par_orden', 'par_fecha_publicacion', 'created_at'];
            $sortBy      = in_array($request->sort_by, $allowedSort) ? $request->sort_by : 'par_orden';
            $sortDir     = strtolower($request->sort_direction) === 'desc' ? 'desc' : 'asc';

            $partners = $query->orderBy($sortBy, $sortDir)->get();

            return response()->json([
                'success' => true,
                'data'    => $partners,
                'message' => 'Partners públicos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error publicPartners: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Get partners statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        // Verificar permiso explícito para ver estadísticas
        $this->authorize('viewStatistics', Partner::class);

        try {
            $now = CarbonImmutable::now();

            $stats = [
                'total'      => Partner::count(),
                'publicados' => Partner::where('par_estatus', 'Publicado')->count(),
                'guardados'  => Partner::where('par_estatus', 'Guardado')->count(),
                
                // Activos = publicados Y no vencidos
                'activos' => Partner::where('par_estatus', 'Publicado')
                    ->where(fn($q) =>
                        $q->whereNull('par_fecha_terminacion')
                        ->orWhere('par_fecha_terminacion', '>=', $now)
                    )
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data'    => $stats,
                'message' => 'Estadísticas de partners obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error statistics partner: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Update partners order
     */
    public function updateOrder(Request $request): JsonResponse
    {
        //Verificar permiso para reordenar
        $this->authorize('reorder', Partner::class);

        $validator = Validator::make($request->all(), [
            'partners'         => 'required|array|min:1',
            'partners.*.id'    => 'required|exists:partners,par_id|distinct',
            'partners.*.orden' => 'required|integer|min:0'
        ], [
            'partners.required'      => 'Debe proporcionar al menos un partner',
            'partners.*.id.distinct' => 'No puede enviar el mismo partner dos veces',
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        try {
            DB::transaction(function () use ($request) {
                // Extraer IDs para bloqueo preventivo
                $ids = collect($request->partners)->pluck('id')->toArray();

                // Lock pesimista de todos los partners a actualizar
                Partner::whereIn('par_id', $ids)->lockForUpdate()->get();

                foreach ($request->partners as $item) {
                    Partner::where('par_id', $item['id'])
                        ->update(['par_orden' => $item['orden']]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Orden de partners actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updateOrder partner: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        // Verificar permiso para operaciones en lote
        $this->authorize('bulkAction', Partner::class);

        $validator = Validator::make($request->all(), [
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'required|distinct|exists:partners,par_id',
            'estatus' => 'required|in:Publicado,Guardado'
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        try {
            // Closure para transacción automática
            DB::transaction(function () use ($request) {
                Partner::whereIn('par_id', $request->ids)
                    ->update(['par_estatus' => $request->estatus]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Estatus de partners actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulkUpdateStatus partner: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Bulk delete partner (funcionalidad adicional)
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        // Verificar permiso para operaciones en lote
        $this->authorize('bulkAction', Partner::class);

        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|exists:partners,par_id|distinct'
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();
        try {
            // Lock de todos los registros a eliminar
            $partners = Partner::whereIn('par_id', $request->ids)
                ->lockForUpdate()
                ->get();

            // Recolectar logos (filter elimina nulls)
            $logoPaths = $partners->pluck('par_logo')->filter()->toArray();

            // Eliminar registros de BD
            Partner::whereIn('par_id', $request->ids)->delete();

            // Eliminar archivos físicos
            foreach ($logoPaths as $path) {
                $this->deleteLogo($path);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => ['deleted_count' => count($request->ids)],
                'message' => count($request->ids) . ' partners eliminados exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulkDelete partner: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Export partners to Excel or PDF
     */
    public function export(Request $request)
    {
        $this->authorize('export', Partner::class);

        $query = Partner::with('user:id,usu_nombre');
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);
        $partners = $query->get();

        $headings = ['ID', 'Nombre', 'Estado', 'Orden', 'Publicación', 'Terminación', 'Creado por'];

        $mapping = function ($partner) {
            // Usar badges HTML para estados
            $statusBadge = $partner->par_estatus === 'Publicado'
                ? '<span class="badge badge-success">Publicado</span>'
                : '<span class="badge badge-warning">Guardado</span>';

            return [
                $partner->par_id,
                $partner->par_nombre,
                $statusBadge,
                $partner->par_orden ?? '—',
                $partner->par_fecha_publicacion ? $partner->par_fecha_publicacion->format('d/m/Y') : 'N/A',
                $partner->par_fecha_terminacion ? $partner->par_fecha_terminacion->format('d/m/Y') : 'N/A',
                $partner->user?->usu_nombre ?? '—',
            ];
        };

        $columnWidths = [
            'A' => 8, 'B' => 35, 'C' => 14, 'D' => 10, 'E' => 18, 'F' => 18, 'G' => 25,
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
            $activeFilters[] = "Solo mis partners";
        }

        return $this->exportData(
            $request,
            $partners,
            $headings,
            $mapping,
            'partners',
            'Reporte de Partners',
            $columnWidths,
        );
    }

    // ============= MÉTODOS PRIVADOS AUXILIARES ============= //

    /**
     * Validación dinámica de partner
     *
     * Lógica inteligente:
     * - En CREATE: nombre único requerido, logo requerido, fecha >= hoy
     * - En UPDATE: campos opcionales, nombre único ignorando registro actual
     * - Fecha terminación: valida contra fecha publicación (request o BD)
     *
     * @param Request $request
     * @param Partner|null $partner - Si es update, se pasa el modelo
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validatePartner(Request $request, ?Partner $partner = null)
    {
        $isUpdate = !is_null($partner);

        $rules = [
            // Nombre: único (ignorando el registro actual en updates)
            'par_nombre' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
                Rule::unique('partners', 'par_nombre')->ignore($partner?->par_id, 'par_id')
            ],

            'par_descripcion' => [
                'nullable',
                'string',
                'max:1000',
            ],

            // Logo: requerido en create, opcional en update
            'par_logo' => ($isUpdate ? 'sometimes|' : 'required|') .
                'image|mimes:jpeg,png,jpg,gif,svg,webp|max:3072|dimensions:max_width=3000,max_height=3000',

            // Fechas
            'par_fecha_publicacion' => 'nullable|date',
            'par_fecha_terminacion' => 'nullable|date',

            // Estatus
            'par_estatus' => ($isUpdate ? 'sometimes|' : 'required|') . 'in:Publicado,Guardado',

            // Orden: opcional, único (ignorando registro actual en updates)
            'par_orden' => $isUpdate
                ? ['nullable', 'integer', 'min:0',
                    Rule::unique('partners', 'par_orden')->ignore($partner?->par_id, 'par_id')]
                : ['nullable', 'integer', 'min:0', Rule::unique('partners', 'par_orden')],
        ];

        // En CREATE, fecha publicación debe ser >= hoy
        if (!$isUpdate) {
            $rules['par_fecha_publicacion'] .= '|after_or_equal:today';
        }

        // VALIDACIÓN DE FECHA DE TERMINACIÓN

        // Caso 1: Si viene nueva fecha de publicación en el request
        if ($request->filled('par_fecha_publicacion')) {
            $rules['par_fecha_terminacion'] = 'nullable|date|after_or_equal:par_fecha_publicacion';
            return Validator::make($request->all(), $rules);
        }

        // Caso 2: Es update Y NO viene fecha nueva PERO existe en BD
        if ($isUpdate && $partner && $partner->par_fecha_publicacion) {
            $rules['par_fecha_terminacion'] = 'nullable|date';

            // Validación custom con closure after()
            return Validator::make($request->all(), $rules)
                ->after(function ($validator) use ($request, $partner) {
                    if ($request->filled('par_fecha_terminacion')) {
                        // Parsear con Carbon para comparación robusta
                        $fechaFin    = Carbon::parse($request->par_fecha_terminacion);
                        $fechaInicio = Carbon::parse($partner->par_fecha_publicacion);

                        if ($fechaFin->lt($fechaInicio)) {
                            $validator->errors()->add(
                                'par_fecha_terminacion',
                                'La fecha de terminación debe ser mayor o igual a la fecha de publicación actual (' .
                                $partner->par_fecha_publicacion . ').'
                            );
                        }
                    }
                });
        }

        // Caso 3: No hay fecha de publicación de referencia
        $rules['par_fecha_terminacion'] = 'nullable|date';
        return Validator::make($request->all(), $rules);
    }

    /**
     * Sube logo del partner al storage
     *
     * Nomenclatura: YmdHis_uniqid.ext
     * Ejemplo: 20250124153045_abc123.png
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string - Path relativo en storage/app/public/partners/
     */
    private function uploadLogo($file): string
    {
        $filename = date('YmdHis') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('partners', $filename, 'public');
    }

    /**
     * Elimina logo del storage
     *
     * Verifica existencia antes de borrar
     *
     * @param string|null $path
     * @return void
     */
    private function deleteLogo(?string $path): void
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
     * - mine: solo mis partners
     * - estatus: Publicado/Guardado
     * - search: búsqueda en nombre
     * - fecha_desde: fecha publicación >= valor
     * - fecha_hasta: fecha terminación <= valor (fin del día)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    private function applyFilters($query, Request $request): void
    {
        // Filtro: solo mis partners
        if ($request->boolean('mine')) {
            $query->where('user_id', auth()->id());
        }

        // Filtro: por estatus
        if ($request->filled('estatus')) {
            $query->where('par_estatus', $request->estatus);
        }

        // Filtro: búsqueda por nombre
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('par_nombre', 'LIKE', "%{$s}%");
        }

        // Filtro: fecha desde (>=)
        if ($request->filled('fecha_desde')) {
            $query->where('par_fecha_publicacion', '>=', $request->fecha_desde);
        }

        // Filtro: fecha hasta (<=) - incluye todo el día
        if ($request->filled('fecha_hasta')) {
            $query->where(
                'par_fecha_terminacion',
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
        $allowed = ['par_nombre', 'par_orden', 'created_at', 'par_estatus', 'par_fecha_publicacion'];

        // Validar campo solicitado, default: par_orden
        $sortBy = in_array($request->sort_by, $allowed) ? $request->sort_by : 'par_orden';

        // Validar dirección, default: asc
        $sortDirection = strtolower($request->sort_direction) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDirection);
    }
}
