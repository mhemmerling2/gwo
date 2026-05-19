<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller;

use Gwo\AppsRecruitmentTask\Controller\Dto\EnrollmentRequestResponseDto;
use Gwo\AppsRecruitmentTask\Controller\Dto\ErrorResponseDto;
use Gwo\AppsRecruitmentTask\Lecture\EnrollmentRequestRepositoryInterface;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Gwo\AppsRecruitmentTask\User\User;
use Gwo\AppsRecruitmentTask\Util\StringId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route(
    '/students/me/enrollment-requests/{requestId}',
    name: 'student_enrollment_request_status',
    methods: ['GET'],
    requirements: ['requestId' => Requirement::UUID],
)]
final readonly class EnrollmentRequestStatusController
{
    public function __construct(
        private EnrollmentRequestRepositoryInterface $enrollmentRequestRepository,
    ) {
    }

    public function __invoke(string $requestId, #[CurrentUser] User $student): JsonResponse
    {
        $enrollmentRequest = $this->enrollmentRequestRepository->getByIdForStudent(
            requestId: StringId::fromRoute($requestId),
            studentId: $student->getId(),
        );

        if ($enrollmentRequest === null) {
            return new JsonResponse(
                data: new ErrorResponseDto(
                    error: ApiErrorCode::ENROLLMENT_REQUEST_NOT_FOUND,
                    message: 'Enrollment request not found.',
                ),
                status: Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse(
            data: EnrollmentRequestResponseDto::fromEnrollmentRequest($enrollmentRequest),
        );
    }
}
