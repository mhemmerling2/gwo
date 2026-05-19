<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Smoke;

use Gwo\AppsRecruitmentTask\Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

final class ApplicationSmokeTest extends ApiTestCase
{
    #[Test]
    public function docsUiIsAvailableWithoutAuthentication(): void
    {
        $response = $this->makeRequest('GET', '/docs');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('SwaggerUIBundle', (string) $response->getContent());
    }

    #[Test]
    public function openApiSpecIsAvailableWithoutAuthentication(): void
    {
        $response = $this->makeRequest('GET', '/openapi.yaml');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('openapi: 3.1.0', (string) $response->getContent());
    }

    #[Test]
    public function unknownRouteReturnsNotFound(): void
    {
        $response = $this->makeRequest('GET', '/definitely-missing-route');

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    #[Test]
    public function protectedEndpointRequiresAuthentication(): void
    {
        $response = $this->makeJsonRequest(
            'POST',
            '/lectures',
            $this->validLecturePayload(),
        );

        $this->assertJsonErrorResponse(
            $response,
            Response::HTTP_UNAUTHORIZED,
            'unauthorized',
            'Authentication required. Provide X-Api-Key header.',
        );
    }

    #[Test]
    public function unknownUserCannotAccessProtectedEndpoint(): void
    {
        $response = $this->makeJsonRequest(
            'POST',
            '/lectures',
            $this->validLecturePayload(),
            [
                'HTTP_X_API_KEY' => 'unknown-api-key',
            ],
        );

        $this->assertJsonErrorResponse(
            $response,
            Response::HTTP_UNAUTHORIZED,
            'unauthorized',
            'Invalid API key.',
        );
    }

    private function validLecturePayload(): array
    {
        return [
            'name' => 'Smoke Test Lecture',
            'studentLimit' => 20,
            'startDate' => '2026-06-01T10:00:00+02:00',
            'endDate' => '2026-06-01T12:00:00+02:00',
        ];
    }
}
