<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use DateTimeImmutable;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Gwo\AppsRecruitmentTask\Util\StringId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;
use Override;

final class MongoEnrollmentRequestRepository implements EnrollmentRequestRepositoryInterface
{
    private const COLLECTION_NAME = 'enrollment_requests';

    private Collection $collection;

    public function __construct(Client $client, string $databaseName)
    {
        $this->collection = $client->selectCollection($databaseName, self::COLLECTION_NAME);
    }

    #[Override]
    public function queue(StringId $requestId, StringId $lectureId, StringId $studentId): void
    {
        $now = $this->now();

        $this->collection->replaceOne(
            ['id' => (string) $requestId],
            [
                'id' => (string) $requestId,
                'lectureId' => (string) $lectureId,
                'studentId' => (string) $studentId,
                'status' => EnrollmentRequestStatus::QUEUED->value,
                'createdAt' => $now,
                'updatedAt' => $now,
                'processedAt' => null,
                'failureCode' => null,
                'failureMessage' => null,
            ],
            ['upsert' => true],
        );
    }

    #[Override]
    public function markProcessing(StringId $requestId): void
    {
        $this->collection->updateOne(
            [
                'id' => (string) $requestId,
                'status' => [
                    '$in' => [
                        EnrollmentRequestStatus::QUEUED->value,
                        EnrollmentRequestStatus::PROCESSING->value,
                    ],
                ],
            ],
            [
                '$set' => [
                    'status' => EnrollmentRequestStatus::PROCESSING->value,
                    'updatedAt' => $this->now(),
                    'failureCode' => null,
                    'failureMessage' => null,
                ],
            ],
        );
    }

    #[Override]
    public function markCompleted(StringId $requestId): void
    {
        $now = $this->now();

        $this->collection->updateOne(
            [
                'id' => (string) $requestId,
                'status' => [
                    '$in' => [
                        EnrollmentRequestStatus::QUEUED->value,
                        EnrollmentRequestStatus::PROCESSING->value,
                        EnrollmentRequestStatus::FAILED->value,
                    ],
                ],
            ],
            [
                '$set' => [
                    'status' => EnrollmentRequestStatus::COMPLETED->value,
                    'updatedAt' => $now,
                    'processedAt' => $now,
                    'failureCode' => null,
                    'failureMessage' => null,
                ],
            ],
        );
    }

    #[Override]
    public function markFailed(StringId $requestId, ApiErrorCode $failureCode, string $failureMessage): void
    {
        $now = $this->now();

        $this->collection->updateOne(
            [
                'id' => (string) $requestId,
                'status' => [
                    '$in' => [
                        EnrollmentRequestStatus::QUEUED->value,
                        EnrollmentRequestStatus::PROCESSING->value,
                    ],
                ],
            ],
            [
                '$set' => [
                    'status' => EnrollmentRequestStatus::FAILED->value,
                    'updatedAt' => $now,
                    'processedAt' => $now,
                    'failureCode' => $failureCode->value,
                    'failureMessage' => $failureMessage,
                ],
            ],
        );
    }

    #[Override]
    public function hasActiveForStudentAndLecture(StringId $lectureId, StringId $studentId): bool
    {
        $document = $this->collection->findOne([
            'lectureId' => (string) $lectureId,
            'studentId' => (string) $studentId,
            'status' => [
                '$in' => [
                    EnrollmentRequestStatus::QUEUED->value,
                    EnrollmentRequestStatus::PROCESSING->value,
                ],
            ],
        ]);

        return $document instanceof BSONDocument;
    }

    #[Override]
    public function getByIdForStudent(StringId $requestId, StringId $studentId): ?EnrollmentRequest
    {
        $document = $this->collection->findOne([
            'id' => (string) $requestId,
            'studentId' => (string) $studentId,
        ]);

        if (!$document instanceof BSONDocument) {
            return null;
        }

        return $this->mapDocumentToEnrollmentRequest($document);
    }

    private function mapDocumentToEnrollmentRequest(BSONDocument $document): ?EnrollmentRequest
    {
        $id = $document['id'] ?? null;
        $lectureId = $document['lectureId'] ?? null;
        $studentId = $document['studentId'] ?? null;
        $status = $document['status'] ?? null;
        $createdAt = $document['createdAt'] ?? null;
        $updatedAt = $document['updatedAt'] ?? null;
        $processedAt = $document['processedAt'] ?? null;
        $failureCode = $document['failureCode'] ?? null;
        $failureMessage = $document['failureMessage'] ?? null;

        if (
            !is_string($id) ||
            !is_string($lectureId) ||
            !is_string($studentId) ||
            !is_string($status) ||
            !$createdAt instanceof UTCDateTime ||
            !$updatedAt instanceof UTCDateTime
        ) {
            return null;
        }

        if ($processedAt !== null && !$processedAt instanceof UTCDateTime) {
            return null;
        }

        if ($failureCode !== null && !is_string($failureCode)) {
            return null;
        }

        if ($failureMessage !== null && !is_string($failureMessage)) {
            return null;
        }

        $statusEnum = EnrollmentRequestStatus::tryFrom($status);

        if ($statusEnum === null) {
            return null;
        }

        $failureCodeEnum = null;

        if ($failureCode !== null) {
            $failureCodeEnum = ApiErrorCode::tryFrom($failureCode);

            if ($failureCodeEnum === null) {
                return null;
            }
        }

        return new EnrollmentRequest(
            id: new StringId($id),
            lectureId: new StringId($lectureId),
            studentId: new StringId($studentId),
            status: $statusEnum,
            createdAt: $this->toDateTimeImmutable($createdAt),
            updatedAt: $this->toDateTimeImmutable($updatedAt),
            processedAt: $processedAt instanceof UTCDateTime ? $this->toDateTimeImmutable($processedAt) : null,
            failureCode: $failureCodeEnum,
            failureMessage: $failureMessage,
        );
    }

    private function now(): UTCDateTime
    {
        return new UTCDateTime(new DateTimeImmutable());
    }

    private function toDateTimeImmutable(UTCDateTime $dateTime): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($dateTime->toDateTime());
    }
}
