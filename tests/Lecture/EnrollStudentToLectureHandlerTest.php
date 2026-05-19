<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Lecture;

use DateTimeImmutable;
use Gwo\AppsRecruitmentTask\Lecture\EnrollmentRequest;
use Gwo\AppsRecruitmentTask\Lecture\EnrollmentRequestRepositoryInterface;
use Gwo\AppsRecruitmentTask\Lecture\EnrollmentRequestStatus;
use Gwo\AppsRecruitmentTask\Lecture\EnrollStudentToLectureCommand;
use Gwo\AppsRecruitmentTask\Lecture\EnrollStudentToLectureHandler;
use Gwo\AppsRecruitmentTask\Lecture\Lecture;
use Gwo\AppsRecruitmentTask\Lecture\LectureEnrollmentException;
use Gwo\AppsRecruitmentTask\Lecture\LectureParticipantRepositoryInterface;
use Gwo\AppsRecruitmentTask\Lecture\LectureRepositoryInterface;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Gwo\AppsRecruitmentTask\Util\StringId;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnrollStudentToLectureHandlerTest extends TestCase
{
    #[Test]
    public function itEnrollsStudentWhenLectureHasNotStartedYet(): void
    {
        $startDate = new DateTimeImmutable('+1 day');
        $endDate = $startDate->modify('+2 hours');
        $lecture = new Lecture(
            id: new StringId('lecture-1'),
            lecturerId: new StringId('lecturer-1'),
            name: 'Architecture Basics',
            studentLimit: 2,
            startDate: $startDate,
            endDate: $endDate,
        );
        $lectureRepository = new InMemoryEnrollmentLectureRepository($lecture);
        $enrollmentRepository = new InMemoryParticipantRepository();
        $handler = new EnrollStudentToLectureHandler(
            $lectureRepository,
            $enrollmentRepository,
            new InMemoryEnrollmentRequestRepository(),
        );

        $handler(new EnrollStudentToLectureCommand(
            lectureId: new StringId('lecture-1'),
            studentId: new StringId('student-1'),
        ));

        self::assertTrue($enrollmentRepository->isStudentEnrolled(new StringId('lecture-1'), new StringId('student-1')));
        self::assertSame(1, $enrollmentRepository->countStudents(new StringId('lecture-1')));
    }

    #[Test]
    public function itRejectsEnrollmentWhenLectureAlreadyStarted(): void
    {
        $startDate = new DateTimeImmutable('-1 hour');
        $endDate = $startDate->modify('+2 hours');
        $lecture = new Lecture(
            id: new StringId('lecture-1'),
            lecturerId: new StringId('lecturer-1'),
            name: 'Architecture Basics',
            studentLimit: 2,
            startDate: $startDate,
            endDate: $endDate,
        );
        $handler = new EnrollStudentToLectureHandler(
            new InMemoryEnrollmentLectureRepository($lecture),
            new InMemoryParticipantRepository(),
            new InMemoryEnrollmentRequestRepository(),
        );

        $this->expectException(LectureEnrollmentException::class);
        $this->expectExceptionMessage('Cannot enroll to a lecture that has already started.');

        $handler(new EnrollStudentToLectureCommand(
            lectureId: new StringId('lecture-1'),
            studentId: new StringId('student-1'),
        ));
    }

    #[Test]
    public function itRejectsWhenStudentIsAlreadyEnrolled(): void
    {
        $startDate = new DateTimeImmutable('+1 day');
        $endDate = $startDate->modify('+2 hours');
        $lecture = new Lecture(
            id: new StringId('lecture-1'),
            lecturerId: new StringId('lecturer-1'),
            name: 'Architecture Basics',
            studentLimit: 2,
            startDate: $startDate,
            endDate: $endDate,
        );
        $lectureRepository = new InMemoryEnrollmentLectureRepository($lecture);
        $enrollmentRepository = new InMemoryParticipantRepository();
        $enrollmentRepository->enroll(new StringId('lecture-1'), new StringId('student-1'));
        $handler = new EnrollStudentToLectureHandler(
            $lectureRepository,
            $enrollmentRepository,
            new InMemoryEnrollmentRequestRepository(),
        );

        try {
            $handler(new EnrollStudentToLectureCommand(
                lectureId: new StringId('lecture-1'),
                studentId: new StringId('student-1'),
            ));
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
        $startDate = new DateTimeImmutable('+1 day');
        $endDate = $startDate->modify('+2 hours');
        $lecture = new Lecture(
            id: new StringId('lecture-1'),
            lecturerId: new StringId('lecturer-1'),
            name: 'Architecture Basics',
            studentLimit: 1,
            startDate: $startDate,
            endDate: $endDate,
        );
        $lectureRepository = new InMemoryEnrollmentLectureRepository($lecture);
        $enrollmentRepository = new InMemoryParticipantRepository(
            enrollResult: false,
            countStudents: 1,
        );
        $handler = new EnrollStudentToLectureHandler(
            $lectureRepository,
            $enrollmentRepository,
            new InMemoryEnrollmentRequestRepository(),
        );

        try {
            $handler(new EnrollStudentToLectureCommand(
                lectureId: new StringId('lecture-1'),
                studentId: new StringId('student-2'),
            ));
            self::fail('Expected lecture full exception to be thrown.');
        } catch (LectureEnrollmentException $exception) {
            self::assertSame(
                ApiErrorCode::LECTURE_FULL,
                $exception->getErrorCode(),
            );
        }
    }

    #[Test]
    public function itCompletesTrackedRequestWhenStudentIsAlreadyEnrolled(): void
    {
        $startDate = new DateTimeImmutable('+1 day');
        $endDate = $startDate->modify('+2 hours');
        $lecture = new Lecture(
            id: new StringId('lecture-1'),
            lecturerId: new StringId('lecturer-1'),
            name: 'Architecture Basics',
            studentLimit: 2,
            startDate: $startDate,
            endDate: $endDate,
        );
        $enrollmentRepository = new InMemoryParticipantRepository();
        $enrollmentRepository->enroll(new StringId('lecture-1'), new StringId('student-1'));
        $requestRepository = new TrackingEnrollmentRequestRepository();
        $handler = new EnrollStudentToLectureHandler(
            new InMemoryEnrollmentLectureRepository($lecture),
            $enrollmentRepository,
            $requestRepository,
        );

        $handler(new EnrollStudentToLectureCommand(
            lectureId: new StringId('lecture-1'),
            studentId: new StringId('student-1'),
            requestId: new StringId('request-1'),
        ));

        self::assertSame(EnrollmentRequestStatus::COMPLETED, $requestRepository->lastStatus);
    }
}

final class InMemoryEnrollmentLectureRepository implements LectureRepositoryInterface
{
    public function __construct(
        private readonly ?Lecture $lecture,
    ) {
    }

    #[Override]
    public function save(Lecture $lecture): void
    {
    }

    #[Override]
    public function getById(StringId $id): ?Lecture
    {
        if ($this->lecture === null) {
            return null;
        }

        return $this->lecture->getId()->equals($id) ? $this->lecture : null;
    }

    /**
     * @param list<StringId> $ids
     * @return list<Lecture>
     */
    #[Override]
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

final class InMemoryParticipantRepository implements LectureParticipantRepositoryInterface
{
    /** @var array<string, array<string, true>> */
    private array $enrollmentsByLecture = [];

    public function __construct(
        private readonly bool $enrollResult = true,
        private readonly int $countStudents = -1,
    ) {
    }

    #[Override]
    public function enroll(StringId $lectureId, StringId $studentId): bool
    {
        if (!$this->enrollResult) {
            return false;
        }

        if ($this->isStudentEnrolled($lectureId, $studentId)) {
            return false;
        }

        $this->enrollmentsByLecture[(string) $lectureId][(string) $studentId] = true;

        return true;
    }

    #[Override]
    public function removeStudent(StringId $lectureId, StringId $studentId): bool
    {
        return false;
    }

    #[Override]
    public function isStudentEnrolled(StringId $lectureId, StringId $studentId): bool
    {
        return isset($this->enrollmentsByLecture[(string) $lectureId][(string) $studentId]);
    }

    #[Override]
    public function countStudents(StringId $lectureId): int
    {
        if ($this->countStudents >= 0) {
            return $this->countStudents;
        }

        return count($this->enrollmentsByLecture[(string) $lectureId] ?? []);
    }

    /**
     * @return list<StringId>
     */
    #[Override]
    public function findLectureIdsByStudent(StringId $studentId): array
    {
        return [];
    }
}

final class InMemoryEnrollmentRequestRepository implements EnrollmentRequestRepositoryInterface
{
    #[Override]
    public function queue(StringId $requestId, StringId $lectureId, StringId $studentId): void
    {
    }

    #[Override]
    public function markProcessing(StringId $requestId): void
    {
    }

    #[Override]
    public function markCompleted(StringId $requestId): void
    {
    }

    #[Override]
    public function markFailed(StringId $requestId, ApiErrorCode $failureCode, string $failureMessage): void
    {
    }

    #[Override]
    public function getByIdForStudent(StringId $requestId, StringId $studentId): ?EnrollmentRequest
    {
        return null;
    }

    #[Override]
    public function hasActiveForStudentAndLecture(StringId $lectureId, StringId $studentId): bool
    {
        return false;
    }
}

final class TrackingEnrollmentRequestRepository implements EnrollmentRequestRepositoryInterface
{
    public ?EnrollmentRequestStatus $lastStatus = null;

    #[Override]
    public function queue(StringId $requestId, StringId $lectureId, StringId $studentId): void
    {
    }

    #[Override]
    public function markProcessing(StringId $requestId): void
    {
        $this->lastStatus = EnrollmentRequestStatus::PROCESSING;
    }

    #[Override]
    public function markCompleted(StringId $requestId): void
    {
        $this->lastStatus = EnrollmentRequestStatus::COMPLETED;
    }

    #[Override]
    public function markFailed(StringId $requestId, ApiErrorCode $failureCode, string $failureMessage): void
    {
        $this->lastStatus = EnrollmentRequestStatus::FAILED;
    }

    #[Override]
    public function getByIdForStudent(StringId $requestId, StringId $studentId): ?EnrollmentRequest
    {
        return null;
    }

    #[Override]
    public function hasActiveForStudentAndLecture(StringId $lectureId, StringId $studentId): bool
    {
        return false;
    }
}
