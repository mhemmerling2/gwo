<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Lecture;

use DateTimeImmutable;
use Gwo\AppsRecruitmentTask\Lecture\GetStudentLecturesHandler;
use Gwo\AppsRecruitmentTask\Lecture\Lecture;
use Gwo\AppsRecruitmentTask\Lecture\LectureEnrollment;
use Gwo\AppsRecruitmentTask\Lecture\LectureEnrollmentRepositoryInterface;
use Gwo\AppsRecruitmentTask\Lecture\LectureRepositoryInterface;
use Gwo\AppsRecruitmentTask\Util\StringId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GetStudentLecturesHandlerTest extends TestCase
{
    #[Test]
    public function itLoadsLecturesInBulkAndSortsThemByStartDate(): void
    {
        $earliest = new Lecture(
            id: new StringId('lecture-1'),
            lecturerId: new StringId('lecturer-1'),
            name: 'Earliest',
            studentLimit: 10,
            startDate: new DateTimeImmutable('2026-06-01T08:00:00+02:00'),
            endDate: new DateTimeImmutable('2026-06-01T10:00:00+02:00'),
        );
        $latest = new Lecture(
            id: new StringId('lecture-2'),
            lecturerId: new StringId('lecturer-1'),
            name: 'Latest',
            studentLimit: 10,
            startDate: new DateTimeImmutable('2026-06-01T12:00:00+02:00'),
            endDate: new DateTimeImmutable('2026-06-01T14:00:00+02:00'),
        );
        $middle = new Lecture(
            id: new StringId('lecture-3'),
            lecturerId: new StringId('lecturer-1'),
            name: 'Middle',
            studentLimit: 10,
            startDate: new DateTimeImmutable('2026-06-01T10:00:00+02:00'),
            endDate: new DateTimeImmutable('2026-06-01T11:00:00+02:00'),
        );

        $lectureRepository = new BulkLectureRepository([
            $latest,
            $earliest,
            $middle,
        ]);
        $enrollmentRepository = new BulkEnrollmentRepository([
            new StringId('lecture-2'),
            new StringId('lecture-3'),
            new StringId('lecture-1'),
            new StringId('missing-lecture'),
        ]);
        $handler = new GetStudentLecturesHandler($lectureRepository, $enrollmentRepository);

        $lectures = $handler(new StringId('student-1'));

        self::assertSame(0, $lectureRepository->singleLookupCount);
        self::assertSame(1, $lectureRepository->bulkLookupCount);
        self::assertSame(
            ['Earliest', 'Middle', 'Latest'],
            array_map(static fn(Lecture $lecture): string => $lecture->getName(), $lectures),
        );
    }
}

final class BulkLectureRepository implements LectureRepositoryInterface
{
    public int $singleLookupCount = 0;
    public int $bulkLookupCount = 0;

    /**
     * @param list<Lecture> $lectures
     */
    public function __construct(
        private readonly array $lectures,
    ) {
    }

    public function save(Lecture $lecture): void
    {
    }

    public function getById(StringId $id): ?Lecture
    {
        ++$this->singleLookupCount;

        foreach ($this->lectures as $lecture) {
            if ($lecture->getId()->equals($id)) {
                return $lecture;
            }
        }

        return null;
    }

    public function getByIds(array $ids): array
    {
        ++$this->bulkLookupCount;

        $idIndex = [];

        foreach ($ids as $id) {
            $idIndex[(string) $id] = true;
        }

        return array_values(array_filter(
            $this->lectures,
            static fn(Lecture $lecture): bool => isset($idIndex[(string) $lecture->getId()]),
        ));
    }
}

final readonly class BulkEnrollmentRepository implements LectureEnrollmentRepositoryInterface
{
    /**
     * @param list<StringId> $lectureIds
     */
    public function __construct(
        private array $lectureIds,
    ) {
    }

    public function save(LectureEnrollment $enrollment): bool
    {
        return true;
    }

    public function deleteByLectureAndStudent(StringId $lectureId, StringId $studentId): bool
    {
        return false;
    }

    public function existsByLectureAndStudent(StringId $lectureId, StringId $studentId): bool
    {
        return false;
    }

    public function countByLecture(StringId $lectureId): int
    {
        return 0;
    }

    /**
     * @return list<StringId>
     */
    public function getLectureIdsByStudent(StringId $studentId): array
    {
        return $this->lectureIds;
    }
}
