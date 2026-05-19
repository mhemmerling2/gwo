<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller;

use Gwo\AppsRecruitmentTask\Controller\Dto\ErrorResponseDto;
use Gwo\AppsRecruitmentTask\Lecture\LectureEnrollmentException;
use Gwo\AppsRecruitmentTask\Lecture\RemoveStudentFromLectureHandler;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Gwo\AppsRecruitmentTask\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Gwo\AppsRecruitmentTask\Util\StringId;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route(
    '/lectures/{lectureId}/enrollments/{studentId}',
    name: 'lecture_remove_student',
    methods: ['DELETE'],
    requirements: [
        'lectureId' => Requirement::UUID,
        'studentId' => Requirement::UUID,
    ],
)]
final readonly class RemoveStudentFromLectureController
{
    public function __construct(
        private RemoveStudentFromLectureHandler $removeStudentFromLectureHandler,
    ) {
    }

    public function __invoke(
        string $lectureId,
        string $studentId,
        #[CurrentUser] User $lecturer,
    ): Response {
        try {
            ($this->removeStudentFromLectureHandler)(
                lectureId: StringId::fromRoute($lectureId),
                studentId: StringId::fromRoute($studentId),
                lecturerId: $lecturer->getId(),
            );
        } catch (LectureEnrollmentException $exception) {
            return $this->errorResponse($exception);
        }

        return new Response(content: '', status: Response::HTTP_NO_CONTENT);
    }

    private function errorResponse(LectureEnrollmentException $exception): JsonResponse
    {
        $errorCode = $exception->getErrorCode();

        return new JsonResponse(
            data: new ErrorResponseDto(
                error: $errorCode,
                message: $exception->getMessage(),
            ),
            status: match ($errorCode) {
                ApiErrorCode::LECTURE_NOT_FOUND,
                ApiErrorCode::ENROLLMENT_NOT_FOUND => Response::HTTP_NOT_FOUND,
                ApiErrorCode::FORBIDDEN => Response::HTTP_FORBIDDEN,
                ApiErrorCode::LECTURE_STARTED,
                ApiErrorCode::ALREADY_ENROLLED,
                ApiErrorCode::LECTURE_FULL => Response::HTTP_CONFLICT,
                default => Response::HTTP_BAD_REQUEST,
            },
        );
    }
}
