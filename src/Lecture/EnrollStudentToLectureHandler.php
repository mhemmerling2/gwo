<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use DateTimeImmutable;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Gwo\AppsRecruitmentTask\Util\StringId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Throwable;

#[AsMessageHandler]
final readonly class EnrollStudentToLectureHandler
{
    public function __construct(
        private LectureRepositoryInterface $lectureRepository,
        private LectureEnrollmentRepositoryInterface $lectureEnrollmentRepository,
        private EnrollmentRequestRepositoryInterface $enrollmentRequestRepository,
    ) {
    }

    public function __invoke(EnrollStudentToLectureCommand $command): void
    {
        if ($command->requestId !== null) {
            $existingRequest = $this->enrollmentRequestRepository->getByIdForStudent(
                $command->requestId,
                $command->studentId,
            );

            if ($existingRequest?->getStatus() === EnrollmentRequestStatus::COMPLETED) {
                return;
            }

            $this->enrollmentRequestRepository->markProcessing($command->requestId);
        }

        try {
            $this->enroll(
                lectureId: $command->lectureId,
                studentId: $command->studentId,
                requestId: $command->requestId,
            );
        } catch (LectureEnrollmentException $exception) {
            $this->markFailedIfTracked(
                requestId: $command->requestId,
                failureCode: $exception->getErrorCode(),
                failureMessage: $exception->getMessage(),
            );

            if ($command->requestId === null) {
                throw $exception;
            }

            return;
        } catch (Throwable $exception) {
            $this->markFailedIfTracked(
                requestId: $command->requestId,
                failureCode: ApiErrorCode::ENROLLMENT_ERROR,
                failureMessage: 'Enrollment processing failed unexpectedly.',
            );

            throw new UnrecoverableMessageHandlingException(
                message: 'Enrollment processing failed unexpectedly.',
                previous: $exception,
            );
        }

        if ($command->requestId !== null) {
            $this->enrollmentRequestRepository->markCompleted($command->requestId);
        }
    }

    private function enroll(StringId $lectureId, StringId $studentId, ?StringId $requestId): void
    {
        $lecture = $this->lectureRepository->getById($lectureId);

        if ($lecture === null) {
            throw LectureEnrollmentException::lectureNotFound();
        }

        if ($lecture->getStartDate() <= new DateTimeImmutable()) {
            throw LectureEnrollmentException::lectureStarted();
        }

        $enrollment = new LectureEnrollment(
            lectureId: $lectureId,
            studentId: $studentId,
        );

        if ($this->lectureEnrollmentRepository->save($enrollment)) {
            return;
        }

        if ($this->lectureEnrollmentRepository->existsByLectureAndStudent($lectureId, $studentId)) {
            if ($requestId !== null) {
                return;
            }

            throw LectureEnrollmentException::alreadyEnrolled();
        }

        if ($this->lectureEnrollmentRepository->countByLecture($lectureId) >= $lecture->getStudentLimit()) {
            throw LectureEnrollmentException::lectureFull();
        }

        throw LectureEnrollmentException::couldNotPersist();
    }

    private function markFailedIfTracked(
        ?StringId $requestId,
        ApiErrorCode $failureCode,
        string $failureMessage,
    ): void {
        if ($requestId === null) {
            return;
        }

        $this->enrollmentRequestRepository->markFailed(
            requestId: $requestId,
            failureCode: $failureCode,
            failureMessage: $failureMessage,
        );
    }
}
