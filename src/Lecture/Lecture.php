<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Util\StringId;

final readonly class Lecture
{
    public function __construct(
        private StringId $id,
        private StringId $lecturerId,
        private string $name,
        private int $studentLimit,
        private \DateTimeImmutable $startDate,
        private \DateTimeImmutable $endDate,
    ) {
    }

    public function getId(): StringId
    {
        return $this->id;
    }

    public function getLecturerId(): StringId
    {
        return $this->lecturerId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStudentLimit(): int
    {
        return $this->studentLimit;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }
}
