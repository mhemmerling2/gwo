<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Lecture;

use Gwo\AppsRecruitmentTask\Lecture\CreateLectureCommand;
use Gwo\AppsRecruitmentTask\Lecture\CreateLectureHandler;
use Gwo\AppsRecruitmentTask\Lecture\InvalidLectureDataException;
use Gwo\AppsRecruitmentTask\Lecture\Lecture;
use Gwo\AppsRecruitmentTask\Lecture\LectureRepositoryInterface;
use Gwo\AppsRecruitmentTask\Util\StringId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CreateLectureHandlerTest extends TestCase
{
    #[Test]
    public function itCreatesAndPersistsLecture(): void
    {
        $repository = new InMemoryLectureRepository();
        $handler = new CreateLectureHandler($repository);

        $lecture = $handler->handle(new CreateLectureCommand(
            lecturerId: new StringId('lecturer-1'),
            name: 'Architecture Basics',
            studentLimit: 30,
            startDate: new \DateTimeImmutable('2026-06-01T10:00:00+02:00'),
            endDate: new \DateTimeImmutable('2026-06-01T12:00:00+02:00'),
        ));

        self::assertSame($lecture, $repository->savedLecture);
        self::assertSame('Architecture Basics', $lecture->getName());
        self::assertSame(30, $lecture->getStudentLimit());
        self::assertTrue($lecture->getLecturerId()->equals(new StringId('lecturer-1')));
        self::assertSame('2026-06-01T10:00:00+02:00', $lecture->getStartDate()->format(\DATE_ATOM));
        self::assertSame('2026-06-01T12:00:00+02:00', $lecture->getEndDate()->format(\DATE_ATOM));
    }

    #[Test]
    public function itRejectsBlankLectureName(): void
    {
        $handler = new CreateLectureHandler(new InMemoryLectureRepository());

        $this->expectException(InvalidLectureDataException::class);
        $this->expectExceptionMessage('Lecture name cannot be empty.');

        $handler->handle(new CreateLectureCommand(
            lecturerId: new StringId('lecturer-1'),
            name: '   ',
            studentLimit: 30,
            startDate: new \DateTimeImmutable('2026-06-01T10:00:00+02:00'),
            endDate: new \DateTimeImmutable('2026-06-01T12:00:00+02:00'),
        ));
    }

    #[Test]
    #[DataProvider('invalidStudentLimits')]
    public function itRejectsInvalidStudentLimit(int $studentLimit): void
    {
        $handler = new CreateLectureHandler(new InMemoryLectureRepository());

        $this->expectException(InvalidLectureDataException::class);
        $this->expectExceptionMessage('Student limit must be greater than 0.');

        $handler->handle(new CreateLectureCommand(
            lecturerId: new StringId('lecturer-1'),
            name: 'Architecture Basics',
            studentLimit: $studentLimit,
            startDate: new \DateTimeImmutable('2026-06-01T10:00:00+02:00'),
            endDate: new \DateTimeImmutable('2026-06-01T12:00:00+02:00'),
        ));
    }

    #[Test]
    #[DataProvider('invalidLectureDates')]
    public function itRejectsWhenEndDateIsNotLaterThanStartDate(string $startDate, string $endDate): void
    {
        $handler = new CreateLectureHandler(new InMemoryLectureRepository());

        $this->expectException(InvalidLectureDataException::class);
        $this->expectExceptionMessage('Lecture end date must be later than start date.');

        $handler->handle(new CreateLectureCommand(
            lecturerId: new StringId('lecturer-1'),
            name: 'Architecture Basics',
            studentLimit: 30,
            startDate: new \DateTimeImmutable($startDate),
            endDate: new \DateTimeImmutable($endDate),
        ));
    }

    public static function invalidStudentLimits(): array
    {
        return [
            'zero' => [0],
            'negative' => [-5],
        ];
    }

    public static function invalidLectureDates(): array
    {
        return [
            'same instant' => ['2026-06-01T10:00:00+02:00', '2026-06-01T10:00:00+02:00'],
            'earlier end' => ['2026-06-01T10:00:00+02:00', '2026-06-01T09:00:00+02:00'],
        ];
    }
}

final class InMemoryLectureRepository implements LectureRepositoryInterface
{
    public ?Lecture $savedLecture = null;

    public function save(Lecture $lecture): void
    {
        $this->savedLecture = $lecture;
    }

    public function getById(StringId $id): ?Lecture
    {
        if ($this->savedLecture === null) {
            return null;
        }

        return $this->savedLecture->getId()->equals($id) ? $this->savedLecture : null;
    }

    public function getByIds(array $ids): array
    {
        if ($this->savedLecture === null) {
            return [];
        }

        foreach ($ids as $id) {
            if ($this->savedLecture->getId()->equals($id)) {
                return [$this->savedLecture];
            }
        }

        return [];
    }
}
