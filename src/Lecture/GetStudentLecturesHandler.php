<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Util\StringId;

final readonly class GetStudentLecturesHandler
{
    public function __construct(
        private LectureRepositoryInterface $lectureRepository,
        private LectureParticipantRepositoryInterface $lectureParticipantRepository,
    ) {
    }

    /**
     * @return list<Lecture>
     */
    public function __invoke(StringId $studentId): array
    {
        $lectures = $this->lectureRepository->getByIds(
            $this->lectureParticipantRepository->findLectureIdsByStudent($studentId),
        );

        usort(
            $lectures,
            static fn(Lecture $left, Lecture $right): int => $left->getStartDate() <=> $right->getStartDate(),
        );

        return $lectures;
    }
}
