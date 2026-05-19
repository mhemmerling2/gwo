<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller\Dto;

use Gwo\AppsRecruitmentTask\Lecture\Lecture;
use JsonSerializable;
use Override;

final readonly class LectureListResponseDto implements JsonSerializable
{
    /**
     * @param list<LectureResponseDto> $lectures
     */
    public function __construct(
        private array $lectures,
    ) {
    }

    /**
     * @param array<array-key, Lecture> $lectures
     */
    public static function fromLectures(array $lectures): self
    {
        return new self(
            lectures: array_values(array_map(
                static fn(Lecture $lecture): LectureResponseDto => LectureResponseDto::fromLecture($lecture),
                $lectures,
            )),
        );
    }

    /**
     * @return list<LectureResponseDto>
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return $this->lectures;
    }
}
