<?php

namespace App\Policies;

use App\Models\Journal;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class JournalPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        return true; // Allow all users
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, Journal $journal): bool
    {
        return true; // Allow all users
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return true; // Allow all users
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, Journal $journal): bool
    {
        return true; // Allow all users
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, Journal $journal): bool
    {
        return true; // Allow all users
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, Journal $journal): bool
    {
        return true; // Allow all users
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, Journal $journal): bool
    {
        return true; // Allow all users
    }
}