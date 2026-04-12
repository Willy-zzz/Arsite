<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registrar nuevo usuario
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'usu_nombre' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'usu_rol' => 'sometimes|in:Administrador,Editor',
        ], [
            'usu_nombre.required' => 'El nombre es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Este email ya está registrado',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }

        try {
            // Solo administradores pueden crear otros administradores
            $rol = $validator->validated()['usu_rol'] ?? 'Editor';
            
            if ($rol === 'Administrador') {
                // Verificar si el usuario actual es admin (si está autenticado)
                if ($request->user() && !$request->user()->isAdmin()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para crear administradores'
                    ], 403);
                }
            }

            $user = User::create([
                'usu_nombre' => $validator->validated()['usu_nombre'],
                'email' => $validator->validated()['email'],
                'password' => Hash::make($validator->validated()['password']),
                'usu_rol' => $rol,
                'usu_estado' => 'Pendiente', // O 'Pendiente' si quieres aprobación manual
            ]);

            // Crear token automáticamente
            $abilities = $this->getAbilitiesByRole($user->usu_rol);
            $token = $user->createToken('auth-token', $abilities)->plainTextToken;

            Log::info('Nuevo usuario registrado', [
                'user_id' => $user->id,
                'email' => $user->email,
                'rol' => $user->usu_rol,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'nombre' => $user->usu_nombre,
                        'email' => $user->email,
                        'rol' => $user->usu_rol,
                        'estado' => $user->usu_estado,
                        'iniciales' => $user->initials,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'abilities' => $abilities
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al registrar usuario: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el usuario'
            ], 500);
        }
    }
    /**
     * Login del usuario (autenticación usando sesiones)
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean'
        ], [
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser una dirección válida',
            'password.required' => 'La contraseña es obligatoria'
        ]);
        if ($validator->fails()){
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }
        try {
            //Buscar usuario por email
            $user = User::where('email', $request->email)->first();

            //Verificar si el usuario existe
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las credenciales proporcionadas son incorrectas'
                ], 401);
            }

            //Verificar la contraseña
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las credenciales proporcionadas son incorrectas'
                ], 401);
            }

            //Verificar que el usuario esté activo
            if ($user->usu_estado !== 'Activo') {
                $mensajes = [
                    'Pendiente' => 'Tu cuenta está pendiente de activación.',
                    'Inactivo' => 'Tu cuenta ha sido desactivada. Contacta al administrador.'
                ];

                return response()->json([
                    'success' => false,
                    'message' => $mensajes[$user->usu_estado] ?? 'Tu cuenta no está disponible.'
                ], 403);
            }

            //Crear token con habilidades según el rol
            $abilities = $this->getAbilitiesByRole($user->usu_rol);
            $tokenName = $request->get('device_name', 'web_token');
            $token = $user->createToken($tokenName, $abilities)->plainTextToken;

            //Ocultar información sensible
            $user->makeHidden(['password', 'remember_token']);

            //Registrar login exitoso
            Log::info('Uusario inició sesión', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'nombre' => $user->usu_nombre,
                        'email' => $user->email,
                        'rol' => $user->usu_rol,
                        'estado' => $user->usu_estado,
                        'iniciales' => $user->initials,
                        'avatar' => $user->avatar_data,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => null, 
                    'abilities' => $abilities
                    ]
                ],200);
        } catch (\Exception $e) {
            Log::error('Error en login: ' . $e->getMessage(), [
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar sesión. Por favor, intenta nuevamente.'
            ], 500);
        }
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public function check(Request $request):JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'authenticated' => false,
                'message' => 'No autenticado'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'authenticated' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'nombre' => $user->usu_nombre,
                    'email' => $user->email,
                    'rol' => $user->usu_rol,
                    'estado' => $user->usu_estado,
                ]
            ]
                ], 200);
    }

    /**
     * Logout del usuario y revocar tokens
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            //Revocar el token actual
            $request->user()->currentAccessToken()->delete();

            Log::info('Usuario cerró sesión', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al cerrar sesión: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión'
            ], 500);
        }
    }

    /**
     * Logout de todos los dispositivos
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            // Revocar todos los tokens del usuario
            $tokensCount = $request->user()->tokens()->count();
            $request->user()->tokens()->delete();

            Log::info('Usuario cerró sesión en todos los dispositivos', [
                'user_id' => $request->user()->id,
                'tokens_revoked' => $tokensCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Sesión cerrada en {$tokensCount} dispositivo(s)",
                'data' => [
                    'tokens_revoked' => $tokensCount
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al cerrar todas las sesiones: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar las sesiones'
            ], 500);
        }
    }

    /**
     * Obtener el usuario autenticado
     */
    public function me (Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->makeHidden(['password', 'remember_token']);

            //Información adicional
            $data = [
                'user' => [
                    'id' => $user->id,
                    'nombre' => $user->usu_nombre,
                    'email' => $user->email,
                    'rol' => $user->usu_rol,
                    'estado' => $user->usu_estado,
                    'iniciales' => $user->initials,
                    'avatar' => $user->avatar_data,
                    'fecha_registro' => $user->created_at->format('d/m/Y'),
                ],
                'permissions' => [
                    'can_edit' => $user->canEdit(),
                    'can_delete' => $user->canDelete(),
                    'can_manage_users' => $user->canManageUsers(),
                    'can_publish' => $user->canPublish(),
                ],
                'stats' => [
                    'total_contenidos' => $user->getTotalContent(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Usuario autenticado obtenido exitosamente'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener usuario: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del usuario'
            ], 500);
        }
    }

    /**
     * Alias para compatibilidad con el método "me"
     */
    public function user(Request $request): JsonResponse
    {
        return $this->me($request);
    }

    /**
     * Actualizar perfil del usuario autenticado
     */
    public function updateProfile (Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'usu_nombre' => 'required|string|max:100',
                'email' => 'required|email|max:150|unique:users,email,' . $user->id . ',id',
            ], [
                'usu_nombre.required' => 'El nombre es obligatorio',
                'email.required' => 'El email es obligatorio',
                'email.email' => 'El email debe ser válido',
                'email.unique' => 'Este email ya está en uso'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Error de validación'
                ], 422);
            }

            $user->update($validator->validated());
            $user->makeHidden(['password', 'remember_token']);

            Log::info('Usuario actualizó su perfil', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Perfil actualizado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al actualizar perfil: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil'
            ], 500);
        }
    }

    /**
     * Cambiar contraseña del usuario autenticado
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ], [
                'current_password.required' => 'La contraseña actual es obligatoria',
                'new_password.required' => 'La nueva contraseña es obligatoria',
                'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres',
                'new_password.confirmed' => 'Las contraseñas no coinciden'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Error de validación'
                ], 422);
            }

            // Verificar contraseña actual
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ], 400);
            }

            // Actualizar contraseña
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            // Revocar todos los tokens excepto el actual
            $currentTokenId = $request->user()->currentAccessToken()->id;
            $user->tokens()->where('id', '!=', $currentTokenId)->delete();

            Log::info('Usuario cambió su contraseña', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al cambiar contraseña: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar la contraseña'
            ], 500);
        }
    }

    /**
     * Verificar token
     */
    public function verifyToken(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Token válido',
            'data' => [
                'valid' => true,
                'user_id' => $request->user()->id,
                'user_name' => $request->user()->usu_nombre,
                'user_role' => $request->user()->usu_rol
            ]
        ], 200);
    }

    /**
     * Refrescar datos del usuario
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = User::find($request->user()->id);
            $user->makeHidden(['password', 'remember_token']);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Datos actualizados'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al refrescar datos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al refrescar datos'
            ], 500);
        }
    }

    /**
     * Solicitar restablecimiento de contraseña
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ], [
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.exists' => 'No existe una cuenta con este email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            // TODO: Implementar envío de email con token de restablecimiento
            // Por ahora solo registramos la solicitud
            Log::info('Solicitud de restablecimiento de contraseña', [
                'email' => $request->email,
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un correo con instrucciones para restablecer tu contraseña'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en forgot password: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud'
            ], 500);
        }
    }

    /**
     *Actualizar avatar del usuario autenticado
     */
    public function updateAvatar(Request $request):JsonResponse
    {
        try{
            $user = $request->user();

            $validator = Validator::make($request->all(), [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120|dimensions:max_width=5000,max_height=5000',
            'avatar_tipo' => 'required|in:upload,preset,initials',
            'avatar_preset' => 'nullable|string|in:' . implode(',', User::getAvailablePresets())
        ], [
            'avatar.image' => 'El archivo debe ser una imagen',
            'avatar.max' => 'La imagen no debe superar los 2MB',
            'avatar_tipo.required' => 'El tipo de avatar es obligatorio',
            'avatar_preset.in' => 'El preset seleccionado no es válido'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Error de validación'
            ], 422);
        }

        $tipo = $request->avatar_tipo;

        //Caso 1: Volver a iniciales
        if ($tipo === 'initials'){
            $user->updateAvatar(null, 'initials');
            
            Log::info('Usuario cambió avatar a iniciales', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'avatar' => $user->avatar_data
                ],
                'message' => 'Avatar actualizado a iniciales exitosamente'
            ]);
        }

        //Caso 2: Subir imagen personalizada
        if ($tipo === 'upload') {
            if (!$request->hasFile('avatar')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe proporcionar una imagen'
                ], 400);
            }

            $user->updateAvatar($request->file('avatar'), 'upload');
            
            Log::info('Usuario subió avatar personalizado', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'avatar' => $user->fresh()->avatar_data
                ],
                'message' => 'Avatar personalizado subido exitosamente'
            ]);
        }

        //Caso 3: Seleccionar preset
        if ($tipo === 'preset'){
            if (!$request->filled('avatar_preset')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe seleccionar un preset'
                ], 400);
            }

            $user->updateAvatar($request->avatar_preset, 'preset');

            Log::info('Usuario seleccionó avatar preset', [
                'user_id' => $user->id,
                'preset' => $request->avatar_preset
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'avatar' => $user->fresh()->avatar_data
                ],
                'message' => 'Avatar preset seleccionado exitosamente'
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Error al actualizar avatar: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar el avatar'
        ], 500);
    } 
}

    /**
     * Obtener lista de avatares preset disponibles
     */
    public function getAvatarPresets(Request $request): JsonResponse
    {
        try{
            $presets = collect(User::getAvailablePresets())->map(function ($preset) {
            return [
                'name' => $preset,
                'url' => asset("avatars/presets/{$preset}")
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $presets,
            'message' => 'Presets obtenidos exitosamente'
        ]);

    } catch (\Exception $e) {
        Log::error('Error al obtener presets: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Error al obtener presets'
        ], 500);
    }
}

    /**
     * Eliminar avatar personalizado y volver a iniciales
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        try{
            $user = $request->user();
            
            if ($user->usu_avatar_tipo !== 'upload') {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay avatar personalizado para eliminar'
                ], 400);
            }
            
            $user->updateAvatar(null, 'initials');

            Log::info('Usuario eliminó su avatar personalizado', [
            'user_id' => $user->id
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'avatar' => $user->fresh()->avatar_data
            ],
            'message' => 'Avatar eliminado exitosamente'
        ]);
    
    } catch (\Exception $e) {
        Log::error('Error al eliminar avatar: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Error al eliminar el avatar'
        ], 500);
    }
}

    /**
     * Obtener tokens activos del usuario
     */
    public function tokens(Request $request): JsonResponse
    {
        try {
            $currentTokenId = $request->user()->currentAccessToken()->id;
            
            $tokens = $request->user()->tokens()->get()->map(function ($token) use ($currentTokenId) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at?->diffForHumans() ?? 'Nunca',
                    'created_at' => $token->created_at->diffForHumans(),
                    'is_current' => $token->id === $currentTokenId
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $tokens,
                'message' => 'Tokens obtenidos exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener tokens: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tokens'
            ], 500);
        }
    }

    /**
     * Revocar un token específico
     */
    public function revokeToken(Request $request, string|int $tokenId): JsonResponse
    {
        try {
            $token = $request->user()->tokens()->find($tokenId);

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token no encontrado'
                ], 404);
            }

            // No permitir revocar el token actual
            if ($token->id === $request->user()->currentAccessToken()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes revocar el token de la sesión actual. Usa logout en su lugar.'
                ], 400);
            }

            $token->delete();

            Log::info('Token revocado', [
                'user_id' => $request->user()->id,
                'token_id' => $tokenId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token revocado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al revocar token: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al revocar token'
            ], 500);
        }
    }

    /**
     * Obtener habilidades según el rol
     */
    private function getAbilitiesByRole(string $rol): array
    {
        return match($rol) {
            'Administrador' => [
                'create',
                'read',
                'update',
                'delete',
                'manage-users',
                'view-statistics',
                'export-data',
                'bulk-operations'
            ],
            'Editor' => [
                'create',
                'read',
                'update',
                'view-statistics'
            ],
            default => ['read']
        };
    }

    /**
     * Verificar habilidad específica
     */
    public function checkAbility(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ability' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $hasAbility = $request->user()->tokenCan($request->ability);

        return response()->json([
            'success' => true,
            'data' => [
                'ability' => $request->ability,
                'has_ability' => $hasAbility
            ]
        ], 200);
    }
}
