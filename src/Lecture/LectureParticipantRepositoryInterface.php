<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Util\StringId;

interface LectureParticipantRepositoryInterface
{
    public function enroll(StringId $lectureId, StringId $studentId): bool;

    public function removeStudent(StringId $lectureId, StringId $studentId): bool;

    public function isStudentEnrolled(StringId $lectureId, StringId $studentId): bool;

    public function countStudents(StringId $lectureId): int;

    /**
     * @return list<StringId>
     */
    public function findLectureIdsByStudent(StringId $studentId): array;
}
