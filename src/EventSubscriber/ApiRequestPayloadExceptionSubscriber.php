<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\EventSubscriber;

use Gwo\AppsRecruitmentTask\Controller\CreateLectureController;
use Gwo\AppsRecruitmentTask\Controller\Dto\ErrorResponseDto;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Throwable;

final class ApiRequestPayloadExceptionSubscriber implements EventSubscriberInterface
{
    private const INVALID_JSON_MESSAGE = 'Request body must contain valid JSON.';
    private const INVALID_REQUEST_MESSAGE = 'Request JSON must be an object.';

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->attributes->get('_route') !== CreateLectureController::ROUTE_NAME) {
            return;
        }

        $exception = $event->getThrowable();

        if (!$exception instanceof BadRequestHttpException) {
            return;
        }

        if ($this->hasExceptionInChain($exception, NotEncodableValueException::class)) {
            $event->setResponse(new JsonResponse(
                data: new ErrorResponseDto(
                    error: ApiErrorCode::INVALID_JSON,
                    message: self::INVALID_JSON_MESSAGE,
                ),
                status: Response::HTTP_BAD_REQUEST,
            ));

            return;
        }

        if (
            $this->hasExceptionInChain($exception, NotNormalizableValueException::class)
            || $this->hasExceptionInChain($exception, PartialDenormalizationException::class)
        ) {
            $event->setResponse(new JsonResponse(
                data: new ErrorResponseDto(
                    error: ApiErrorCode::INVALID_REQUEST,
                    message: self::INVALID_REQUEST_MESSAGE,
                ),
                status: Response::HTTP_BAD_REQUEST,
            ));
        }
    }

    /**
     * @param class-string<Throwable> $exceptionClass
     */
    private function hasExceptionInChain(Throwable $exception, string $exceptionClass): bool
    {
        $current = $exception;

        while ($current !== null) {
            if ($current instanceof $exceptionClass) {
                return true;
            }

            $current = $current->getPrevious();
        }

        return false;
    }
}
