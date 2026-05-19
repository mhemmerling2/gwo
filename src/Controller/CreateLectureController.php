<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller;

use Gwo\AppsRecruitmentTask\Controller\Dto\CreateLectureRequestDto;
use Gwo\AppsRecruitmentTask\Controller\Dto\ErrorResponseDto;
use Gwo\AppsRecruitmentTask\Controller\Dto\LectureResponseDto;
use Gwo\AppsRecruitmentTask\Lecture\CreateLectureHandler;
use Gwo\AppsRecruitmentTask\Lecture\InvalidLectureDataException;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Gwo\AppsRecruitmentTask\User\User;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/lectures', name: CreateLectureController::ROUTE_NAME, methods: ['POST'])]
final readonly class CreateLectureController
{
    public const ROUTE_NAME = 'lecture_create';

    public function __construct(
        private CreateLectureHandler $createLectureHandler,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] CreateLectureRequestDto $requestDto,
        #[CurrentUser] User $user,
    ): JsonResponse
    {
        try {
            $lecture = ($this->createLectureHandler)($requestDto->toCommand($user->getId()));
        } catch (InvalidLectureDataException $exception) {
            return $this->errorResponse(
                ApiErrorCode::INVALID_LECTURE_DATA,
                $exception->getMessage()
            );
        } catch (InvalidArgumentException $exception) {
            return $this->errorResponse(
                ApiErrorCode::INVALID_REQUEST,
                $exception->getMessage()
            );
        }

        return new JsonResponse(
            data: LectureResponseDto::fromLecture($lecture),
            status: Response::HTTP_CREATED,
        );
    }

    private function errorResponse(
        ApiErrorCode $error,
        string $message
    ): JsonResponse {
        return new JsonResponse(
            data: new ErrorResponseDto(error: $error, message: $message),
            status: Response::HTTP_BAD_REQUEST,
        );
    }
}
