<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Contacto;

class ContactoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    public function view(User $user, Contacto $contacto): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    public function create(User $user): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    public function update(User $user, Contacto $contacto): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    public function delete(User $user, Contacto $contacto): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    public function markAsRead(User $user): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    public function resendNotification(User $user, Contacto $contacto): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    public function viewRecent(User $user): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    public function viewStatistics(User $user): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    public function export(User $user): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    public function bulkUpdateStatus(User $user): bool
    {
        return $user->usu_rol === 'Administrador';
    }

    public function bulkDelete(User $user): bool
    {
        return $user->usu_rol === 'Administrador';
    }
}