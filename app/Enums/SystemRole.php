<?php

namespace App\Enums;

use App\Models\User;
use Illuminate\Support\Facades\Log;

enum SystemRole: string
{
    case ADMIN = 'admin';
    case CONSULTANT = 'consultant';
    case ADVISOR = 'advisor';
    case SUPERVISOR = 'supervisor';
    case LEADER = 'leader';
    case AMBASSADOR = 'ambassador';
    case BOOK_QUALITY_TEAM = 'book_quality_team';
    case SUPPORT_LEADER = 'support_leader';
    case USER = 'user';
    case REVIEWER = 'reviewer';
    case AUDITOR = 'auditor';
    case USER_ACCEPT = 'user_accept';
    case SUPER_AUDITIR = 'super_auditer';
    case SUPER_REVIEWER = 'super_reviewer';
    case ELIGIBLE_ADMIN = 'eligible_admin';
    case MARATHON_COORDINATOR = 'marathon_coordinator';
    case MARATHON_VERIFICATION_SUPERVISOR = 'marathon_verification_supervisor';
    case MARATHON_SUPERVISOR = 'marathon_supervisor';
    case MARATHON_AMBASSADOR = 'marathon_ambassador';
    case RAMADAN_COORDINATOR = 'ramadan_coordinator';
    case RAMADAN_HADITH_CORRECTOR = 'ramadan_hadith_corrector';
    case RAMADAN_FIQH_CORRECTOR = 'ramadan_fiqh_corrector';
    case RAMADAN_TAFSEER_CORRECTOR = 'ramadan_tafseer_corrector';
    case RAMADAN_VEDIO_CORRECTOR = 'ramadan_vedio_corrector';
    case SPECIAL_CARE_COORDINATOR = 'special_care_coordinator';
    case SPECIAL_CARE_SUPERVISOR = 'special_care_supervisor';
    case SPECIAL_CARE_LEADER = 'special_care_leader';
    case COORDINATOR_OF_WITHDRAWNS_TEAM = 'coordinator_of_withdrawns_team';
    case MEMBER_OF_WITHDRAWNS_TEAM = 'member_of_withdrawns_team';
    case BOOK_QUALITY_TEAM_COORDINATOR = 'book_quality_team_coordinator';
    case BOOK_QUALITY_SUPERVISOR = 'book_quality_supervisor';
    case OSBOHA_SUPPORT_COORDINATOR = 'osboha_support_coordinator';
    case OSBOHA_SUPPORT_MEMBER = 'osboha_support_member';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(string $locale = 'ar'): string
    {
        return __('roles.' . $this->value, [], $locale);
    }

    public static function translate(string|SystemRole $role, string $locale = 'ar'): string
    {
        $r = '';
        //check if role is SystemRole
        if (is_string($role)) {
            $r = $role;
        } else {
            $r = $role->value;
        }
        return __('roles.' . $r, [], $locale);
    }

    public static function leaderRoles(): array
    {
        return [
            self::LEADER->value,
            self::SUPPORT_LEADER->value,
            self::MARATHON_SUPERVISOR->value,
            self::SPECIAL_CARE_LEADER->value,
        ];
    }

    public static function administrativeRoles(): array
    {
        return [
            self::ADMIN->value,
            self::CONSULTANT->value,
            self::ADVISOR->value,
            self::MARATHON_VERIFICATION_SUPERVISOR->value,
            self::MARATHON_COORDINATOR->value,
            self::SPECIAL_CARE_COORDINATOR->value,
        ];
    }

    public static function mainRoles(): array
    {
        //admin|consultant|advisor|supervisor|leader
        return [
            self::ADMIN->value,
            self::CONSULTANT->value,
            self::ADVISOR->value,
            self::SUPERVISOR->value,
            self::LEADER->value,
            self::AMBASSADOR->value
        ];
    }

    public static function basicHighRoles(): array
    {
        //admin|consultant|advisor|supervisor|leader
        return [
            self::ADMIN->value,
            self::CONSULTANT->value,
            self::ADVISOR->value,
            self::SUPERVISOR->value,
            self::LEADER->value,
        ];
    }

