<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller;

use Gwo\AppsRecruitmentTask\Controller\Dto\QueuedEnrollmentResponseDto;
use Gwo\AppsRecruitmentTask\Lecture\EnrollmentRequestRepositoryInterface;
use Gwo\AppsRecruitmentTask\Lecture\EnrollStudentToLectureCommand;
use Gwo\AppsRecruitmentTask\Messenger\CommandBus;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Gwo\AppsRecruitmentTask\User\User;
use Gwo\AppsRecruitmentTask\Util\StringId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/lectures/{lectureId}/enrollments', name: 'lecture_enroll_student', methods: ['POST'])]
final readonly class EnrollStudentToLectureController
{
    public function __construct(
        private CommandBus $commandBus,
        private EnrollmentRequestRepositoryInterface $enrollmentRequestRepository,
    ) {
    }

    public function __invoke(string $lectureId, #[CurrentUser] User $student): JsonResponse
    {
        $requestId = StringId::new();
        $this->enrollmentRequestRepository->queue(
            requestId: $requestId,
            lectureId: new StringId(value: $lectureId),
            studentId: $student->getId(),
        );

        $command = new EnrollStudentToLectureCommand(
            lectureId: new StringId(value: $lectureId),
            studentId: $student->getId(),
            requestId: $requestId,
        );

        try {
            $this->commandBus->dispatch($command);
        } catch (\Throwable $exception) {
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
