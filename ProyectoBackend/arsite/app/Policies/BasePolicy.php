<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

    /**
     * Política base para todos los recursos 
     * Define los permisos según el rol del usuario y la propiedad del recurso
     */

class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Determinar si el usuario puede ver cualquier recurso
     */
    public function viewAny(User $user): bool
    {
        //Todos los usuarios autenticados pueden ver listados
        return in_array($user->usu_rol, ['Administrador', 'Editor']);
    }

    /**
     * Determinar si el usuario puede ver un recurso específico
     */
    public function view(User $user, $model): bool
    {
        // Todos los usuarios autenticados pueden ver detalles
        return in_array($user->usu_rol, ['Administrador', 'Editor']);
    }

    /**
     * Determinar si el usuario puede crear recursos
     */
    public function create(User $user): bool
    {
        // Editores y Admins pueden crear
        return in_array($user->usu_rol, ['Administrador', 'Editor']);
    }

    /**
     * Determinar si el usuario puede actualizar el recurso
     */
    public function update(User $user, $model): bool
    {
        //Administradores pueden actualizar todo
        if ($user->usu_rol === 'Administrador') {
            return true;
        }

        //Editores solo pueden actualizar sus propios recursos
        if ($user->usu_rol === 'Editor') {
            return $this->isOwner($user, $model);
        }
        return false;
    }

    /**
     * Determinar si el usuario puede eliminar el recurso
     */
    public function delete(User $user, $model): bool
    {
        //Administradores pueden eliminar todo
        if  ($user->usu_rol === 'Administrador') {
            return true;
        }

        //Editores solo pueden eliminar sus propios recursos
        if ($user->usu_rol === 'Editor') {
            return $this->isOwner($user, $model);
        }
        return false;
    }

    /**
     * Determinar si el usuario puede restaurar el recurso
     */
    public function restore(User $user, $model): bool
    {
        // Administradores pueden restaurar todo
        if  ($user->usu_rol === 'Administrador') {
            return true;
        }

        //Editores solo pueden restaurar sus propios recursos
        if ($user->usu_rol === 'Editor') {
            return $this->isOwner($user, $model);
        }
        return false;
    }

    /**
     * Determinar si el usuario puede eliminar permanentemente el recurso
     */
    public function forceDelete(User $user, $model): bool
    {
        // Solo Administradores pueden eliminar permanentemente
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Determinar si el usuario puede realizar operaciones en lote
     */
    public function bulkAction(User $user): bool
    {
        // Solo Administradores pueden hacer operaciones masivas
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Determinar si el usuario puede exportar datos
     */
    public function export(User $user): bool
    {
        // Solo Administradores pueden exportar
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Determinar si el usuario puede ver estadísticas
     */
    public function viewStatistics(User $user): bool
    {
        // Editores y Admins pueden ver estadísticas
        return in_array($user->usu_rol, ['Administrador', 'Editor']);
    }

    /**
     * Determinar si el usuario puede reordenar elementos
     */
    public function reorder(User $user): bool
    {
        // Editores y Admins pueden reordenar
        return in_array($user->usu_rol, ['Administrador', 'Editor']);
    }

    /**
     * Verificar si el usuario es dueño del recurso
     * 
     * @param User $user
     * @param mixed $model
     * @return bool
     */
    protected function isOwner(User $user, $model): bool 
    {
        //Verifica diferentes posibles nombres de columna para el creador
        $ownershipFields = [
            'user_id', 
            'created_by',
            'creado_por',
            'autor_id',
        ];
        foreach ($ownershipFields as $field) {
            if (isset($model->$field)) {
                return $model->$field == $user->id;
            }
        }
        // Si no encuentra ningún campo de propiedad, por seguridad retorna false
        // Esto evita que editores borren contenido sin ownership definido
        return false;
    }

    /**
     * Verificar si puede ver contenido de otros usuarios
     */
    public function viewOthers(User $user): bool
    {
        // Todos pueden ver todo el contenido
        return in_array($user->usu_rol, ['Administrador', 'Editor']);
    }

    /**
     * Verificar si puede modificar contenido de otros usuarios
     */
    public function updateOthers(User $user): bool
    {
        // Solo administradores pueden modificar contenido de otros
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Verificar si puede eliminar contenido de otros usuarios
     */
    public function deleteOthers(User $user): bool
    {
        // Solo administradores pueden eliminar contenido de otros
        return $user->usu_rol === 'Administrador';
    }
}
