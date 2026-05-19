<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Util\StringId;

final readonly class LectureEnrollment
{
    public function __construct(
        private StringId $lectureId,
        private StringId $studentId,
    ) {
    }

    public function getLectureId(): StringId
    {
        return $this->lectureId;
    }

    public function getStudentId(): StringId
    {
        return $this->studentId;
    }
}
