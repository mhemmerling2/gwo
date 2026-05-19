<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller;

use Gwo\AppsRecruitmentTask\Controller\Dto\ErrorResponseDto;
use Gwo\AppsRecruitmentTask\Controller\Dto\QueuedEnrollmentResponseDto;
use Gwo\AppsRecruitmentTask\Lecture\EnrollmentRequestRepositoryInterface;
use Gwo\AppsRecruitmentTask\Lecture\EnrollStudentToLectureCommand;
use Gwo\AppsRecruitmentTask\Lecture\LectureEnrollmentException;
use Gwo\AppsRecruitmentTask\Lecture\LectureParticipantRepositoryInterface;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Gwo\AppsRecruitmentTask\User\User;
use Gwo\AppsRecruitmentTask\Util\StringId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Throwable;

#[Route(
    '/lectures/{lectureId}/enrollments',
    name: 'lecture_enroll_student',
    methods: ['POST'],
    requirements: ['lectureId' => Requirement::UUID],
)]
final readonly class EnrollStudentToLectureController
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private EnrollmentRequestRepositoryInterface $enrollmentRequestRepository,
        private LectureParticipantRepositoryInterface $lectureParticipantRepository,
    ) {
    }

    public function __invoke(string $lectureId, #[CurrentUser] User $student): JsonResponse
    {
        $lectureStringId = StringId::fromRoute($lectureId);

        if ($this->lectureParticipantRepository->isStudentEnrolled($lectureStringId, $student->getId())) {
            $exception = LectureEnrollmentException::alreadyEnrolled();

            return new JsonResponse(
                data: new ErrorResponseDto(
                    error: $exception->getErrorCode(),
                    message: $exception->getMessage(),
                ),
                status: Response::HTTP_CONFLICT,
            );
        }

        if ($this->enrollmentRequestRepository->hasActiveForStudentAndLecture($lectureStringId, $student->getId())) {
            return new JsonResponse(
                data: new ErrorResponseDto(
                    error: ApiErrorCode::ENROLLMENT_IN_PROGRESS,
                    message: 'An enrollment request for this lecture is already being processed.',
                ),
                status: Response::HTTP_CONFLICT,
            );
        }

        $requestId = StringId::new();
        $this->enrollmentRequestRepository->queue(
            requestId: $requestId,
            lectureId: $lectureStringId,
            studentId: $student->getId(),
        );

        $command = new EnrollStudentToLectureCommand(
            lectureId: $lectureStringId,
            studentId: $student->getId(),
            requestId: $requestId,
        );

        try {
            $this->messageBus->dispatch($command);
        } catch (Throwable $exception) {
            $this->enrollmentRequestRepository->markFailed(
                requestId: $requestId,
                failureCode: ApiErrorCode::ENROLLMENT_ERROR,
                failureMessage: 'Enrollment request could not be queued.',
            );

            throw $exception;
        }

        return new JsonResponse(
            data: QueuedEnrollmentResponseDto::fromCommand($command),
            status: Response::HTTP_ACCEPTED,
        );
    }
}
