<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\User;

enum UserRole: string
{
    case LECTURER = 'lecturer';
    case STUDENT = 'student';

    public function toSecurityRole(): string
    {
        return match ($this) {
            self::LECTURER => 'ROLE_LECTURER',
            self::STUDENT => 'ROLE_STUDENT',
        };
    }
}
