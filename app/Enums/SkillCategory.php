<?php

namespace App\Enums;

enum SkillCategory: string
{
    case Languages = 'languages';
    case Frameworks = 'frameworks';
    case Databases = 'databases';
    case Devops = 'devops';
    case Platform = 'platform';
    case Security = 'security';

    /**
     * @return array<string, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value', 'value');
    }
}
