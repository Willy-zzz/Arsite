<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\DestacadoController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ServicioController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\NoticiaController;
use App\Http\Controllers\Api\ContactoController;
use App\Http\Controllers\Api\HitoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Rutas de autenticación públicas con prefijo
Route::prefix('auth')->group(function() {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
});

//Rutas de autenticación (alias sin prefijo por compatibilidad)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
//Route::get('/check', [AuthController::class, 'check']);

//Rutas públicas con logging de consumo
Route::middleware([\App\Http\Middleware\LogPublicApiAccess::class])->group(function () {
    //Ruta pública para enviar mensaje desde el formulario de contacto
    Route::post('/contactos', [ContactoController::class, 'store']);

    // Ruta pública para obtener banners activos (carrusel)
    Route::get('/banners/public', [App\Http\Controllers\Api\BannerController::class, 'publicBanners']);

    //Rutas públicas del cms
    Route::get('clientes/public', [ClienteController::class, 'publicClientes']);
    Route::get('/partners/public', [PartnerController::class, 'publicPartners']);
    Route::get('/servicios/public', [ServicioController::class, 'publicServicios']);
    Route::get('/destacados/public', [DestacadoController::class, 'publicDestacados']);
});


//Rutas protegidas del cms
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    //Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/check', [AuthController::class, 'check']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/verify-token', [AuthController::class, 'verifyToken']);
    Route::get('/refresh', [AuthController::class, 'refresh']);
    Route::get('/tokens', [AuthController::class, 'tokens']);
    Route::delete('/tokens/{tokenId}', [AuthController::class, 'revokeToken']);
    Route::post('/check-ability', [AuthController::class, 'checkAbility']);

    //Alias para compatiblidad
    Route::post('/logout', [AuthController::class, 'logout']);
    //Route::get('/user', [AuthController::class, 'user']);
    //Route::get('/check', [AuthController::class, 'check']);

    //Rutas del avatar del usuario autenticado
    Route::post('/profile/avatar', [AuthController::class, 'updateAvatar']);
    Route::delete('/profile/avatar', [AuthController::class, 'deleteAvatar']);
    Route::get('/profile/avatar/presets', [AuthController::class, 'getAvatarPresets']);
    
    //Rutas para administradores
    //Gestión de usuarios
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/pending', [UserController::class, 'pending']);
        Route::get('/statistics', [UserController::class, 'statistics']);
        Route::put('/{user}/activate', [UserController::class, 'activate']);
        Route::put('/{user}/deactivate', [UserController::class, 'deactivate']);
        Route::put('/{id}/change-role', [UserController::class, 'changeRole']);
        Route::put('/{id}/reset-password', [UserController::class, 'resetPassword']);
        Route::post('/bulk-action', [UserController::class, 'bulkAction']);
        Route::post('/users/{user}/avatar', [UserController::class, 'updateUserAvatar']);
    });
    Route::apiResource('users', UserController::class);

    //Contactos CRUD - Policies controlan automáticamente los permisos
    Route::prefix('contactos')->group(function () {
        Route::get('/statistics', [ContactoController::class, 'statistics']);
        Route::get('/recent', [ContactoController::class, 'recent']);
        Route::get('/export', [ContactoController::class, 'export']);
        Route::put('/bulk-status', [ContactoController::class, 'bulkUpdateStatus']);
        Route::delete('/bulk-delete', [ContactoController::class, 'bulkDelete']);
        Route::put('/mark-as-read', [ContactoController::class, 'markAsRead']);
        Route::post('/{id}/resend-notification', [ContactoController::class, 'resendNotification']);
        Route::post('/{id}/reply', [ContactoController::class, 'reply']);
    });
    Route::apiResource('contactos', ContactoController::class)->except(['store']);


    //Banner CRUD
    Route::get('/banners/export', [BannerController::class, 'export']);
    Route::put('/banners/update-order', [BannerController::class, 'updateOrder']);
    Route::delete('/banners/bulk-delete', [BannerController::class, 'bulkDelete']);
    Route::apiResource('banners', BannerController::class);

    //Destacado CRUD
    Route::get('/destacados/export', [DestacadoController::class, 'export']);
    Route::put('/destacados/update-order', [DestacadoController::class, 'updateOrder']);
    Route::delete('/destacados/bulk-delete', [DestacadoController::class, 'bulkDelete']);
    Route::apiResource('destacados', DestacadoController::class);

    //Producto CRUD
    Route::get('/productos/public', [ProductoController::class, 'publicProductos']); // Sin auth
    Route::delete('/productos/bulk-delete', [ProductoController::class, 'bulkDelete']);
    Route::put('/productos/update-order', [ProductoController::class, 'updateOrder']);
    Route::get('/productos/export', [ProductoController::class, 'export']);
    Route::get('/productos/statistics', [ProductoController::class, 'statistics']);
    Route::apiResource('productos', ProductoController::class);

    //Servicio CRUD
    Route::delete('/servicios/bulk-delete', [ServicioController::class, 'bulkDelete']);
    Route::get('/servicios/export', [ServicioController::class, 'export']);
    Route::get('/servicios/statistics', [ServicioController::class, 'statistics']);
    Route::put('/servicios/update-order', [ServicioController::class, 'updateOrder']);
    Route::apiResource('servicios', ServicioController::class);

    //Partner CRUD
    Route::put('/partners/update-order', [PartnerController::class, 'updateOrder']);
    Route::get('/partners/export', [PartnerController::class, 'export']);
    Route::delete('/partners/bulk-delete', [PartnerController::class, 'bulkDelete']);
    Route::get('/partners/statistics', [PartnerController::class, 'statistics']);
    Route::put('/partners/bulk-status', [PartnerController::class, 'bulkUpdateStatus']);
    Route::apiResource('partners', PartnerController::class);

    //Cliente CRUD
    Route::put('/clientes/update-order', [ClienteController::class, 'updateOrder']);
    Route::get('/clientes/export', [ClienteController::class, 'export']);
    Route::get('/clientes/statistics', [ClienteController::class, 'statistics']);
    Route::put('/clientes/bulk-update-status', [ClienteController::class, 'bulkUpdateStatus']);
    Route::post('/clientes/bulk-delete', [ClienteController::class, 'bulkDelete']);
    Route::apiResource('clientes', ClienteController::class);

    //Noticias CRUD
    Route::delete('/noticias/bulk-delete', [NoticiaController::class, 'bulkDelete']);
    Route::get('/noticias/featured', [NoticiaController::class, 'featuredNoticias']); 
    Route::get('/noticias/public', [NoticiaController::class, 'publicNoticias']); 
    Route::get('/noticias/statistics', [NoticiaController::class, 'statistics']);
    Route::put('/noticias/bulk-status', [NoticiaController::class, 'bulkUpdateStatus']);
    Route::apiResource('noticias', NoticiaController::class);

    //Hitos CRUD
    Route::get('/hitos/public', [HitoController::class, 'publicHitos']);
    Route::get('/hitos/statistics', [HitoController::class, 'statistics']);
    Route::put('/hitos/update-order', [HitoController::class, 'updateOrder']);
    Route::put('/hitos/bulk-status', [HitoController::class, 'bulkUpdateStatus']);
    Route::delete('/hitos/bulk-delete', [HitoController::class, 'bulkDelete']);
    Route::apiResource('hitos', HitoController::class);

    //Test
    Route::get('/test', function() {
        return response()->json([
            'message' => '¡Sanctum funciona correctamente!',
            'user' => auth()->user()->usu_nombre,
            'rol' => auth()->user()->usu_rol,
        ]);
    });
});

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');
