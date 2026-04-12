<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contacto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactoController extends BaseApiController
{
    public function __construct()
    {
        // Se excluye 'store' porque es público (formulario de contacto)
        $this->authorizeResource(Contacto::class, 'contacto', [
            'except' => ['store']
        ]);
    }

    /**
     * Display a listing of contactos
     */
    public function index(Request $request): JsonResponse
{
    try {
        $query = Contacto::query();

        // Filtros
        if ($request->filled('estado')) {
            $query->where('con_estado', $request->estado);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('con_nombre', 'LIKE', "%{$search}%")
                  ->orWhere('con_email', 'LIKE', "%{$search}%")
                  ->orWhere('con_empresa', 'LIKE', "%{$search}%")
                  ->orWhere('con_asunto', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Ordenamiento
        $allowedSorts = ['created_at', 'con_nombre', 'con_email', 'con_estado', 'con_asunto'];

        $sortBy = $request->get('sort_by', 'created_at');
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $sortDirection = strtolower($request->get('sort_direction', 'desc'));
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortBy, $sortDirection);

        $contactos = $query->paginate($request->get('per_page', 15))
                           ->appends($request->query());

        return response()->json([
            'success' => true,
            'data'    => $contactos,
            'message' => 'Contactos obtenidos exitosamente'
        ]);

    } catch (\Exception $e) {
        Log::error('Error index contacto: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Error interno'], 500);
    }
}

    /**
     * Store a newly created contacto (desde formulario público)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'con_nombre' => 'required|string|max:100',
            'con_email' => 'required|email|max:150',
            'con_telefono' => 'required|string|max:20',
            'con_asunto' => 'required|string|max:200',
            'con_mensaje' => 'required|string',
            'con_empresa' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }

        try {
            $data = $validator->validated();
            
            // Obtener IP del usuario
            $data['con_ip'] = $request->ip();
            $data['con_estado'] = 'Nuevo';

            $contacto = Contacto::create($data);

            // Enviar notificación por email
            $this->enviarNotificacionContacto($contacto);

            return response()->json([
                'success' => true,
                'data' => $contacto,
                'message' => 'Mensaje de contacto enviado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            // Log del error pero no fallar la creación del contacto
            Log::error('Error al guardar el mensaje de contacto en BD: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el mensaje: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified contacto
     */
    public function show(Contacto $contacto): JsonResponse
    {
         // Marcar como leído si estaba como 'Nuevo'
        if ($contacto->con_estado === 'Nuevo') {
            $contacto->update(['con_estado' => 'Leido']);
        }
        
        return response()->json([
            'success' => true,
            'data' => $contacto,
            'message' => 'Contacto obtenido exitosamente'
        ]);
    }

    /**
     * Update the specified contacto (solo estado)
     */
    public function update(Request $request, Contacto $contacto): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'con_estado' => 'required|in:Nuevo,Leido,Respondido,Archivado'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }

        try{
            
            $contacto->update(['con_estado' => $request->con_estado]);

            return response()->json([
                'success' => true,
                'data' => $contacto,
                'message' => 'Estado del contacto actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el contacto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified contacto
     */
    public function destroy(Contacto $contacto): JsonResponse
    {
        try {
            $contacto->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contacto eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el contacto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contactos statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewStatistics', Contacto::class);

        $stats = [
            'total' => Contacto::count(),
            'nuevos' => Contacto::where('con_estado', 'Nuevo')->count(),
            'leidos' => Contacto::where('con_estado', 'Leido')->count(),
            'respondidos' => Contacto::where('con_estado', 'Respondido')->count(),
            'archivados' => Contacto::where('con_estado', 'Archivado')->count(),
            'este_mes' => Contacto::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'esta_semana' => Contacto::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'hoy' => Contacto::whereDate('created_at', now())->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Estadísticas de contactos obtenidas exitosamente'
        ]);
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $this->authorize('bulkUpdateStatus', Contacto::class);

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:contactos,con_id',
            'estado' => 'required|in:Nuevo,Leido,Respondido,Archivado'
        ], [
            'ids.required' => 'Debes seleccionar al menos un contacto',
            'ids.min' => 'Debes seleccionar al menos un contacto',
            'ids.*.exists' => 'Uno o más contactos seleccionados no existen',
            'estado.required' => 'Debes seleccionar un estado',
            'estado.in' => 'El estado seleccionado no es válido'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }

        try {
            $cantidadActualizada = Contacto::whereIn('con_id', $request->ids)
                ->update(['con_estado' => $request->estado]);

            return response()->json([
                'success' => true,
                'message' => "Estado de {$cantidadActualizada} contacto(s) actualizado exitosamente",
                'data' => [
                    'updated_count' => $cantidadActualizada,
                    'nuevo_estado' => $request->estado
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en bulkUpdateStatus: ' . $e->getMessage(), [
                'ids' => $request->ids,
                'estado' => $request->estado
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete contactos
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $this->authorize('bulkDelete', Contacto::class);

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:contactos,con_id'
        ], [
            'ids.required' => 'Debes seleccionar al menos un contacto',
            'ids.min' => 'Debes seleccionar al menos un contacto',
            'ids.*.exists' => 'Uno o más contactos seleccionados no existen'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }

        try {
            $cantidadEliminada = Contacto::whereIn('con_id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "{$cantidadEliminada} contacto(s) eliminado(s) exitosamente",
                'data' => [
                    'deleted_count' => $cantidadEliminada
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en bulkDelete: ' . $e->getMessage(), [
                'ids' => $request->ids
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar contactos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark multiple contactos as read
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $this->authorize('markAsRead', Contacto::class);

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:contactos,con_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }

        try {
            Contacto::whereIn('con_id', $request->ids)
                ->where('con_estado', 'Nuevo')
                ->update(['con_estado' => 'Leido']);

            return response()->json([
                'success' => true,
                'message' => 'Contactos marcados como leídos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como leídos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export contactos to CSV
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('export', Contacto::class);

        try {
            $query = Contacto::query();

            // Aplicar filtros
            if ($request->has('estado')) {
                $query->where('con_estado', $request->estado);
            }

            if ($request->has('fecha_desde')) {
                $query->whereDate('created_at', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->whereDate('created_at', '<=', $request->fecha_hasta);
            }

            $contactos = $query->orderBy('created_at', 'desc')->get();

            // Preparar datos para CSV
            $csvData = [];
            $csvData[] = ['Nombre', 'Email', 'Teléfono', 'Empresa', 'Asunto', 'Estado', 'Fecha', 'IP'];

            foreach ($contactos as $contacto) {
                $csvData[] = [
                    $contacto->con_nombre,
                    $contacto->con_email,
                    $contacto->con_telefono,
                    $contacto->con_empresa,
                    $contacto->con_asunto,
                    $contacto->con_estado,
                    $contacto->created_at->format('Y-m-d H:i:s'),
                    $contacto->con_ip
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $csvData,
                'message' => 'Datos de contactos exportados exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar los datos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent contactos
     */
    public function recent(Request $request): JsonResponse
    {
        $this->authorize('viewRecent', Contacto::class);

        $limit = $request->get('limit', 10);

        $contactos = Contacto::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $contactos,
            'message' => 'Contactos recientes obtenidos exitosamente'
        ]);
    }

    /**
     * Enviar notificación de nuevo contacto por email
     */
    private function enviarNotificacionContacto(Contacto $contacto)
    {
        try {
            // Email del administrador (puedes configurarlo en .env)
            $adminEmail = config('mail.admin_email', 'admin@ar-site.com');

            // Enviar email al administrador
            Mail::send('emails.nuevo-contacto', ['contacto' => $contacto], function ($message) use ($adminEmail, $contacto) {
                $message->to($adminEmail)
                    ->subject('Nuevo mensaje de contacto - ' . $contacto->con_asunto)
                    ->replyTo($contacto->con_email, $contacto->con_nombre);
            });

            // Enviar email de confirmación al usuario (opcional)
            Mail::send('emails.confirmacion-contacto', ['contacto' => $contacto], function ($message) use ($contacto) {
                $message->to($contacto->con_email, $contacto->con_nombre)
                    ->subject('Hemos recibido tu mensaje - Ar-Site Integradores');
            });

            Log::info('Emails de contacto enviados exitosamente', [
                'contacto_id' => $contacto->con_id,
                'email' => $contacto->con_email
            ]);

        } catch (\Exception $e) {
            // Registrar el error pero no lanzar excepción
            Log::error('Error al enviar emails de contacto: ' . $e->getMessage(), [
                'contacto_id' => $contacto->con_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Resend notification email (para reenviar notificación manualmente)
     */
    public function resendNotification(string|int $id): JsonResponse
    {
        $this->authorize('resendNotification', $contacto);

        try {
            $this->enviarNotificacionContacto($contacto);

            return response()->json([
                'success' => true,
                'message' => 'Notificación reenviada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reenviar notificación: ' . $e->getMessage()
            ], 500);
        }
    }
}