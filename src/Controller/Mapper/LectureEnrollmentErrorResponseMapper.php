<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller\Mapper;

use Gwo\AppsRecruitmentTask\Controller\Dto\ErrorResponseDto;
use Gwo\AppsRecruitmentTask\Lecture\LectureEnrollmentException;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class LectureEnrollmentErrorResponseMapper
{
    public function map(LectureEnrollmentException $exception): JsonResponse
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
