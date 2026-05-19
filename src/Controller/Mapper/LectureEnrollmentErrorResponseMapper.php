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
        return match ($exception->getErrorCode()) {
            ApiErrorCode::LECTURE_NOT_FOUND => new JsonResponse(
                data: new ErrorResponseDto(
                    error: ApiErrorCode::LECTURE_NOT_FOUND,
                    message: $exception->getMessage(),
                ),
                status: Response::HTTP_NOT_FOUND,
            ),
            ApiErrorCode::LECTURE_STARTED => new JsonResponse(
                data: new ErrorResponseDto(
                    error: ApiErrorCode::LECTURE_STARTED,
                    message: $exception->getMessage(),
                ),
                status: Response::HTTP_CONFLICT,
            ),
            ApiErrorCode::ALREADY_ENROLLED => new JsonResponse(
                data: new ErrorResponseDto(
                    error: ApiErrorCode::ALREADY_ENROLLED,
                    message: $exception->getMessage(),
                ),
                status: Response::HTTP_CONFLICT,
            ),
            ApiErrorCode::LECTURE_FULL => new JsonResponse(
                data: new ErrorResponseDto(
                    error: ApiErrorCode::LECTURE_FULL,
                    message: $exception->getMessage(),
                ),
                status: Response::HTTP_CONFLICT,
            ),
            ApiErrorCode::FORBIDDEN => new JsonResponse(
                data: new ErrorResponseDto(
                    error: ApiErrorCode::FORBIDDEN,
                    message: $exception->getMessage(),
                ),
                status: Response::HTTP_FORBIDDEN,
            ),
            ApiErrorCode::ENROLLMENT_NOT_FOUND => new JsonResponse(
                data: new ErrorResponseDto(
                    error: ApiErrorCode::ENROLLMENT_NOT_FOUND,
                    message: $exception->getMessage(),
                ),
                status: Response::HTTP_NOT_FOUND,
            ),
            default => new JsonResponse(
                data: new ErrorResponseDto(
                    error: ApiErrorCode::ENROLLMENT_ERROR,
                    message: $exception->getMessage(),
                ),
                status: Response::HTTP_BAD_REQUEST,
            ),
        };
    }
}
