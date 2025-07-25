<?php

namespace App\Policies;

use App\Models\User;
use App\Models\QrAbsen;
use Illuminate\Auth\Access\HandlesAuthorization;

class QrAbsenPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_qr::absen');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, QrAbsen $qrAbsen): bool
    {
        return $user->can('view_qr::absen');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_qr::absen');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, QrAbsen $qrAbsen): bool
    {
        return $user->can('update_qr::absen');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, QrAbsen $qrAbsen): bool
    {
        return $user->can('delete_qr::absen');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_qr::absen');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, QrAbsen $qrAbsen): bool
    {
        return $user->can('force_delete_qr::absen');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_qr::absen');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, QrAbsen $qrAbsen): bool
    {
        return $user->can('restore_qr::absen');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_qr::absen');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, QrAbsen $qrAbsen): bool
    {
        return $user->can('replicate_qr::absen');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_qr::absen');
    }
}
