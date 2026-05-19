<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Util\StringId;

interface LectureEnrollmentRepositoryInterface
{
    public function save(LectureEnrollment $enrollment): bool;

    public function deleteByLectureAndStudent(StringId $lectureId, StringId $studentId): bool;

    public function existsByLectureAndStudent(StringId $lectureId, StringId $studentId): bool;

    public function countByLecture(StringId $lectureId): int;

    /**
     * @return list<StringId>
     */
    public function getLectureIdsByStudent(StringId $studentId): array;
}
