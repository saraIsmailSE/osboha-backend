<?php

namespace App\Helpers;

use App\Enums\SystemRole;
use App\Models\User;

class RoleHelper
{
    public static function canRolesManage(array $userRoles, string $targetRole): bool
    {
        $hierarchy = SystemRole::hierarchy();

        foreach ($userRoles as $role) {
            if (in_array($targetRole, $hierarchy[$role] ?? [], true)) {
                return true;
            }
        }

        return false;
    }

    public static function canUserManageByRoleName(User $user, string $targetRole): bool
    {
        $userRoles = $user->roles()->pluck('name')->toArray();
        return self::canRolesManage($userRoles, $targetRole);
    }
}
