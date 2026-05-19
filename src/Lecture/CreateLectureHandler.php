<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Util\StringId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateLectureHandler
{
    public function __construct(
        private LectureRepositoryInterface $lectureRepository,
    ) {
    }

    public function handle(CreateLectureCommand $command): Lecture
    {
        if (trim($command->name) === '') {
            throw new InvalidLectureDataException('Lecture name cannot be empty.');
        }

        if ($command->studentLimit < 1) {
            throw new InvalidLectureDataException('Student limit must be greater than 0.');
        }

        if ($command->endDate <= $command->startDate) {
            throw new InvalidLectureDataException('Lecture end date must be later than start date.');
        }

        $lecture = new Lecture(
            StringId::new(),
            $command->lecturerId,
            $command->name,
            $command->studentLimit,
            $command->startDate,
            $command->endDate,
        );

        $this->lectureRepository->save($lecture);

        return $lecture;
    }

    public function __invoke(CreateLectureCommand $command): Lecture
    {
        return $this->handle($command);
    }
}
