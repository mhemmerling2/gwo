<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller\Dto;

use Gwo\AppsRecruitmentTask\Lecture\CreateLectureCommand;
use Gwo\AppsRecruitmentTask\Util\StringId;

final readonly class CreateLectureRequestDto
{
    public function __construct(
        public mixed $name = null,
        public mixed $studentLimit = null,
        public mixed $startDate = null,
        public mixed $endDate = null,
    ) {
    }

    public function toCommand(StringId $lecturerId): CreateLectureCommand
    {
        return new CreateLectureCommand(
            lecturerId: $lecturerId,
            name: self::requireString($this->name, 'name'),
            studentLimit: self::requireInt($this->studentLimit, 'studentLimit'),
            startDate: self::requireDate($this->startDate, 'startDate'),
            endDate: self::requireDate($this->endDate, 'endDate'),
        );
    }

    private static function requireString(mixed $value, string $field): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Field "%s" must be a string.', $field));
        }

        return $value;
    }

    private static function requireInt(mixed $value, string $field): int
    {
        if (!is_int($value)) {
            throw new \InvalidArgumentException(sprintf('Field "%s" must be an integer.', $field));
        }

        return $value;
    }

    private static function requireDate(mixed $value, string $field): \DateTimeImmutable
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Field "%s" must be a valid datetime string.', $field));
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new \InvalidArgumentException(sprintf('Field "%s" must be a valid datetime string.', $field));
        }
    }
}
