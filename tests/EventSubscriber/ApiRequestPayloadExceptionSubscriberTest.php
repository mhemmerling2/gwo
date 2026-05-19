<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\EventSubscriber;

use Gwo\AppsRecruitmentTask\Controller\CreateLectureController;
use Gwo\AppsRecruitmentTask\EventSubscriber\ApiRequestPayloadExceptionSubscriber;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

final class ApiRequestPayloadExceptionSubscriberTest extends TestCase
{
    #[Test]
    public function itIgnoresExceptionsOutsideLectureCreateRoute(): void
    {
        $event = $this->createExceptionEvent(
            route: 'different_route',
            throwable: new BadRequestHttpException(previous: new NotEncodableValueException()),
        );

        (new ApiRequestPayloadExceptionSubscriber())->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    #[Test]
    public function itIgnoresNonBadRequestExceptions(): void
    {
        $event = $this->createExceptionEvent(
            route: CreateLectureController::ROUTE_NAME,
            throwable: new \RuntimeException('unexpected'),
        );

        (new ApiRequestPayloadExceptionSubscriber())->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    #[Test]
    public function itMapsInvalidJsonErrors(): void
    {
        $event = $this->createExceptionEvent(
            route: CreateLectureController::ROUTE_NAME,
            throwable: new BadRequestHttpException(previous: new NotEncodableValueException()),
        );

        (new ApiRequestPayloadExceptionSubscriber())->onKernelException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            [
                'error' => ApiErrorCode::INVALID_JSON->value,
                'message' => 'Request body must contain valid JSON.',
            ],
            json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    public function itMapsInvalidRequestErrors(): void
    {
        $event = $this->createExceptionEvent(
            route: CreateLectureController::ROUTE_NAME,
            throwable: new BadRequestHttpException(previous: new NotNormalizableValueException()),
        );

        (new ApiRequestPayloadExceptionSubscriber())->onKernelException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            [
                'error' => ApiErrorCode::INVALID_REQUEST->value,
                'message' => 'Request JSON must be an object.',
            ],
            json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR),
        );
    }

    private function createExceptionEvent(string $route, \Throwable $throwable): ExceptionEvent
    {
        $request = new Request();
        $request->attributes->set('_route', $route);

        /** @var HttpKernelInterface&\PHPUnit\Framework\MockObject\Stub $kernel */
        $kernel = $this->createStub(HttpKernelInterface::class);

        return new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }
}
