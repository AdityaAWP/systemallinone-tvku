<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Tentukan apakah pengguna dapat melihat model apa pun.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Tentukan apakah pengguna dapat melihat model.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        return $user->is_admin && $user->id === $model->created_by;
    }

    /**
     * Tentukan apakah pengguna dapat membuat model.
     */
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Tentukan apakah pengguna dapat memperbarui model.
     */
    public function update(User $user, User $model): bool
    {
        // Super admin dapat memperbarui pengguna mana pun
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        // Admin biasa hanya dapat memperbarui pengguna yang mereka buat
        if ($user->is_admin && $user->id === $model->created_by) {
            return true;
        }
        
        return false;
    }

    /**
     * Tentukan apakah pengguna dapat menghapus model.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->isSuperAdmin() && $user->id !== $model->id) {
            return true;
        }
        
        if ($user->is_admin && $user->id === $model->created_by && $user->id !== $model->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Tentukan apakah pengguna dapat memulihkan model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Tentukan apakah pengguna dapat menghapus model secara permanen.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }
}