<?php
namespace App\Policies;
use App\Models\User;
use App\Models\Journal;
use Illuminate\Auth\Access\HandlesAuthorization;

class JournalPolicy
{
    use HandlesAuthorization;
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_journal');
    }
    public function view(User $user, Journal $journal): bool
    {
        return $user->can('view_journal');
    }
    public function create(User $user): bool
    {
        return $user->can('create_journal');
    }
    public function update(User $user, Journal $journal): bool
    {
        return $user->can('update_journal');
    }
    public function delete(User $user, Journal $journal): bool
    {
        return $user->can('delete_journal');
    }
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_journal');
    }
    public function forceDelete(User $user, Journal $journal): bool
    {
        return $user->can('force_delete_journal');
    }
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_journal');
    }
    public function restore(User $user, Journal $journal): bool
    {
        return $user->can('restore_journal');
    }
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_journal');
    }
    public function replicate(User $user, Journal $journal): bool
    {
        return $user->can('replicate_journal');
    }
    public function reorder(User $user): bool
    {
        return $user->can('reorder_journal');
    }
}