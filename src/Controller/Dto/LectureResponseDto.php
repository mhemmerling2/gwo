<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller\Dto;

use Gwo\AppsRecruitmentTask\Lecture\Lecture;

final readonly class LectureResponseDto implements \JsonSerializable
{
    public function __construct(
        private string $id,
        private string $lecturerId,
        private string $name,
        private int $studentLimit,
        private string $startDate,
        private string $endDate,
    ) {
    }

    public static function fromLecture(Lecture $lecture): self
    {
        return new self(
            id: (string) $lecture->getId(),
            lecturerId: (string) $lecture->getLecturerId(),
            name: $lecture->getName(),
            studentLimit: $lecture->getStudentLimit(),
            startDate: $lecture->getStartDate()->format(\DATE_ATOM),
            endDate: $lecture->getEndDate()->format(\DATE_ATOM),
        );
    }

    /**
     * @return array{
     *   id: string,
     *   lecturerId: string,
     *   name: string,
     *   studentLimit: int,
     *   startDate: string,
     *   endDate: string
     * }
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'lecturerId' => $this->lecturerId,
            'name' => $this->name,
            'studentLimit' => $this->studentLimit,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ];
    }
}
