<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Lecture;

use Gwo\AppsRecruitmentTask\Lecture\LectureRepositoryInterface;
use Gwo\AppsRecruitmentTask\Tests\ApiTestCase;
use Gwo\AppsRecruitmentTask\User\User;
use Gwo\AppsRecruitmentTask\Util\StringId;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

final class LectureTest extends ApiTestCase
{
    #[Test]
    public function lecturerCanCreateNewLecture(): void
    {
        $lecturer = $this->createLecturer();
        $this->persistUser($lecturer);

        $response = $this->makeJsonRequest(
            'POST',
            '/lectures',
            $this->validLecturePayload(),
            $this->authHeaders($lecturer),
        );

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());

        $payload = $this->decodeJsonResponse($response);

        self::assertArrayHasKey('id', $payload);
        self::assertSame((string) $lecturer->getId(), $payload['lecturerId']);
        self::assertSame('Distributed Systems 101', $payload['name']);
        self::assertSame(25, $payload['studentLimit']);
        self::assertSame('2026-06-01T10:00:00+02:00', $payload['startDate']);
        self::assertSame('2026-06-01T12:00:00+02:00', $payload['endDate']);

        /** @var LectureRepositoryInterface $lectureRepository */
        $lectureRepository = $this->httpClient->getContainer()->get(LectureRepositoryInterface::class);
        $savedLecture = $lectureRepository->getById(new StringId($payload['id']));

        self::assertNotNull($savedLecture);
        self::assertSame('Distributed Systems 101', $savedLecture->getName());
        self::assertSame(25, $savedLecture->getStudentLimit());
        self::assertTrue($savedLecture->getLecturerId()->equals($lecturer->getId()));

        $storedLecture = $this->mongoClient()
            ->selectCollection($this->databaseName(), 'lectures')
            ->findOne(['id' => $payload['id']]);

