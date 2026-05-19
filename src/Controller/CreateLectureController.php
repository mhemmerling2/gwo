<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller;

use Gwo\AppsRecruitmentTask\Controller\Dto\CreateLectureRequestDto;
use Gwo\AppsRecruitmentTask\Controller\Dto\ErrorResponseDto;
use Gwo\AppsRecruitmentTask\Controller\Dto\LectureResponseDto;
use Gwo\AppsRecruitmentTask\Lecture\CreateLectureCommand;
use Gwo\AppsRecruitmentTask\Lecture\InvalidLectureDataException;
use Gwo\AppsRecruitmentTask\Lecture\Lecture;
use Gwo\AppsRecruitmentTask\Messenger\CommandBus;
use Gwo\AppsRecruitmentTask\Messenger\HandlerFailedExceptionUnwrapper;
use Gwo\AppsRecruitmentTask\Messenger\HandledMessageResultExtractor;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Gwo\AppsRecruitmentTask\User\User;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/lectures', name: CreateLectureController::ROUTE_NAME, methods: ['POST'])]
final readonly class CreateLectureController
{
    public const ROUTE_NAME = 'lecture_create';

    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] CreateLectureRequestDto $requestDto,
        #[CurrentUser] User $user,
    ): JsonResponse
    {
        try {
            $lecture = $this->dispatchCreateLectureCommand($requestDto->toCommand($user->getId()));
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
        } catch (HandlerFailedException $exception) {
            $unwrappedException = HandlerFailedExceptionUnwrapper::unwrap(
                exception: $exception,
                preferredExceptionClasses: [
                    InvalidLectureDataException::class,
                    InvalidArgumentException::class,
                ],
            );

            if ($unwrappedException instanceof InvalidLectureDataException) {
                return $this->errorResponse(
                    ApiErrorCode::INVALID_LECTURE_DATA,
                    $unwrappedException->getMessage()
                );
            }

            if ($unwrappedException instanceof InvalidArgumentException) {
                return $this->errorResponse(
                    ApiErrorCode::INVALID_REQUEST,
                    $unwrappedException->getMessage()
                );
            }

            throw $exception;
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

    private function dispatchCreateLectureCommand(CreateLectureCommand $command): Lecture
    {
        $envelope = $this->commandBus->dispatch($command);
        $result = HandledMessageResultExtractor::extract($envelope);

        if (!$result instanceof Lecture) {
            throw new \RuntimeException('Create lecture handler did not return a Lecture result.');
        }

        return $result;
    }
}
