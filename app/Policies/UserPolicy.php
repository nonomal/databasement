<?php

namespace App\Policies;

use App\Models\User;
use App\Services\CurrentOrganization;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     * Only super admins and org admins can access the user list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     * All authenticated users can view user details.
     */
    public function view(User $user, User $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     * Super admins and org admins can create new users.
     */
    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return app(CurrentOrganization::class)->isOrgAdmin();
    }

    /**
     * Determine whether the user can update the model.
     * Super admins can update any user. Org admins can update users in their org.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $currentOrg = app(CurrentOrganization::class);

        return $currentOrg->isOrgAdmin()
            && ! $model->isSuperAdmin()
            && $model->belongsToOrganization($currentOrg->model());
    }

    /**
     * Determine whether the user can delete the model.
     * Super admins can always delete (except self/last SA).
     * Org admins can delete users who belong to only one organization.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            if ($model->isSuperAdmin() && User::where('super_admin', true)->count() === 1) {
                return false;
            }

            return true;
        }

        $currentOrg = app(CurrentOrganization::class);

        return $currentOrg->isOrgAdmin()
            && ! $model->isSuperAdmin()
            && $model->belongsToOrganization($currentOrg->model())
            && $model->organizations()->count() === 1;
    }

    /**
     * Determine whether the user can remove the model from the current organization.
     * Only available when the target belongs to more than one organization.
     */
    public function removeFromOrganization(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if ($model->organizations()->count() <= 1) {
            return false;
        }

        if (! $user->isSuperAdmin() && $model->isSuperAdmin()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return app(CurrentOrganization::class)->isOrgAdmin();
    }

    /**
     * Determine whether the user can copy the invitation link.
     * Super admins and org admins can copy invitation links for pending users.
     */
    public function copyInvitationLink(User $user, User $model): bool
    {
        if ($model->invitation_token === null) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $currentOrg = app(CurrentOrganization::class);

        return $currentOrg->isOrgAdmin()
            && ! $model->isSuperAdmin()
            && $model->belongsToOrganization($currentOrg->model());
    }

    /**
     * Determine whether the user can attach/detach users in the current org.
     * Super admins and org admins can manage org membership.
     */
    public function manageOrgMembership(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $currentOrg = app(CurrentOrganization::class);

        return $currentOrg->isOrgAdmin();
    }
}
