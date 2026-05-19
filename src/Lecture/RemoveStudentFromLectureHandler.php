<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Util\StringId;

final readonly class RemoveStudentFromLectureHandler
{
    public function __construct(
        private LectureRepositoryInterface $lectureRepository,
        private LectureParticipantRepositoryInterface $lectureParticipantRepository,
    ) {
    }

    public function __invoke(StringId $lectureId, StringId $studentId, StringId $lecturerId): void
    {
        $lecture = $this->lectureRepository->getById($lectureId);

        if ($lecture === null) {
            throw LectureEnrollmentException::lectureNotFound();
        }

        if (!$lecture->getLecturerId()->equals($lecturerId)) {
            throw LectureEnrollmentException::forbiddenRemoval();
        }

        if (!$this->lectureParticipantRepository->removeStudent($lectureId, $studentId)) {
            throw LectureEnrollmentException::enrollmentNotFound();
        }
    }
}
