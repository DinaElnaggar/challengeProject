<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function usersRead(User $user, int $organizationId): bool
    {
        return Organization::userHasPermission($user->id, $organizationId, 'users.read');
    }

    public function usersUpdate(User $user, int $organizationId): bool
    {
        return Organization::userHasPermission($user->id, $organizationId, 'users.update');
    }

    public function usersDelete(User $user, int $organizationId): bool
    {
        return Organization::userHasPermission($user->id, $organizationId, 'users.delete');
    }

    public function usersInvite(User $user, int $organizationId): bool
    {
        return Organization::userHasPermission($user->id, $organizationId, 'users.invite');
    }

    public function analyticsRead(User $user, int $organizationId): bool
    {
        return Organization::userHasPermission($user->id, $organizationId, 'analytics.read');
    }
}

