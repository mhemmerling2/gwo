<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Util\StringId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetStudentLecturesHandler
{
    public function __construct(
        private LectureRepositoryInterface $lectureRepository,
        private LectureEnrollmentRepositoryInterface $lectureEnrollmentRepository,
    ) {
    }

    /**
     * @return Lecture[]
     */
    public function handle(StringId $studentId): array
    {
        $lectures = $this->lectureRepository->getByIds(
            $this->lectureEnrollmentRepository->getLectureIdsByStudent($studentId),
        );

        usort(
            $lectures,
            static fn(Lecture $left, Lecture $right): int => $left->getStartDate() <=> $right->getStartDate(),
        );

        return $lectures;
    }

    /**
     * @return Lecture[]
     */
    public function __invoke(GetStudentLecturesQuery $query): array
    {
        return $this->handle($query->studentId);
    }
}