    /**
     * Return an array of the roles that are subordinate to the current role.
     * The current role is the role that this enum value represents.
     * The subordinate roles are the roles that can be managed by the current role.
     * The array is empty if the current role has no subordinates.
     *
     * @return array<int, SystemRole>
     */
    public function subordinates(): array
    {
        return match ($this) {
            self::ADMIN => [self::CONSULTANT, self::ADVISOR, self::SUPERVISOR, self::LEADER, self::SUPPORT_LEADER, self::AMBASSADOR],
            self::CONSULTANT => [self::CONSULTANT, self::ADVISOR, self::SUPERVISOR, self::LEADER, self::SUPPORT_LEADER, self::AMBASSADOR],
            self::ADVISOR => [self::SUPERVISOR, self::LEADER, self::SUPPORT_LEADER, self::AMBASSADOR],
            self::SUPERVISOR => [self::LEADER, self::SUPPORT_LEADER, self::AMBASSADOR],
            self::LEADER => [self::AMBASSADOR, self::SUPPORT_LEADER],
            self::SUPPORT_LEADER => [self::AMBASSADOR],
            default => [],
        };
    }

    /*
    !Note: We may add here another list to get the subordinates needed for the upgrade pages
     */

    /**
     * Determine if the current role can manage the given role.
     *
     * @param  SystemRole|string  $targetRole
     * @return bool
     */
    public function canRoleManage(SystemRole|string $targetRole): bool
    {
        if (is_string($targetRole)) {
            $targetRole = SystemRole::from($targetRole);
        }
        return in_array($targetRole, $this->subordinates(), true);
    }


    /**
     * Determine if any of the given roles can manage the specified target role.
     *
     * @param  array  $roles  An array of SystemRole instances.
     * @param  SystemRole|string  $targetRole  The role to be managed.
     * @return bool  True if any role can manage the target role, false otherwise.
     */

    public static function canManageRole(array $roles, SystemRole|string $targetRole): bool
    {
        foreach ($roles as $role) {
            if ($role->value === SystemRole::ADMIN->value) {
                return true;
            }

            if ($role->canRoleManage($targetRole)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the given user can manage the specified target role.
     *
     * @param  User  $user  The user to check.
     * @param  SystemRole|string  $targetRole  The role to be managed.
     * @return bool  True if the user can manage the target role, false otherwise.
     */
    public static function canUserManageRole(User $user, SystemRole|string $targetRole): bool
    {
        $roles = $user->roles()
            ->whereIn('name', SystemRole::mainRoles())
            ->pluck('name')
            ->map(fn($role) => SystemRole::from($role))
            ->toArray();
        return self::canManageRole($roles, $targetRole);
    }

    /**
     * Determine if any of the given roles can manage all of the target roles.
     *
     * @param  array  $roles  An array of role names.
     * @param  array  $targetRoles  An array of target role names.
     * @return bool  True if any role can manage all target roles, false otherwise.
     */
    public static function canManageAllRoles(array $roles, array $targetRoles): bool
    {
        foreach ($targetRoles as $targetRole) {
            if (!self::canManageRole($roles, $targetRole)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the given user can manage another user, given by their roles.
     * A user can manage another user if they have any role that can manage all of the target user's roles.
     *
     * @param  User  $user  The user to check.
     * @param  User  $targetUser  The user to be managed.
     * @return bool  True if the user can manage the target user, false otherwise.
     */
    public static function canUserManageAnotherUser(User $user, User $targetUser): bool
    {
        $userRoles = $user->roles()
            ->whereIn('name', SystemRole::mainRoles())
            ->pluck('name')
            ->map(fn($role) => SystemRole::from($role))
            ->toArray();
        $targetUserRoles = $targetUser->roles()
            ->whereIn('name', SystemRole::mainRoles())
            ->pluck('name')
            ->map(fn($role) => SystemRole::from($role))
            ->toArray();

        return self::canManageAllRoles($userRoles, $targetUserRoles);
    }


    /**
     * The hierarchy of roles. This is an associative array where the key is a role and the value is an array of roles that
     * are subordinate to the key role.
     * The key and values are strings representing role names.
     *
     * @return array
     */
    public static function hierarchy(): array
    {
        return [
            self::ADMIN->value => [self::ADMIN->value, self::CONSULTANT->value, self::ADVISOR->value, self::SUPERVISOR->value, self::LEADER->value, self::SUPPORT_LEADER->value, self::AMBASSADOR->value, self::BOOK_QUALITY_TEAM->value],
            self::CONSULTANT->value => [self::CONSULTANT->value, self::ADVISOR->value, self::SUPERVISOR->value, self::LEADER->value, self::SUPPORT_LEADER->value, self::AMBASSADOR->value],
            self::ADVISOR->value => [self::SUPERVISOR->value, self::LEADER->value, self::SUPPORT_LEADER->value, self::AMBASSADOR->value],
            self::SUPERVISOR->value => [self::LEADER->value, self::AMBASSADOR->value],
            self::LEADER->value => [self::AMBASSADOR->value],
        ];
    }
}
