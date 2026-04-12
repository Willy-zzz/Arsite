<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Política para gestión de usuarios
 * Solo administradores pueden gestionar usuarios
 */

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        //No puede modificarse a sí mismo mediante gestión de usuarios
        if ($user->id === $model->id) {
            return false;
        }
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        //No puede eliminarse a si mismo
        if ($user->id === $model->id) {
            return false;
        }
        return $user->usu_rol === "Administrador";
    }

    /**
     * Determine whether the user can activate an user.
     */
    public function activate(User $user, User $model): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Determine whether the user can desactivate an user.
     */
    public function deactivate(User $user, User $model): bool 
    {
        //No puede desactivarse a sí mismo
        if ($user->id === $model->id) {
            return false;
        }
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Determine whether the user can change roles.
     */
    public function changeRole(User $user, User $model): bool 
    {
        //No puede cambiar su propio rol
        if ($user->id === $model->id) {
            return false;
        }
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Determine whether the user can reset their password.
     */
    public function resetPassword(User $user, User $model): bool 
    {
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Determine whether the user can perform bulk operations
     */
    public function bulkAction(User $user): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Determine whether the user can view statistics.
     */
    public function viewStatistics(User $user): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Determine whether the user can view pending users.
     */
    public function viewPending(User $user): bool 
    {
        return $user->usu_rol === 'Administrador';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
