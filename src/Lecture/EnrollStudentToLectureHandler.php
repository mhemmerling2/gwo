<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Clock\ClockInterface;
use Gwo\AppsRecruitmentTask\Util\StringId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class EnrollStudentToLectureHandler
{
    public function __construct(
        private LectureRepositoryInterface $lectureRepository,
        private LectureEnrollmentRepositoryInterface $lectureEnrollmentRepository,
        private EnrollmentRequestRepositoryInterface $enrollmentRequestRepository,
        private ClockInterface $clock,
    ) {
    }

    public function handle(StringId $lectureId, StringId $studentId): LectureEnrollment
    {
        $lecture = $this->lectureRepository->getById($lectureId);

        if ($lecture === null) {
            throw LectureEnrollmentException::lectureNotFound();
        }

        if ($lecture->getStartDate() <= $this->clock->now()) {
            throw LectureEnrollmentException::lectureStarted();
        }

        $enrollment = new LectureEnrollment(
            lectureId: $lectureId,
            studentId: $studentId,
        );

        if (!$this->lectureEnrollmentRepository->save($enrollment)) {
            if ($this->lectureEnrollmentRepository->existsByLectureAndStudent($lectureId, $studentId)) {
                throw LectureEnrollmentException::alreadyEnrolled();
            }

            if ($this->lectureEnrollmentRepository->countByLecture($lectureId) >= $lecture->getStudentLimit()) {
                throw LectureEnrollmentException::lectureFull();
            }

            throw LectureEnrollmentException::couldNotPersist();
        }

        return $enrollment;
    }

    public function __invoke(EnrollStudentToLectureCommand $command): LectureEnrollment
    {
        if ($command->requestId !== null) {
            $this->enrollmentRequestRepository->markProcessing($command->requestId);
        }

        try {
            $enrollment = $this->handle($command->lectureId, $command->studentId);
        } catch (LectureEnrollmentException $exception) {
            if ($command->requestId !== null) {
                $this->enrollmentRequestRepository->markFailed(
                    requestId: $command->requestId,
                    failureCode: $exception->getErrorCode(),
                    failureMessage: $exception->getMessage(),
                );
            }

            throw $exception;
        } catch (\Throwable $exception) {
            if ($command->requestId !== null) {
                $this->enrollmentRequestRepository->markFailed(
                    requestId: $command->requestId,
                    failureCode: \Gwo\AppsRecruitmentTask\Shared\ApiErrorCode::ENROLLMENT_ERROR,
                    failureMessage: 'Enrollment processing failed unexpectedly.',
                );
            }

            throw $exception;
        }

        if ($command->requestId !== null) {
            $this->enrollmentRequestRepository->markCompleted($command->requestId);
        }

        return $enrollment;
    }
}
