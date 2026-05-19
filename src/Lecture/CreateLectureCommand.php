<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use DateTimeImmutable;
use Gwo\AppsRecruitmentTask\Util\StringId;

final readonly class CreateLectureCommand
{
    public function __construct(
        public StringId $lecturerId,
        public string $name,
        public int $studentLimit,
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
    ) {
    }
}
