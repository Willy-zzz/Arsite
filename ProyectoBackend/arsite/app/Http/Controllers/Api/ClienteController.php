<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
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

class ClienteController extends BaseApiController
{
    use Exportable;

    /**
     * Constructor - Autorizar con policies
     */
    public function __construct()
    {
        //Autoriza automáticamente con policies
        $this->authorizeResource(Cliente::class, 'cliente');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Eager loading para evitar N+1 queries
            // Solo seleccionamos id y nombre del usuario relacionado
            $query = Cliente::with('user:id,usu_nombre');

            // Aplicamos filtros dinámicos (mine, estatus, search, fechas)
            $this->applyFilters($query, $request);
            
            // Aplicamos ordenamiento (nombre, fecha, estatus, etc.)
            $this->applySorting($query, $request);

            // Paginamos y preservamos query params en los links de paginación
            $clientes = $query
                ->paginate($request->per_page ?? 15)
                ->appends($request->query());

            return response()->json([
                'success' => true,
                'data' => $clientes,
                'message' => 'Clientes obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            // Log del error para debugging (no exponer stack trace al cliente)
            Log::error('Error index cliente: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Error interno'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Validación inicial (nombre único, logo requerido, fechas, etc.)
        $validator = $this->validateCliente($request);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        // Iniciamos transacción para atomicidad. Asegura que si falla la subida de imagen o el insert, no quede basura en la BD.
        DB::beginTransaction();
        $newLogo = null; // Para cleanup en caso de error

        try {
            // Obtenemos solo los datos validados para evitar asignación masiva
            $data = $validator->validated();

            // AUTO-INCREMENTO DE ORDEN: Si no se especifica cli_orden, asignarlo automáticamente
            if (empty($data['cli_orden']) || $data['cli_orden'] === 0) {
                $maxOrden = Cliente::max('cli_orden') ?? 0;
                $data['cli_orden'] = $maxOrden + 1;
            }

            // Subir logo si existe
            if ($request->hasFile('cli_logo')) {
                $newLogo = $this->uploadLogo($request->file('cli_logo'));
                $data['cli_logo'] = $newLogo;
            }

            // Asignar usuario autenticado
            $data['user_id'] = auth()->id();

            // Crear registro y eager load la relación user
            $cliente = Cliente::create($data)->load('user:id,usu_nombre');

            // Todo salió bien, confirmar transacción
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $cliente,
                'message' => 'Cliente creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            // Si algo falla, revertir cambios en BD y borrar la imagen si se llegó a subir
            DB::rollBack();
            
            // Limpiar archivo subido si existe (evita archivos huérfanos)
            if ($newLogo) $this->deleteLogo($newLogo);

            Log::error('Error store cliente: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $cliente->load('user:id,usu_nombre'),
            'message' => 'Cliente obtenido exitosamente'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cliente $cliente): JsonResponse
    {
        // Validación dinámica (pasamos el modelo para validar fechas contra BD)
        $validator = $this->validateCliente($request, $cliente);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();
        $newLogo = null;

        try {
            // Lock FOR UPDATE: previene que otra transacción modifique este registro
            // hasta que terminemos (evita race conditions)
            $cliente = Cliente::where('cli_id', $cliente->cli_id)->lockForUpdate()->first();

            // Obtener datos limpios y excluimos cli_logo del validated para manejarlo manualmente
            $data = $validator->safe()->except(['cli_logo']);
            $oldLogo = $cliente->cli_logo;

            // Si el frontend envía campo vacío, no actualizar el logo para no borrar el logo existente accidentalmente.
            // blank() considera: null, '' y '   ' como vacío
            if ($request->has('cli_logo') && blank($request->cli_logo)) {
                unset($data['cli_logo']);
            }

            // Si hay archivo nuevo, subirlo
            if ($request->hasFile('cli_logo')) {
                $newLogo = $this->uploadLogo($request->file('cli_logo'));
                $data['cli_logo'] = $newLogo;
            }

            // Actualizar y recargar relación
            $cliente->update($data);
            $cliente->load('user:id,usu_nombre');

            // CRÍTICO: Commit ANTES de borrar archivos.
            // Si algo falla aquí, el rollback protege la BD
            DB::commit();

            // AHORA ES SEGURO borrar el logo viejo
            // Solo se elimina el logo viejo del disco si la transacción fue exitosa y subieron uno nuevo.
            if ($newLogo && $oldLogo) {
                $this->deleteLogo($oldLogo);
            }

            return response()->json([
                'success' => true,
                'data' => $cliente,
                'message' => 'Cliente actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Si falló la BD, limpiamos el logo NUEVO que acabamos de subir para no dejar basura
            if ($newLogo) $this->deleteLogo($newLogo);

            Log::error('Error update cliente: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Error interno'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cliente $cliente): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Lock del registro para evitar modificaciones concurrentes
            $cliente = Cliente::where('cli_id', $cliente->cli_id)->lockForUpdate()->first();
            $logo = $cliente->cli_logo;

            // Eliminar de BD
            $cliente->delete();

            // Eliminar archivo físico solo si existe el registro de la BD
            if ($logo) {
                $this->deleteLogo($logo);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cliente eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error destroy cliente: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Get clientes for public display (published and within date range)
     */
    public function publicClientes(Request $request): JsonResponse
    {
        try {
            $now = CarbonImmutable::now();

            // Lógica de Negocio: Un cliente es visible si:
            // 1. Está 'Publicado'
            // 2. La fecha de publicación ya pasó O es nula (siempre visible)
            // 3. La fecha de terminación NO ha pasado O es nula (indefinido)

            // Query builder con closures para condiciones OR
            $clientesQuery = Cliente::where('cli_estatus', 'Publicado')
                ->where(fn ($q) =>
                    // Mostrar si: no tiene fecha de publicación O ya se publicó
                    $q->whereNull('cli_fecha_publicacion')
                    ->orWhere('cli_fecha_publicacion', '<=', $now)
                )
                ->where(fn ($q) =>
                    // Mostrar si: no tiene fecha de terminación O aún no termina
                    $q->whereNull('cli_fecha_terminacion')
                    ->orWhere('cli_fecha_terminacion', '>=', $now)
                );

            // Ordenamiento consistente con index()
            $allowedSort = ['cli_nombre', 'cli_orden', 'cli_fecha_publicacion', 'created_at'];
            $sortBy = in_array($request->sort_by, $allowedSort) ? $request->sort_by : 'cli_orden';
            $dir = strtolower($request->sort_direction) === 'desc' ? 'desc' : 'asc';

            $clientes = $clientesQuery->orderBy($sortBy, $dir)->get();

            return response()->json([
                'success' => true,
                'data' => $clientes,
                'message' => 'Clientes públicos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error publicClientes: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Get clientes statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        // Verificar permiso explícito para ver estadísticas
        $this->authorize('viewStatistics', Cliente::class);

        try {
            $now = CarbonImmutable::now();

            // Cálculos rápidos usando count() en base de datos
            $stats = [
                'total' => Cliente::count(),
                'publicados' => Cliente::where('cli_estatus', 'Publicado')->count(),
                'guardados' => Cliente::where('cli_estatus', 'Guardado')->count(),
                
                // Activos = publicados Y no vencidos
                'activos' => Cliente::where('cli_estatus', 'Publicado')
                    ->where(fn ($q) =>
                        $q->whereNull('cli_fecha_terminacion')
                        ->orWhere('cli_fecha_terminacion', '>=', $now)
                    )
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas de clientes obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error statistics cliente: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Update clientes order
     */
    public function updateOrder(Request $request): JsonResponse
    {
        //Verificar permiso para reordenar
        $this->authorize('reorder', Cliente::class);

        $validator = Validator::make($request->all(), [
            'clientes'         => 'required|array|min:1',
            'clientes.*.id'    => 'required|exists:clientes,cli_id|distinct',
            'clientes.*.orden' => 'required|integer|min:0'
        ], [
            'clientes.required'      => 'Debe proporcionar al menos un cliente',
            'clientes.*.id.distinct' => 'No puede enviar el mismo cliente dos veces',
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        try {
            DB::transaction(function () use ($request) {
                // Extraer IDs para bloqueo preventivo
                $ids = collect($request->clientes)->pluck('id')->toArray();

                // Lock pesimista de todos los clientes a actualizar
                Cliente::whereIn('cli_id', $ids)->lockForUpdate()->get();

                foreach ($request->clientes as $item) {
                    Cliente::where('cli_id', $item['id'])
                        ->update(['cli_orden' => $item['orden']]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Orden de clientes actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updateOrder cliente: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        // Verificar permiso para operaciones en lote
        $this->authorize('bulkAction', Cliente::class);

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|distinct|exists:clientes,cli_id', // distinct: sin duplicados
            'estatus' => 'required|in:Publicado,Guardado'
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        try {
            // Closure para transacción automática, se asegura que se actualicen todos o ninguno
            DB::transaction(function () use ($request) {
                Cliente::whereIn('cli_id', $request->ids)
                    ->update(['cli_estatus' => $request->estatus]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Estatus de clientes actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulkUpdateStatus cliente: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Error interno'
            ], 500);
        }
    }

    /**
     * Bulk delete cliente
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        //Verificar permiso para operaciones en lote
        $this->authorize('bulkAction', Cliente::class);

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|distinct|exists:clientes,cli_id'
        ]);

        if ($validator->fails()) {
            return $this->failValidation($validator);
        }

        DB::beginTransaction();

        try {
            // Lock de todos los registros a eliminar
            $clientes = Cliente::whereIn('cli_id', $request->ids)->lockForUpdate()->get();
            
            // Recolectar las rutas de los logos (filter elimina nulls) antes de borrar los registros de la BD
            $logos = $clientes->pluck('cli_logo')->filter()->toArray();

            // Eliminar registros de BD
            Cliente::whereIn('cli_id', $request->ids)->delete();

            // Eliminar archivos físicos
            foreach ($logos as $path) {
                $this->deleteLogo($path);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => ['deleted_count' => count($request->ids)],
                'message' => count($request->ids) . ' clientes eliminados exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulkDelete cliente: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Error interno'
            ], 500);
        }
    }

    /**
     * Export clientes to Excel or PDF
     */
    public function export(Request $request)
    {
        $this->authorize('export', Cliente::class);

        $query = Cliente::with('user:id,usu_nombre');
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);
        $clientes = $query->get();

        $headings = ['ID', 'Nombre', 'Estado', 'Orden', 'Publicación', 'Terminación', 'Creado por'];

        $mapping = function ($cliente) {
            // Usar badges HTML para estados
            $statusBadge = $cliente->cli_estatus === 'Publicado'
                ? '<span class="badge badge-success">Publicado</span>'
                : '<span class="badge badge-warning">Guardado</span>';

            return [
                $cliente->cli_id,
                $cliente->cli_nombre,
                $statusBadge,
                $cliente->cli_orden ?? '—',
                $cliente->cli_fecha_publicacion ? $cliente->cli_fecha_publicacion->format('d/m/Y') : 'N/A',
                $cliente->cli_fecha_terminacion ? $cliente->cli_fecha_terminacion->format('d/m/Y') : 'N/A',
                $cliente->user?->usu_nombre ?? '—',
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
            $activeFilters[] = "Solo mis clientes";
        }

        return $this->exportData(
            $request,
            $clientes,
            $headings,
            $mapping,
            'clientes',
            'Reporte de Clientes',
            $columnWidths,
        );
    }

    // ============= MÉTODOS PRIVADOS AUXILIARES ============= //

    /**
     * Validación dinámica del cliente
     */
    private function validateCliente(Request $request, ?Cliente $cliente = null)
    {
        $isUpdate = !is_null($cliente);

        $rules = [
            // Nombre: único (ignorando el registro actual en updates)
            'cli_nombre' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
                // Validación única ignorando el ID actual si es update
                Rule::unique('clientes', 'cli_nombre')->ignore($cliente?->cli_id, 'cli_id')
            ],

            // Logo: requerido en create, opcional en update
            'cli_logo' => [
                $isUpdate ? 'sometimes' : 'required',
                'image',
                'mimes:jpeg,png,jpg,gif,svg',
                'max:3072', // 3MB
                'dimensions:max_width=3000,max_height=3000'
            ],

            // Fecha publicación: requerida y futura en create, opcional en update
            'cli_fecha_publicacion' => $isUpdate
                ? ['sometimes', 'nullable', 'date']
                : ['required', 'date', 'after_or_equal:today'],

            // Estatus
            'cli_estatus' => [
                $isUpdate ? 'sometimes' : 'required',
                Rule::in(['Publicado', 'Guardado'])
            ],

            // Orden: opcional, único (ignorando registro actual en updates)
            'cli_orden' => $isUpdate
                ? ['nullable', 'integer', 'min:0',
                    Rule::unique('clientes', 'cli_orden')->ignore($cliente?->cli_id, 'cli_id')]
                : ['nullable', 'integer', 'min:0', Rule::unique('clientes', 'cli_orden')],
        ];

        // LÓGICA COMPLEJA DE FECHAS (Cross-field validation)
        // Validamos que la fecha de terminación no sea anterior a la de publicación

        // Caso 1: Si viene nueva fecha de publicación en el request
        if ($request->filled('cli_fecha_publicacion')) {
            // Usar regla 'after_or_equal' con el nombre del campo
            $rules['cli_fecha_terminacion'] = ['nullable', 'date', 'after_or_equal:cli_fecha_publicacion'];
        }
        // Caso 2: El usuario NO envía fecha de inicio (se mantiene la de DB), pero sí cambia la fecha fin.
        // Debemos comparar manualmente la fecha fin nueva vs la fecha inicio vieja en DB.
        elseif ($isUpdate && $cliente && $cliente->cli_fecha_publicacion) {
            $rules['cli_fecha_terminacion'] = ['nullable', 'date'];

            // Validación custom con closure after()
            return Validator::make($request->all(), $rules)->after(function ($v) use ($request, $cliente) {
                if ($request->filled('cli_fecha_terminacion')) {
                    // Parsear ambas fechas con Carbon para comparación robusta
                    $fechaFin = Carbon::parse($request->cli_fecha_terminacion);
                    $fechaInicio = Carbon::parse($cliente->cli_fecha_publicacion);

                    // lt() = less than (menor que)
                    if ($fechaFin->lt($fechaInicio)) {
                        $v->errors()->add(
                            'cli_fecha_terminacion',
                            "La fecha de terminación debe ser mayor o igual a la fecha de publicación actual ({$cliente->cli_fecha_publicacion})."
                        );
                    }
                }
            });
        }
        // Caso 3: No hay fecha de publicación de referencia
        else {
            $rules['cli_fecha_terminacion'] = ['nullable', 'date'];
        }

        return Validator::make($request->all(), $rules);
    }

    /**
     * Sube logo del cliente al storage
     *
     * Nomenclatura: YmdHis_uniqid.ext
     * Ejemplo: 20250124153045_67890abcd.png
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string - Path relativo en storage/app/public/clientes/
     */
    private function uploadLogo($file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        // Nombre único: timestamp + id único
        $filename = now()->format('YmdHis') . '_' . uniqid() . '.' . $ext;

        // Guardar en: storage/app/public/clientes/
        // Retorna: clientes/20250124153045_67890abcd.png
        return $file->storeAs('clientes', $filename, 'public');
    }

    /**
     * Elimina logo del storage
     *
     * Verifica existencia antes de intentar borrar (evita excepciones)
     *
     * @param string|null $path - Path relativo (ej: clientes/archivo.jpg)
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
     * @return JsonResponse - Status 422 (Unprocessable Entity)
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
     * - mine: solo mis clientes (por user_id)
     * - estatus: Publicado/Guardado
     * - search: búsqueda en nombre (LIKE)
     * - fecha_desde: fecha publicación >= valor
     * - fecha_hasta: fecha terminación <= valor (fin del día)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    private function applyFilters($query, Request $request): void
    {
        // Filtro: solo mis registros
        if ($request->boolean('mine')) {
            $query->where('user_id', auth()->id());
        }

        // Filtro: por estatus
        if ($request->filled('estatus')) {
            $query->where('cli_estatus', $request->estatus);
        }

        // Filtro: búsqueda por nombre (case-insensitive en MySQL)
        if ($request->filled('search')) {
            $query->where('cli_nombre', 'LIKE', '%' . $request->search . '%');
        }

        // Filtro: fecha desde (>=)
        if ($request->filled('fecha_desde')) {
            $query->where('cli_fecha_publicacion', '>=', $request->fecha_desde);
        }

        // Filtro: fecha hasta (<=) - incluye todo el día
        if ($request->filled('fecha_hasta')) {
            $query->where(
                'cli_fecha_terminacion',
                '<=',
                CarbonImmutable::parse($request->fecha_hasta)->endOfDay() // 23:59:59
            );
        }
    }

    /**
     * Aplica ordenamiento dinámico a la query
     *
     * Solo permite ordenar por campos en whitelist (seguridad)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    private function applySorting($query, Request $request): void
    {
        // Whitelist de campos ordenables (previene SQL injection)
        $allowed = ['cli_nombre', 'cli_orden', 'created_at', 'cli_estatus', 'cli_fecha_publicacion'];

        // Validar campo solicitado, default: cli_orden
        $sortBy = in_array($request->sort_by, $allowed) ? $request->sort_by : 'cli_orden';

        // Validar dirección, default: asc
        $dir = strtolower($request->sort_direction) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $dir);
    }
}