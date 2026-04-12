<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UserController extends BaseApiController
{
    /**
     * Constructor - Autorizar con policies
     */
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Listar todos los usuarios (con filtros y paginación)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::query();

            //Filtro por estado
            if ($request->has('estado')) {
                $query->where('usu_estado', $request->estado);
            }

            //Filtro por rol
            if ($request->has('rol')) {
                $query->where('usu_rol', $request->rol);
            }

            //Busqueda por nombre o email
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('usu_nombre', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                });
            }

            //Ordenamiento
            $allowedSorts = ['usu_nombre', 'email', 'usu_rol', 'usu_estado', 'created_at', 'updated_at'];
            
            $sortBy = $request->get('sort_by', 'created_at'); 
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'created_at'; // Fallback si el campo no está permitido
            }

            $sortDirection = strtolower($request->get('sort_direction', 'desc'));
            if (!in_array($sortDirection, ['asc', 'desc'])) {
                $sortDirection = 'desc';
            }

            $query->orderBy($sortBy, $sortDirection);

            //Paginación
            $perPage = $request->get('per_page', 10);
            $users = $query->paginate($perPage);

            //Ocultar información sensible
            $users->getCollection()->transform(function($user) {
                return [
                    'id' => $user->id,
                    'nombre' => $user->usu_nombre,
                    'email' => $user->email,
                    'rol' => $user->usu_rol,
                    'estado' => $user->usu_estado,
                    'iniciales' => $user->initials,
                    'avatar' => $user->avatar_data,
                    'fecha_registro' => $user->created_at->format('d/m/Y H:i'),
                    'ultima_actualizacion' => $user->updated_at->format('d/m/Y H:i'),
                    'total_contenidos' => $user->getTotalContent(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Usuarios obtenidos exitosamente'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al listar usuarios: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los usuarios'
            ], 500);
        }
    }

    /**
     * Listar usuarios pendientes de aprobación
     */
    public function pending(Request $request): JsonResponse
    {
        $this->authorize('viewPending', User::class);

        try {
            $users = User::where('usu_estado', 'Pendiente')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'nombre' => $user->usu_nombre,
                        'email' => $user->email,
                        'rol' => $user->usu_rol,
                        'fecha_registro' => $user->created_at->format('d/m/Y H:i'),
                        'dias_pendiente' => $user->created_at->diffInDays(now()),
                    ];
                });

                return response()->json([
                    'success' => true,
                'data' => $users,
                'total' => $users->count(),
                'message' => 'Usuarios pendientes obtenidos exitosamente'
                ], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener usuarios pendientes: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios pendientes'
            ], 500);
        }
    }

    /**
     * Obtener un usuario específico
     */
    public function show(User $user): JsonResponse
    {
        $userData = [
            'id' => $user->id,
            'nombre' => $user->usu_nombre,
            'email' => $user->email,
            'rol' => $user->usu_rol,
            'estado' => $user->usu_estado,
            'iniciales' => $user->initials,
            'fecha_registro' => $user->created_at->format('d/m/Y H:i'),
            'ultima_actualizacion' => $user->updated_at->format('d/m/Y H:i'),
            'estadisticas' => [
                'total_contenidos' => $user->getTotalContent(),
                'tokens_activos' => $user->tokens()->count(),
            ],
            'permisos' => [
                'puede_editar' => $user->canEdit(),
                'puede_eliminar' => $user->canDelete(),
                'puede_gestionar_usuarios' => $user->canManageUsers(),
                'puede_publicar' => $user->canPublish(),
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $userData,
                'message' => 'Usuario obtenido exitosamente'
            ], 200);
    }

    /**
     * Crear nuevo usuario (solo admin)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'usu_nombre' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8',
            'usu_rol' => 'required|in:Administrador,Editor',
            'usu_estado' => 'sometimes|in:Activo,Pendiente,Inactivo',
        ], [
            'usu_nombre.required' => 'El nombre es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.unique' => 'Este email ya está registrado',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'usu_rol.required' => 'El rol es obligatorio',
            'usu_rol.in' => 'El rol debe ser Administrador o Editor',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }

        try {
            $user = User::create([
                'usu_nombre' => $request->usu_nombre,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'usu_rol' => $request->usu_rol,
                'usu_estado' => $request->get('usu_estado', 'Activo'),
            ]);

            Log::info('Administrador creó nuevo usuario', [
                'admin_id' => $request->user()->id,
                'new_user_id' => $user->id,
                'rol' => $user->usu_rol
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'nombre' => $user->usu_nombre,
                    'email' => $user->email,
                    'rol' => $user->usu_rol,
                    'estado' => $user->usu_estado,
                ],
                'message' => 'Usuario creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear usuario: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el usuario'
            ], 500);
        }
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, User $user): JsonResponse
    {
        // No permitir que el admin se modifique a sí mismo el rol o estado
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes modificar tu propio usuario. Use el endpoint de perfil.'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'usu_nombre' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|max:150|unique:users,email,' . $user->id,
            'usu_rol' => 'sometimes|in:Administrador,Editor',
            'usu_estado' => 'sometimes|in:Activo,Pendiente,Inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }

        $user->update($validator->validated());

        Log::info('Administrador actualizó usuario', [
            'admin_id' => $request->user()->id,
            'user_id' => $user->id,
            'changes' => $validator->validated()
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'nombre' => $user->usu_nombre,
                'email' => $user->email,
                'rol' => $user->usu_rol,
                'estado' => $user->usu_estado,
            ],
            'message' => 'Usuario actualizado exitosamente'
        ], 200);
    }

    /**
     * Activar usuario
     */
    public function activate(Request $request, User $user): JsonResponse
    {
        $this->authorize('activate', $user);

        if ($user->usu_estado === 'Activo') {
            return response()->json([
                'success' => false,
                'message' => 'El usuario ya está activo'
            ], 400);
        }

        $user->update(['usu_estado' => 'Activo']);
        $user->refresh();

        Log::info('Administrador activó usuario', [
            'admin_id' => $request->user()->id,
            'user_id' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'nombre' => $user->usu_nombre,
                'email' => $user->email,
                'estado' => $user->usu_estado,
            ],
            'message' => 'Usuario activado exitosamente'
        ], 200);
    }

    /**
     * Desactivar usuario
     */
    public function deactivate(Request $request, User $user): JsonResponse
    {
        $this->authorize('deactivate', $user);

        // No permitir desactivar al mismo admin
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes desactivar tu propio usuario'
            ], 400);
        }

        if ($user->usu_estado === 'Inactivo') {
            return response()->json([
                'success' => false,
                'message' => 'El usuario ya está inactivo'
            ], 400);
        }

        $user->update(['usu_estado' => 'Inactivo']);

        // Revocar todos los tokens del usuario
        $user->tokens()->delete();

        Log::info('Administrador desactivó usuario', [
            'admin_id' => $request->user()->id,
            'user_id' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'nombre' => $user->usu_nombre,
                'email' => $user->email,
                'estado' => $user->usu_estado,
            ],
            'message' => 'Usuario desactivado exitosamente'
        ], 200);
    }

    /**
     * Cambiar rol de usuario
     */
    public function changeRole(Request $request, User $user): JsonResponse
    {
        $this->authorize('changeRole', $user);

        // No permitir cambiar el rol del mismo admin
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes cambiar tu propio rol'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'usu_rol' => 'required|in:Administrador,Editor',
        ], [
            'usu_rol.required' => 'El rol es obligatorio',
            'usu_rol.in' => 'El rol debe ser Administrador o Editor',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }

        $oldRole = $user->usu_rol;
        $user->update(['usu_rol' => $request->usu_rol]);

        // Revocar tokens para que se regeneren con nuevos permisos
        $user->tokens()->delete();

        Log::info('Administrador cambió rol de usuario', [
            'admin_id' => $request->user()->id,
            'user_id' => $user->id,
            'old_role' => $oldRole,
            'new_role' => $user->usu_rol
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'nombre' => $user->usu_nombre,
                'email' => $user->email,
                'rol' => $user->usu_rol,
                'rol_anterior' => $oldRole,
            ],
            'message' => 'Rol actualizado exitosamente. El usuario debe iniciar sesión nuevamente.'
        ], 200);
    }

    /**
     * Eliminar usuario
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        // No permitir eliminar al mismo admin
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar tu propio usuario'
            ], 400);
        }

        $userName = $user->usu_nombre;
        $userEmail = $user->email;

        // Revocar todos los tokens
        $user->tokens()->delete();

        // Eliminar usuario
        $user->delete();

        Log::warning('Usuario eliminado por administrador', [
            'admin_id' => $request->user()->id,
            'deleted_user_id' => $user->id,
            'deleted_user_name' => $userName,
            'deleted_user_email' => $userEmail
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente'
        ], 200);
    }

    /**
     * Restablecer contraseña de usuario (admin)
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $this->authorize('resetPassword', $user);

        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:8',
        ], [
            'new_password.required' => 'La nueva contraseña es obligatoria',
            'new_password.min' => 'La contraseña debe tener al menos 8 caracteres',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Revocar todos los tokens
        $user->tokens()->delete();

        Log::info('Administrador restableció contraseña de usuario', [
            'admin_id' => $request->user()->id,
            'user_id' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña restablecida exitosamente. El usuario debe iniciar sesión nuevamente.'
        ], 200);
    }

    /**
     * Estadísticas generales de usuarios
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewStatistics', User::class);
        try {
            $stats = [
                'total_usuarios' => User::count(),
                'usuarios_activos' => User::where('usu_estado', 'Activo')->count(),
                'usuarios_pendientes' => User::where('usu_estado', 'Pendiente')->count(),
                'usuarios_inactivos' => User::where('usu_estado', 'Inactivo')->count(),
                'administradores' => User::where('usu_rol', 'Administrador')->count(),
                'editores' => User::where('usu_rol', 'Editor')->count(),
                'registros_hoy' => User::whereDate('created_at', today())->count(),
                'registros_semana' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'registros_mes' => User::whereMonth('created_at', now()->month)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas obtenidas exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ], 500);
        }
    }

    /**
     * Operaciones en lote
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('bulkAction', User::class);

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,delete',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer|exists:users,id',
        ], [
            'action.required' => 'La acción es obligatoria',
            'action.in' => 'Acción inválida',
            'user_ids.required' => 'Debes seleccionar al menos un usuario',
            'user_ids.*.exists' => 'Uno o más usuarios no existen',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }

        try {
            $userIds = $request->user_ids;
            $currentUserId = $request->user()->id;

            // No permitir acciones sobre el mismo admin
            if (in_array($currentUserId, $userIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes realizar acciones sobre tu propio usuario'
                ], 400);
            }

            DB::beginTransaction();

            $affected = 0;

            switch ($request->action) {
                case 'activate':
                    $affected = User::whereIn('id', $userIds)->update(['usu_estado' => 'Activo']);
                    break;

                case 'deactivate':
                    $affected = User::whereIn('id', $userIds)->update(['usu_estado' => 'Inactivo']);
                    // Revocar tokens
                    DB::table('personal_access_tokens')->whereIn('tokenable_id', $userIds)->delete();
                    break;

                case 'delete':
                    // Revocar tokens primero
                    DB::table('personal_access_tokens')->whereIn('tokenable_id', $userIds)->delete();
                    $affected = User::whereIn('id', $userIds)->delete();
                    break;
            }

            DB::commit();

            Log::info('Operación en lote realizada', [
                'admin_id' => $currentUserId,
                'action' => $request->action,
                'user_ids' => $userIds,
                'affected' => $affected
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'affected' => $affected,
                    'action' => $request->action
                ],
                'message' => "Operación completada. {$affected} usuario(s) afectado(s)."
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en operación en lote: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la operación'
            ], 500);
        }
    }

    public function updateUserAvatar (Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        try {
            $validator = Validator::make($request->all(), [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120|dimensions:max_width=5000,max_height=5000',
            'avatar_tipo' => 'required|in:upload,preset,initials',
            'avatar_preset' => 'nullable|string|in:' . implode(',', User::getAvailablePresets())
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }
        
        $tipo = $request->avatar_tipo;

        if ($tipo === 'initials') {
            $user->updateAvatar(null, 'initials');
        } elseif ($tipo === 'upload' && $request->hasFile('avatar')) {
            $user->updateAvatar($request->file('avatar'), 'upload');
        } elseif ($tipo === 'preset' && $request->filled('avatar_preset')) {
            $user->updateAvatar($request->avatar_preset, 'preset');
        }
        
        Log::info('Admin actualizó avatar de usuario', [
            'admin_id' => $request->user()->id,
            'user_id' => $user->id,
            'tipo' => $tipo
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'avatar' => $user->fresh()->avatar_data
            ],
            'message' => 'Avatar del usuario actualizado exitosamente'
        ]);
    } catch (\Exception $e) {
        Log::error('Error al actualizar avatar de usuario: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar el avatar'
        ], 500);
    }
}
}
