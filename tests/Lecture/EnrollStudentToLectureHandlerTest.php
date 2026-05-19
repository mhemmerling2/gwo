<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Lecture;

use Gwo\AppsRecruitmentTask\Clock\ClockInterface;
use Gwo\AppsRecruitmentTask\Lecture\EnrollmentRequest;
use Gwo\AppsRecruitmentTask\Lecture\EnrollmentRequestRepositoryInterface;
use Gwo\AppsRecruitmentTask\Lecture\EnrollStudentToLectureHandler;
use Gwo\AppsRecruitmentTask\Lecture\Lecture;
use Gwo\AppsRecruitmentTask\Lecture\LectureEnrollment;
use Gwo\AppsRecruitmentTask\Lecture\LectureEnrollmentException;
use Gwo\AppsRecruitmentTask\Lecture\LectureEnrollmentRepositoryInterface;
use Gwo\AppsRecruitmentTask\Lecture\LectureRepositoryInterface;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Gwo\AppsRecruitmentTask\Util\StringId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnrollStudentToLectureHandlerTest extends TestCase
{
    #[Test]
    public function itEnrollsStudentWhenLectureHasNotStartedYet(): void
    {
        $lecture = new Lecture(
            id: new StringId('lecture-1'),
            lecturerId: new StringId('lecturer-1'),
            name: 'Architecture Basics',
            studentLimit: 2,
            startDate: new \DateTimeImmutable('2026-06-01T10:00:00+02:00'),
            endDate: new \DateTimeImmutable('2026-06-01T12:00:00+02:00'),
        );
        $lectureRepository = new InMemoryEnrollmentLectureRepository($lecture);
        $enrollmentRepository = new InMemoryEnrollmentRepository();
        $handler = new EnrollStudentToLectureHandler(
            $lectureRepository,
            $enrollmentRepository,
            new InMemoryEnrollmentRequestRepository(),
            new FixedClock(new \DateTimeImmutable('2026-06-01T09:00:00+02:00')),
        );

        $enrollment = $handler->handle(new StringId('lecture-1'), new StringId('student-1'));

        self::assertInstanceOf(LectureEnrollment::class, $enrollment);
        self::assertTrue($enrollmentRepository->existsByLectureAndStudent(new StringId('lecture-1'), new StringId('student-1')));
        self::assertSame(1, $enrollmentRepository->countByLecture(new StringId('lecture-1')));
    }

    #[Test]
    public function itRejectsEnrollmentWhenLectureAlreadyStarted(): void
    {
        $lecture = new Lecture(
            id: new StringId('lecture-1'),
            lecturerId: new StringId('lecturer-1'),
            name: 'Architecture Basics',
            studentLimit: 2,
            startDate: new \DateTimeImmutable('2026-06-01T10:00:00+02:00'),
            endDate: new \DateTimeImmutable('2026-06-01T12:00:00+02:00'),
        );
        $handler = new EnrollStudentToLectureHandler(
            new InMemoryEnrollmentLectureRepository($lecture),
            new InMemoryEnrollmentRepository(),
            new InMemoryEnrollmentRequestRepository(),
            new FixedClock(new \DateTimeImmutable('2026-06-01T10:00:00+02:00')),
        );

        $this->expectException(LectureEnrollmentException::class);
        $this->expectExceptionMessage('Cannot enroll to a lecture that has already started.');

        $handler->handle(new StringId('lecture-1'), new StringId('student-1'));
    }

    #[Test]
    public function itRejectsWhenStudentIsAlreadyEnrolled(): void
    {
        $lecture = new Lecture(
            id: new StringId('lecture-1'),
            lecturerId: new StringId('lecturer-1'),
            name: 'Architecture Basics',
            studentLimit: 2,
            startDate: new \DateTimeImmutable('2026-06-01T10:00:00+02:00'),
            endDate: new \DateTimeImmutable('2026-06-01T12:00:00+02:00'),
        );
        $lectureRepository = new InMemoryEnrollmentLectureRepository($lecture);
        $enrollmentRepository = new InMemoryEnrollmentRepository();
        $enrollmentRepository->save(new LectureEnrollment(
            lectureId: new StringId('lecture-1'),
            studentId: new StringId('student-1'),
        ));
        $handler = new EnrollStudentToLectureHandler(
            $lectureRepository,
            $enrollmentRepository,
            new InMemoryEnrollmentRequestRepository(),
            new FixedClock(new \DateTimeImmutable('2026-06-01T09:00:00+02:00')),
        );

        try {
            $handler->handle(new StringId('lecture-1'), new StringId('student-1'));
            self::fail('Expected already enrolled exception to be thrown.');
        } catch (LectureEnrollmentException $exception) {
            self::assertSame(
                ApiErrorCode::ALREADY_ENROLLED,
                $exception->getErrorCode(),
            );
        }
    }

    #[Test]
    public function itRejectsWhenLectureStudentLimitWouldBeExceeded(): void
    {
        $lecture = new Lecture(
            id: new StringId('lecture-1'),
            lecturerId: new StringId('lecturer-1'),
            name: 'Architecture Basics',
            studentLimit: 1,
            startDate: new \DateTimeImmutable('2026-06-01T10:00:00+02:00'),
            endDate: new \DateTimeImmutable('2026-06-01T12:00:00+02:00'),
        );
        $lectureRepository = new InMemoryEnrollmentLectureRepository($lecture);
        $enrollmentRepository = new InMemoryEnrollmentRepository(
            saveResult: false,
            countByLecture: 1,
        );
        $handler = new EnrollStudentToLectureHandler(
            $lectureRepository,
            $enrollmentRepository,
            new InMemoryEnrollmentRequestRepository(),
            new FixedClock(new \DateTimeImmutable('2026-06-01T09:00:00+02:00')),
        );

        try {
            $handler->handle(new StringId('lecture-1'), new StringId('student-2'));
            self::fail('Expected lecture full exception to be thrown.');
        } catch (LectureEnrollmentException $exception) {
            self::assertSame(
                ApiErrorCode::LECTURE_FULL,
                $exception->getErrorCode(),
            );
        }
    }
}

