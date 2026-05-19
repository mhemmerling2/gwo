<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\EventSubscriber;

use Gwo\AppsRecruitmentTask\EventSubscriber\ApiExceptionSubscriber;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Throwable;

final class ApiExceptionSubscriberTest extends TestCase
{
    #[Test]
    public function itIgnoresExceptionsOutsideApiRoutes(): void
    {
        $event = $this->createExceptionEvent(
            path: '/docs',
            throwable: new BadRequestHttpException(previous: new NotEncodableValueException()),
        );

        (new ApiExceptionSubscriber())->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    #[Test]
    public function itMapsInvalidArgumentExceptionsOnApiRoutes(): void
    {
        $event = $this->createExceptionEvent(
            path: '/lectures/550e8400-e29b-41d4-a716-446655440000/enrollments',
            throwable: new InvalidArgumentException('Identifier must be a valid UUID.'),
        );

        (new ApiExceptionSubscriber())->onKernelException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            [
                'error' => ApiErrorCode::INVALID_REQUEST->value,
                'message' => 'Identifier must be a valid UUID.',
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    public function itMapsInvalidJsonErrors(): void
    {
        $event = $this->createExceptionEvent(
            path: '/lectures',
            throwable: new BadRequestHttpException(previous: new NotEncodableValueException()),
        );

        (new ApiExceptionSubscriber())->onKernelException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            [
                'error' => ApiErrorCode::INVALID_JSON->value,
                'message' => 'Request body must contain valid JSON.',
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    public function itMapsInvalidRequestErrors(): void
    {
        $event = $this->createExceptionEvent(
            path: '/lectures',
            throwable: new BadRequestHttpException(previous: new NotNormalizableValueException()),
        );

        (new ApiExceptionSubscriber())->onKernelException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            [
                'error' => ApiErrorCode::INVALID_REQUEST->value,
                'message' => 'Request JSON must be an object.',
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    public function itIgnoresNonBadRequestExceptions(): void
    {
        $event = $this->createExceptionEvent(
            path: '/lectures',
            throwable: new RuntimeException('unexpected'),
        );

        (new ApiExceptionSubscriber())->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    private function createExceptionEvent(string $path, Throwable $throwable): ExceptionEvent
    {
        $request = Request::create($path, 'POST', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ]);

        /** @var HttpKernelInterface&Stub $kernel */
        $kernel = $this->createStub(HttpKernelInterface::class);

        return new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }
}
