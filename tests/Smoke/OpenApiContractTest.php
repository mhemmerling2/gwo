<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Smoke;

use Gwo\AppsRecruitmentTask\Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

final class OpenApiContractTest extends ApiTestCase
{
    #[Test]
    public function openApiSpecIncludesImplementedPathsAndSecurityHeader(): void
    {
        $response = $this->makeRequest('GET', '/openapi.yaml');
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = (string) $response->getContent();
        self::assertStringContainsString('/lectures:', $content);
        self::assertStringContainsString('/lectures/{lectureId}/enrollments:', $content);
        self::assertStringContainsString('/lectures/{lectureId}/enrollments/{studentId}:', $content);
        self::assertStringContainsString('/students/me/enrollment-requests/{requestId}:', $content);
        self::assertStringContainsString('/students/me/lectures:', $content);
        self::assertStringContainsString('name: X-Api-Key', $content);
        self::assertStringContainsString('ErrorResponse', $content);
        self::assertStringContainsString('QueuedEnrollment', $content);
        self::assertStringContainsString('EnrollmentRequest', $content);
        self::assertStringContainsString('enrollment_in_progress', $content);
        self::assertStringContainsString("'409':", $content);
    }

    #[Test]
    public function createLectureResponseMatchesLectureSchemaShape(): void
    {
        $lecturer = $this->createLecturer();
        $this->persistUser($lecturer);

        $response = $this->makeJsonRequest(
            'POST',
            '/lectures',
            [
                'name' => 'OpenAPI Contract Lecture',
                'studentLimit' => 20,
                'startDate' => '2026-06-01T10:00:00+02:00',
                'endDate' => '2026-06-01T12:00:00+02:00',
            ],
            $this->authHeaders($lecturer),
        );

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $payload = $this->decodeJsonResponse($response);
        $this->assertLecturePayloadShape($payload);
    }

    #[Test]
    public function unauthorizedResponseMatchesErrorSchemaShape(): void
    {
        $response = $this->makeJsonRequest(
            'POST',
            '/lectures',
            [
                'name' => 'OpenAPI Contract Lecture',
                'studentLimit' => 20,
                'startDate' => '2026-06-01T10:00:00+02:00',
                'endDate' => '2026-06-01T12:00:00+02:00',
            ],
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $payload = $this->decodeJsonResponse($response);
        self::assertSame(['error', 'message'], array_keys($payload));
        self::assertIsString($payload['error']);
        self::assertIsString($payload['message']);
    }

    #[Test]
    public function queuedEnrollmentResponseMatchesSchemaShape(): void
    {
        $lecturer = $this->createLecturer();
        $student = $this->createStudent();
        $this->persistUser($lecturer);
        $this->persistUser($student);

        $lectureResponse = $this->makeJsonRequest(
            'POST',
            '/lectures',
            [
                'name' => 'Queued Enrollment Contract Lecture',
                'studentLimit' => 20,
                'startDate' => '2026-06-01T10:00:00+02:00',
                'endDate' => '2026-06-01T12:00:00+02:00',
            ],
            $this->authHeaders($lecturer),
        );
        $lecture = $this->decodeJsonResponse($lectureResponse);
        self::assertIsString($lecture['id']);

        $response = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($student),
        );

        self::assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $payload = $this->decodeJsonResponse($response);

        $keys = array_keys($payload);
        sort($keys);

        self::assertSame(['lectureId', 'requestId', 'status', 'studentId'], $keys);
        self::assertIsString($payload['requestId']);
        self::assertSame('queued', $payload['status']);
        self::assertIsString($payload['lectureId']);
        self::assertIsString($payload['studentId']);
    }

    #[Test]
    public function enrollmentRequestStatusResponseMatchesSchemaShape(): void
    {
        $lecturer = $this->createLecturer();
        $student = $this->createStudent();
        $this->persistUser($lecturer);
        $this->persistUser($student);

        $lectureResponse = $this->makeJsonRequest(
            'POST',
            '/lectures',
            [
                'name' => 'Enrollment Request Contract Lecture',
                'studentLimit' => 20,
                'startDate' => '2026-06-01T10:00:00+02:00',
                'endDate' => '2026-06-01T12:00:00+02:00',
            ],
            $this->authHeaders($lecturer),
        );
        $lecture = $this->decodeJsonResponse($lectureResponse);
        self::assertIsString($lecture['id']);

        $queueResponse = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($student),
        );
        $queuedEnrollment = $this->decodeJsonResponse($queueResponse);
        self::assertIsString($queuedEnrollment['requestId']);

        $statusResponse = $this->makeRequest(
            'GET',
            sprintf('/students/me/enrollment-requests/%s', $queuedEnrollment['requestId']),
            headers: $this->authHeaders($student),
        );

        self::assertSame(Response::HTTP_OK, $statusResponse->getStatusCode());
        $payload = $this->decodeJsonResponse($statusResponse);

        $keys = array_keys($payload);
        sort($keys);

        self::assertSame(
            ['createdAt', 'failureCode', 'failureMessage', 'lectureId', 'processedAt', 'requestId', 'status', 'studentId', 'updatedAt'],
            $keys,
        );
        self::assertIsString($payload['requestId']);
        self::assertSame('queued', $payload['status']);
        self::assertIsString($payload['createdAt']);
        self::assertIsString($payload['updatedAt']);
        self::assertNull($payload['processedAt']);
        self::assertNull($payload['failureCode']);
        self::assertNull($payload['failureMessage']);
    }

    /**
        * @param array<array-key, mixed> $payload
        */
    private function assertLecturePayloadShape(array $payload): void
    {
        $keys = array_keys($payload);
        sort($keys);

        self::assertSame(
            ['endDate', 'id', 'lecturerId', 'name', 'startDate', 'studentLimit'],
            $keys,
        );
        self::assertIsString($payload['id']);
        self::assertIsString($payload['lecturerId']);
        self::assertIsString($payload['name']);
        self::assertIsInt($payload['studentLimit']);
        self::assertIsString($payload['startDate']);
        self::assertIsString($payload['endDate']);
    }
}