final class FixedClock implements ClockInterface
{
    public function __construct(
        private readonly \DateTimeImmutable $now,
    ) {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}

final class InMemoryEnrollmentLectureRepository implements LectureRepositoryInterface
{
    public function __construct(
        private readonly ?Lecture $lecture,
    ) {
    }

    public function save(Lecture $lecture): void
    {
    }

    public function getById(StringId $id): ?Lecture
    {
        if ($this->lecture === null) {
            return null;
        }

        return $this->lecture->getId()->equals($id) ? $this->lecture : null;
    }

    public function getByIds(array $ids): array
    {
        if ($this->lecture === null) {
            return [];
        }

        foreach ($ids as $id) {
            if ($this->lecture->getId()->equals($id)) {
                return [$this->lecture];
            }
        }

        return [];
    }
}

final class InMemoryEnrollmentRepository implements LectureEnrollmentRepositoryInterface
{
    /** @var list<LectureEnrollment> */
    private array $enrollments = [];

    public function __construct(
        private readonly bool $saveResult = true,
        private readonly int $countByLecture = -1,
    ) {
    }

    public function save(LectureEnrollment $enrollment): bool
    {
        if (!$this->saveResult) {
            return false;
        }

        if ($this->existsByLectureAndStudent($enrollment->getLectureId(), $enrollment->getStudentId())) {
            return false;
        }

        $this->enrollments[] = $enrollment;

        return true;
    }

    public function deleteByLectureAndStudent(StringId $lectureId, StringId $studentId): bool
    {
        return false;
    }

    public function existsByLectureAndStudent(StringId $lectureId, StringId $studentId): bool
    {
        foreach ($this->enrollments as $enrollment) {
            if (
                $enrollment->getLectureId()->equals($lectureId)
                && $enrollment->getStudentId()->equals($studentId)
            ) {
                return true;
            }
        }

        return false;
    }

    public function countByLecture(StringId $lectureId): int
    {
        if ($this->countByLecture >= 0) {
            return $this->countByLecture;
        }

        return count(array_filter(
            $this->enrollments,
            static fn(LectureEnrollment $enrollment): bool => $enrollment->getLectureId()->equals($lectureId),
        ));
    }

    /**
     * @return list<StringId>
     */
    public function getLectureIdsByStudent(StringId $studentId): array
    {
        return [];
    }
}

final class InMemoryEnrollmentRequestRepository implements EnrollmentRequestRepositoryInterface
{
    public function queue(StringId $requestId, StringId $lectureId, StringId $studentId): void
    {
    }

    public function markProcessing(StringId $requestId): void
    {
    }

    public function markCompleted(StringId $requestId): void
    {
    }

    public function markFailed(StringId $requestId, ApiErrorCode $failureCode, string $failureMessage): void
    {
    }

    public function getByIdForStudent(StringId $requestId, StringId $studentId): ?EnrollmentRequest
    {
        return null;
    }
}
