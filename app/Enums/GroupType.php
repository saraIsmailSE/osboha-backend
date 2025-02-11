<?php

namespace App\Enums;

enum GroupType: string
{
    case FOLLOWUP = 'followup';
    case ADVANCED_FOLLOWUP = 'advanced_followup';
    case SOPHISTICATED_FOLLOWUP = 'sophisticated_followup';
    case SPECIAL_CARE = 'special_care';
    case MARATHON = 'marathon';
    case ADMINISTRATION = 'administration';
    case CONSULTATION = 'consultation';
    case ADVISING = 'advising';
    case SUPERVISING = 'supervising';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function followupTeams(): array
    {
        return [
            self::FOLLOWUP->value,
            self::ADVANCED_FOLLOWUP->value,
            self::SOPHISTICATED_FOLLOWUP->value,
        ];
    }

    public static function advancedTeams(): array
    {
        return [
            self::ADVANCED_FOLLOWUP->value,
            self::SOPHISTICATED_FOLLOWUP->value,
        ];
    }

    public static function hasLeaderTeams(): array
    {
        return [
            self::FOLLOWUP->value,
            self::ADVANCED_FOLLOWUP->value,
            self::SOPHISTICATED_FOLLOWUP->value,
            self::SPECIAL_CARE->value,
        ];
    }
}
