<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Determine whether the user can view any organizations.
     * Only super admins can view the organizations management page.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create organizations.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the organization.
     * Super admins can rename non-main orgs.
     */
    public function update(User $user, Organization $organization): bool
    {
        if (! $user->isSuperAdmin()) {
            return false;
        }

        // Default org name cannot be changed
        return ! $organization->is_default;
    }

    /**
     * Determine whether the user can delete the organization.
     * Only super admins can delete non-default orgs.
     */
    public function delete(User $user, Organization $organization): bool
    {
        if (! $user->isSuperAdmin()) {
            return false;
        }

        return ! $organization->is_default;
    }
}
