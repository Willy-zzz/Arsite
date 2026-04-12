<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Contacto;
use App\Models\Banner;
use App\Models\Destacado;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Partner;
use App\Models\Cliente;
use App\Models\Noticia;
use App\Models\Hito;
use App\Policies\UserPolicy;
use App\Policies\ContactoPolicy;
use App\Policies\BasePolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Las asignaciones de políticas para la aplicación.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Contacto::class => ContactoPolicy::class,
        Banner::class => BasePolicy::class,
        Destacado::class => BasePolicy::class,
        Producto::class => BasePolicy::class,
        Servicio::class => BasePolicy::class,
        Partner::class => BasePolicy::class,
        Cliente::class => BasePolicy::class,
        Noticia::class => BasePolicy::class,
        Hito::class => BasePolicy::class,
    ];

    /**
     * Registrar cualquier servicio de autenticación / autorización.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Gates adicionales para permisos globales
        
        // Gate: Solo administradores
        Gate::define('admin-only', function (User $user) {
            return $user->usu_rol === 'Administrador';
        });

        // Gate: Administradores y Editores
        Gate::define('content-manager', function (User $user) {
            return in_array($user->usu_rol, ['Administrador', 'Editor']);
        });

        // Gate: Puede eliminar
        Gate::define('can-delete', function (User $user) {
            return $user->usu_rol === 'Administrador';
        });

        // Gate: Puede hacer operaciones en lote
        Gate::define('can-bulk-action', function (User $user) {
            return $user->usu_rol === 'Administrador';
        });

        // Gate: Puede exportar datos
        Gate::define('can-export', function (User $user) {
            return $user->usu_rol === 'Administrador';
        });

        // Gate: Puede gestionar usuarios
        Gate::define('can-manage-users', function (User $user) {
            return $user->usu_rol === 'Administrador';
        });

        // Gate: Verificar si es un rol específico
        Gate::define('has-role', function (User $user, string $role) {
            return $user->usu_rol === $role;
        });

        // Gate: Verificar si tiene uno de varios roles
        Gate::define('has-any-role', function (User $user, array $roles) {
            return in_array($user->usu_rol, $roles);
        });

        // Gate: Usuario está activo
        Gate::define('is-active', function (User $user) {
            return $user->usu_estado === 'Activo';
        });

        // Gate: Puede ver estadísticas
        Gate::define('can-view-statistics', function (User $user) {
            return in_array($user->usu_rol, ['Administrador', 'Editor']);
        });
    }
}