        self::assertNotNull($storedLecture);
        self::assertInstanceOf(UTCDateTime::class, $storedLecture['startDate'] ?? null);
        self::assertInstanceOf(UTCDateTime::class, $storedLecture['endDate'] ?? null);
    }

    #[Test]
    public function studentCannotCreateNewLecture(): void
    {
        $student = $this->createStudent();
        $this->persistUser($student);

        $response = $this->makeJsonRequest(
            'POST',
            '/lectures',
            $this->validLecturePayload(),
            $this->authHeaders($student),
        );

        $this->assertJsonErrorResponse(
            $response,
            Response::HTTP_FORBIDDEN,
            'forbidden',
            'Access denied. Insufficient permissions.',
        );
    }

    #[Test]
    public function lecturerCannotEnrollToLecture(): void
    {
        $lecturer = $this->createLecturer();
        $this->persistUser($lecturer);

        $lecture = $this->createLecture($lecturer);

        $response = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($lecturer),
        );

        $this->assertJsonErrorResponse(
            $response,
            Response::HTTP_FORBIDDEN,
            'forbidden',
            'Access denied. Insufficient permissions.',
        );
    }

    #[Test]
    public function requestWithoutAuthenticationCannotCreateNewLecture(): void
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
    public function unknownUserCannotCreateNewLecture(): void
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

    #[Test]
    public function lecturerCannotCreateNewLectureWithInvalidJson(): void
    {
        $lecturer = $this->createLecturer();
        $this->persistUser($lecturer);

        $response = $this->makeJsonRequest(
            'POST',
            '/lectures',
            '{"name":',
            $this->authHeaders($lecturer),
        );

        $this->assertJsonErrorResponse(
            $response,
            Response::HTTP_BAD_REQUEST,
            'invalid_json',
            'Request body must contain valid JSON.',
        );
    }

    #[Test]
    public function lecturerCannotCreateNewLectureWithoutRequiredName(): void
    {
        $lecturer = $this->createLecturer();
        $this->persistUser($lecturer);

        $payload = $this->validLecturePayload();
        unset($payload['name']);

        $response = $this->makeJsonRequest(
            'POST',
            '/lectures',
            $payload,
            $this->authHeaders($lecturer),
        );

        $this->assertJsonErrorResponse(
            $response,
            Response::HTTP_BAD_REQUEST,
            'invalid_request',
            'Field "name" must be a string.',
        );
    }

    #[Test]
    public function lecturerCannotCreateNewLectureFromScalarJsonPayload(): void
    {
        $lecturer = $this->createLecturer();
        $this->persistUser($lecturer);

        $response = $this->makeJsonRequest(
            'POST',
            '/lectures',
            '123',
            $this->authHeaders($lecturer),
        );

        $this->assertJsonErrorResponse(
            $response,
            Response::HTTP_BAD_REQUEST,
            'invalid_request',
            'Field "name" must be a string.',
        );
    }

    #[Test]
    public function lecturerCannotCreateNewLectureWithNonIntegerStudentLimit(): void
    {
        $lecturer = $this->createLecturer();
        $this->persistUser($lecturer);

        $response = $this->makeJsonRequest(
            'POST',
            '/lectures',
            [
                ...$this->validLecturePayload(),
                'studentLimit' => '25',
            ],
            $this->authHeaders($lecturer),
        );

        $this->assertJsonErrorResponse(
            $response,
            Response::HTTP_BAD_REQUEST,
            'invalid_request',
            'Field "studentLimit" must be an integer.',
        );
    }

    #[Test]
    public function lecturerCannotCreateNewLectureWithInvalidStudentLimit(): void
    {
        $lecturer = $this->createLecturer();
        $this->persistUser($lecturer);

        $response = $this->makeJsonRequest(
            'POST',
            '/lectures',
            [
                ...$this->validLecturePayload(),
                'studentLimit' => 0,
            ],
            $this->authHeaders($lecturer),
        );

        $this->assertJsonErrorResponse(
            $response,
            Response::HTTP_BAD_REQUEST,
            'invalid_lecture_data',
            'Student limit must be greater than 0.',
        );
    }

    #[Test]
    public function lecturerCannotCreateNewLectureWithInvalidStartDate(): void
    {
        $lecturer = $this->createLecturer();
        $this->persistUser($lecturer);

        $response = $this->makeJsonRequest(
            'POST',
            '/lectures',
            [
                ...$this->validLecturePayload(),
                'startDate' => 'definitely-not-a-date',
            ],
            $this->authHeaders($lecturer),
        );

        $this->assertJsonErrorResponse(
            $response,
            Response::HTTP_BAD_REQUEST,
            'invalid_request',
            'Field "startDate" must be a valid datetime string.',
        );
    }

    #[Test]
    public function lecturerCannotCreateNewLectureWithEndDateEarlierThanStartDate(): void
    {
        $lecturer = $this->createLecturer();
        $this->persistUser($lecturer);

        $response = $this->makeJsonRequest(
            'POST',
            '/lectures',
            [
                ...$this->validLecturePayload(),
                'endDate' => '2026-06-01T09:00:00+02:00',
            ],
            $this->authHeaders($lecturer),
        );

        $this->assertJsonErrorResponse(
            $response,
            Response::HTTP_BAD_REQUEST,
            'invalid_lecture_data',
            'Lecture end date must be later than start date.',
        );
    }

    #[Test]
    public function lecturerCanRemoveStudentFromOwnLecture(): void
    {
        $lecturer = $this->createLecturer();
        $student = $this->createStudent();
        $this->persistUser($lecturer);
        $this->persistUser($student);

        $lecture = $this->createLecture($lecturer);

        $enrollResponse = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($student),
        );
        self::assertSame(Response::HTTP_ACCEPTED, $enrollResponse->getStatusCode());
        $this->processEnrollmentQueue();

        $removeResponse = $this->makeRequest(
            'DELETE',
            sprintf('/lectures/%s/enrollments/%s', $lecture['id'], (string) $student->getId()),
            headers: $this->authHeaders($lecturer),
        );

        self::assertSame(Response::HTTP_NO_CONTENT, $removeResponse->getStatusCode());
        self::assertSame('', (string) $removeResponse->getContent());

        $lecturesResponse = $this->makeRequest(
            'GET',
            '/students/me/lectures',
            headers: $this->authHeaders($student),
        );

        self::assertSame(Response::HTTP_OK, $lecturesResponse->getStatusCode());
        self::assertSame([], $this->decodeJsonResponse($lecturesResponse));
    }

    #[Test]
    public function studentCanEnrollToLecture(): void
    {
        $lecturer = $this->createLecturer();
        $student = $this->createStudent();
        $this->persistUser($lecturer);
        $this->persistUser($student);

        $lecture = $this->createLecture($lecturer);
        $response = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($student),
        );

        self::assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());

        $payload = $this->decodeJsonResponse($response);
        self::assertIsString($payload['requestId'] ?? null);
        self::assertSame('queued', $payload['status']);
        self::assertSame($lecture['id'], $payload['lectureId']);
        self::assertSame((string) $student->getId(), $payload['studentId']);

        $queuedStatusResponse = $this->makeRequest(
            'GET',
            sprintf('/students/me/enrollment-requests/%s', $payload['requestId']),
            headers: $this->authHeaders($student),
        );
        self::assertSame(Response::HTTP_OK, $queuedStatusResponse->getStatusCode());
        self::assertSame('queued', $this->decodeJsonResponse($queuedStatusResponse)['status']);

        $this->processEnrollmentQueue();

        $completedStatusResponse = $this->makeRequest(
            'GET',
            sprintf('/students/me/enrollment-requests/%s', $payload['requestId']),
            headers: $this->authHeaders($student),
        );
        self::assertSame(Response::HTTP_OK, $completedStatusResponse->getStatusCode());

        $completedStatus = $this->decodeJsonResponse($completedStatusResponse);
        self::assertSame('completed', $completedStatus['status']);
        self::assertSame($lecture['id'], $completedStatus['lectureId']);
        self::assertSame((string) $student->getId(), $completedStatus['studentId']);
        self::assertNotNull($completedStatus['processedAt']);
        self::assertNull($completedStatus['failureCode']);
        self::assertNull($completedStatus['failureMessage']);

        $lecturesResponse = $this->makeRequest(
            'GET',
            '/students/me/lectures',
            headers: $this->authHeaders($student),
        );

        self::assertSame(Response::HTTP_OK, $lecturesResponse->getStatusCode());
        $lectures = $this->decodeJsonResponse($lecturesResponse);
        self::assertCount(1, $lectures);
        self::assertSame($lecture['id'], $lectures[0]['id']);
    }

    #[Test]
    public function queueProcessingFailsIfStudentLimitExceeded(): void
    {
        $lecturer = $this->createLecturer();
        $studentOne = $this->createStudent('Student One');
        $studentTwo = $this->createStudent('Student Two');
        $this->persistUser($lecturer);
        $this->persistUser($studentOne);
        $this->persistUser($studentTwo);

        $lecture = $this->createLecture($lecturer, [
            'studentLimit' => 1,
        ]);

        $firstEnrollResponse = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($studentOne),
        );
        self::assertSame(Response::HTTP_ACCEPTED, $firstEnrollResponse->getStatusCode());
        $this->processEnrollmentQueue();

        $secondEnrollResponse = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($studentTwo),
        );
        self::assertSame(Response::HTTP_ACCEPTED, $secondEnrollResponse->getStatusCode());
        $secondEnrollPayload = $this->decodeJsonResponse($secondEnrollResponse);

        $this->processEnrollmentQueue();

        $failedStatusResponse = $this->makeRequest(
            'GET',
            sprintf('/students/me/enrollment-requests/%s', $secondEnrollPayload['requestId']),
            headers: $this->authHeaders($studentTwo),
        );
        self::assertSame(Response::HTTP_OK, $failedStatusResponse->getStatusCode());

        $failedStatus = $this->decodeJsonResponse($failedStatusResponse);
        self::assertSame('failed', $failedStatus['status']);
        self::assertSame('lecture_full', $failedStatus['failureCode']);
        self::assertSame('Lecture student limit exceeded.', $failedStatus['failureMessage']);

        $lecturesResponse = $this->makeRequest(
            'GET',
            '/students/me/lectures',
            headers: $this->authHeaders($studentTwo),
        );

        self::assertSame([], $this->decodeJsonResponse($lecturesResponse));
    }

    #[Test]
    public function queueProcessingFailsIfLectureAlreadyStarted(): void
    {
        $lecturer = $this->createLecturer();
        $student = $this->createStudent();
        $this->persistUser($lecturer);
        $this->persistUser($student);

        $lecture = $this->createLecture($lecturer, [
            'startDate' => '2020-01-01T10:00:00+00:00',
            'endDate' => '2030-01-01T12:00:00+00:00',
        ]);

        $response = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($student),
        );
        self::assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $payload = $this->decodeJsonResponse($response);

        $this->processEnrollmentQueue();

        $failedStatusResponse = $this->makeRequest(
            'GET',
            sprintf('/students/me/enrollment-requests/%s', $payload['requestId']),
            headers: $this->authHeaders($student),
        );
        self::assertSame('failed', $this->decodeJsonResponse($failedStatusResponse)['status']);
    }

    #[Test]
    public function queueProcessingFailsWhenStudentEnrollsTwice(): void
    {
        $lecturer = $this->createLecturer();
        $student = $this->createStudent();
        $this->persistUser($lecturer);
        $this->persistUser($student);

        $lecture = $this->createLecture($lecturer);

        $firstResponse = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($student),
        );
        self::assertSame(Response::HTTP_ACCEPTED, $firstResponse->getStatusCode());
        $this->processEnrollmentQueue();

        $secondResponse = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($student),
        );

        $this->assertJsonErrorResponse(
            $secondResponse,
            Response::HTTP_CONFLICT,
            'already_enrolled',
            'Student is already enrolled to this lecture.',
        );
    }

    #[Test]
    public function itRejectsConcurrentEnrollmentRequestsForSameLecture(): void
    {
        $lecturer = $this->createLecturer();
        $student = $this->createStudent();
        $this->persistUser($lecturer);
        $this->persistUser($student);

        $lecture = $this->createLecture($lecturer);

        $firstResponse = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($student),
        );
        self::assertSame(Response::HTTP_ACCEPTED, $firstResponse->getStatusCode());

        $secondResponse = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($student),
        );

        $this->assertJsonErrorResponse(
            $secondResponse,
            Response::HTTP_CONFLICT,
            'enrollment_in_progress',
            'An enrollment request for this lecture is already being processed.',
        );
    }

    #[Test]
    public function studentCanFetchListOfEnrolledLectures(): void
    {
        $lecturer = $this->createLecturer();
        $student = $this->createStudent();
        $this->persistUser($lecturer);
        $this->persistUser($student);

        $earlierLecture = $this->createLecture($lecturer, [
            'name' => 'Algorithms',
            'startDate' => '2026-06-01T08:00:00+02:00',
            'endDate' => '2026-06-01T10:00:00+02:00',
        ]);
        $laterLecture = $this->createLecture($lecturer, [
            'name' => 'Databases',
            'startDate' => '2026-06-02T08:00:00+02:00',
            'endDate' => '2026-06-02T10:00:00+02:00',
        ]);

        $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $laterLecture['id']),
            [],
            $this->authHeaders($student),
        );
        $this->processEnrollmentQueue(1);
        $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $earlierLecture['id']),
            [],
            $this->authHeaders($student),
        );
        $this->processEnrollmentQueue(1);

        $response = $this->makeRequest(
            'GET',
            '/students/me/lectures',
            headers: $this->authHeaders($student),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $payload = $this->decodeJsonResponse($response);
        self::assertCount(2, $payload);
        self::assertSame($earlierLecture['id'], $payload[0]['id']);
        self::assertSame('Algorithms', $payload[0]['name']);
        self::assertSame($laterLecture['id'], $payload[1]['id']);
        self::assertSame('Databases', $payload[1]['name']);
    }

    #[Test]
    public function studentCanEnrollAgainAfterBeingRemovedFromLecture(): void
    {
        $lecturer = $this->createLecturer();
        $studentOne = $this->createStudent('Student One');
        $studentTwo = $this->createStudent('Student Two');
        $this->persistUser($lecturer);
        $this->persistUser($studentOne);
        $this->persistUser($studentTwo);

        $lecture = $this->createLecture($lecturer, [
            'studentLimit' => 1,
        ]);

        $firstEnroll = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($studentOne),
        );
        self::assertSame(Response::HTTP_ACCEPTED, $firstEnroll->getStatusCode());
        $this->processEnrollmentQueue();

        $remove = $this->makeRequest(
            'DELETE',
            sprintf('/lectures/%s/enrollments/%s', $lecture['id'], (string) $studentOne->getId()),
            headers: $this->authHeaders($lecturer),
        );
        self::assertSame(Response::HTTP_NO_CONTENT, $remove->getStatusCode());

        $secondEnroll = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($studentTwo),
        );
        self::assertSame(Response::HTTP_ACCEPTED, $secondEnroll->getStatusCode());
        $this->processEnrollmentQueue();
    }

    #[Test]
    public function queueProcessingFailsForUnknownLecture(): void
    {
        $student = $this->createStudent();
        $this->persistUser($student);

        $response = $this->makeJsonRequest(
            'POST',
            '/lectures/00000000-0000-4000-8000-000000000099/enrollments',
            [],
            $this->authHeaders($student),
        );
        self::assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $payload = $this->decodeJsonResponse($response);

        $this->processEnrollmentQueue();

        $failedStatusResponse = $this->makeRequest(
            'GET',
            sprintf('/students/me/enrollment-requests/%s', $payload['requestId']),
            headers: $this->authHeaders($student),
        );
        self::assertSame('lecture_not_found', $this->decodeJsonResponse($failedStatusResponse)['failureCode']);
    }

    #[Test]
    public function studentCannotReadAnotherStudentsEnrollmentRequest(): void
    {
        $lecturer = $this->createLecturer();
        $owner = $this->createStudent('Owner');
        $otherStudent = $this->createStudent('Other');
        $this->persistUser($lecturer);
        $this->persistUser($owner);
        $this->persistUser($otherStudent);

        $lecture = $this->createLecture($lecturer);
        $response = $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($owner),
        );
        $payload = $this->decodeJsonResponse($response);

        $statusResponse = $this->makeRequest(
            'GET',
            sprintf('/students/me/enrollment-requests/%s', $payload['requestId']),
            headers: $this->authHeaders($otherStudent),
        );

        $this->assertJsonErrorResponse(
            $statusResponse,
            Response::HTTP_NOT_FOUND,
            'enrollment_request_not_found',
            'Enrollment request not found.',
        );
    }

    #[Test]
    public function lecturerCannotRemoveStudentFromAnotherLecturerLecture(): void
    {
        $ownerLecturer = $this->createLecturer('Owner');
        $otherLecturer = $this->createLecturer('Other');
        $student = $this->createStudent();
        $this->persistUser($ownerLecturer);
        $this->persistUser($otherLecturer);
        $this->persistUser($student);

        $lecture = $this->createLecture($ownerLecturer);

        $this->makeJsonRequest(
            'POST',
            sprintf('/lectures/%s/enrollments', $lecture['id']),
            [],
            $this->authHeaders($student),
        );
        $this->processEnrollmentQueue();

        $response = $this->makeRequest(
            'DELETE',
            sprintf('/lectures/%s/enrollments/%s', $lecture['id'], (string) $student->getId()),
            headers: $this->authHeaders($otherLecturer),
        );

        $this->assertJsonErrorResponse(
            $response,
            Response::HTTP_FORBIDDEN,
            'forbidden',
            'Lecturer can remove students only from own lectures.',
        );
    }

    #[Test]
    public function removingNonEnrolledStudentReturnsNotFound(): void
    {
        $lecturer = $this->createLecturer();
        $student = $this->createStudent();
        $this->persistUser($lecturer);
        $this->persistUser($student);

        $lecture = $this->createLecture($lecturer);

        $response = $this->makeRequest(
            'DELETE',
            sprintf('/lectures/%s/enrollments/%s', $lecture['id'], (string) $student->getId()),
            headers: $this->authHeaders($lecturer),
        );

        $this->assertJsonErrorResponse(
            $response,
            Response::HTTP_NOT_FOUND,
            'enrollment_not_found',
            'Student is not enrolled to this lecture.',
        );
    }

    private function createLecture(User $lecturer, array $payloadOverrides = []): array
    {
        $response = $this->makeJsonRequest(
            'POST',
            '/lectures',
            [
                ...$this->validLecturePayload(),
                ...$payloadOverrides,
            ],
            $this->authHeaders($lecturer),
        );

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());

        return $this->decodeJsonResponse($response);
    }

    private function validLecturePayload(): array
    {
        return [
            'name' => 'Distributed Systems 101',
            'studentLimit' => 25,
            'startDate' => '2026-06-01T10:00:00+02:00',
            'endDate' => '2026-06-01T12:00:00+02:00',
        ];
    }
}
