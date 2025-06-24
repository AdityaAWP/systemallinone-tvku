<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Intern;
use App\Models\Journal;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

class JournalPolicy
{
    use HandlesAuthorization;

    public function viewAny($user): bool
    {
        // If it's an intern, they can always view their own journals
        if ($user instanceof Intern) {
            return true;
        }
        
        // If it's a User, check permissions
        if ($user instanceof User) {
            return $user->can('view_any_journal');
        }
        
        return false;
    }

    public function view($user, Journal $journal): bool
    {
        // If it's an intern, they can only view their own journals
        if ($user instanceof Intern) {
            return $journal->intern_id === $user->id;
        }
        
        // If it's a User, check permissions
        if ($user instanceof User) {
            return $user->can('view_journal');
        }
        
        return false;
    }

    public function create($user): bool
    {
        // If it's an intern, they can create journals
        if ($user instanceof Intern) {
            return true;
        }
        
        // If it's a User, check permissions
        if ($user instanceof User) {
            return $user->can('create_journal');
        }
        
        return false;
    }

    public function update($user, Journal $journal): bool
    {
        // If it's an intern, they can only update their own journals
        if ($user instanceof Intern) {
            return $journal->intern_id === $user->id;
        }
        
        // If it's a User, check permissions
        if ($user instanceof User) {
            return $user->can('update_journal');
        }
        
        return false;
    }

    public function delete($user, Journal $journal): bool
    {
        // If it's an intern, they can only delete their own journals
        if ($user instanceof Intern) {
            return $journal->intern_id === $user->id;
        }
        
        // If it's a User, check permissions
        if ($user instanceof User) {
            return $user->can('delete_journal');
        }
        
        return false;
    }

    public function deleteAny($user): bool
    {
        // Interns typically shouldn't have bulk delete permissions
        if ($user instanceof Intern) {
            return false;
        }
        
        // If it's a User, check permissions
        if ($user instanceof User) {
            return $user->can('delete_any_journal');
        }
        
        return false;
    }

    public function forceDelete($user, Journal $journal): bool
    {
        // Interns typically shouldn't have force delete permissions
        if ($user instanceof Intern) {
            return false;
        }
        
        // If it's a User, check permissions
        if ($user instanceof User) {
            return $user->can('force_delete_journal');
        }
        
        return false;
    }

    public function forceDeleteAny($user): bool
    {
        // Interns typically shouldn't have force delete permissions
        if ($user instanceof Intern) {
            return false;
        }
        
        // If it's a User, check permissions
        if ($user instanceof User) {
            return $user->can('force_delete_any_journal');
        }
        
        return false;
    }

    public function restore($user, Journal $journal): bool
    {
        // Interns typically shouldn't have restore permissions
        if ($user instanceof Intern) {
            return false;
        }
        
        // If it's a User, check permissions
        if ($user instanceof User) {
            return $user->can('restore_journal');
        }
        
        return false;
    }

    public function restoreAny($user): bool
    {
        // Interns typically shouldn't have restore permissions
        if ($user instanceof Intern) {
            return false;
        }
        
        // If it's a User, check permissions
        if ($user instanceof User) {
            return $user->can('restore_any_journal');
        }
        
        return false;
    }

    public function replicate($user, Journal $journal): bool
    {
        // If it's an intern, they can replicate their own journals
        if ($user instanceof Intern) {
            return $journal->intern_id === $user->id;
        }
        
        // If it's a User, check permissions
        if ($user instanceof User) {
            return $user->can('replicate_journal');
        }
        
        return false;
    }

    public function reorder($user): bool
    {
        // Interns typically shouldn't have reorder permissions
        if ($user instanceof Intern) {
            return false;
        }
        
        // If it's a User, check permissions
        if ($user instanceof User) {
            return $user->can('reorder_journal');
        }
        
        return false;
    }
}