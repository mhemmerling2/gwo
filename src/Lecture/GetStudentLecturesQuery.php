<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Util\StringId;

final readonly class GetStudentLecturesQuery
{
    public function __construct(
        public StringId $studentId,
    ) {
    }
}
