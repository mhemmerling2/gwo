<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Util\StringId;

final readonly class EnrollStudentToLectureCommand
{
    public function __construct(
        public StringId $lectureId,
        public StringId $studentId,
        public ?StringId $requestId = null,
    ) {
    }
}
