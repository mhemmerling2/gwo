<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use DateTimeImmutable;
use Gwo\AppsRecruitmentTask\Util\StringId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Override;

final class MongoLectureParticipantRepository implements LectureParticipantRepositoryInterface
{
    private const COLLECTION_NAME = 'lectures';

    private Collection $collection;

    public function __construct(Client $client, string $databaseName)
    {
        $this->collection = $client->selectCollection($databaseName, self::COLLECTION_NAME);
    }

    #[Override]
    public function enroll(StringId $lectureId, StringId $studentId): bool
    {
        $result = $this->collection->updateOne(
            [
                'id' => (string) $lectureId,
                'startDate' => ['$gt' => new UTCDateTime(new DateTimeImmutable())],
                'studentIds' => ['$ne' => (string) $studentId],
                '$expr' => [
                    '$lt' => [
                        [
                            '$size' => [
                                '$ifNull' => ['$studentIds', []],
                            ],
                        ],
                        '$studentLimit',
                    ],
                ],
            ],
            [
                '$addToSet' => [
                    'studentIds' => (string) $studentId,
                ],
            ],
        );

        return $result->getModifiedCount() > 0;
    }

    #[Override]
    public function removeStudent(StringId $lectureId, StringId $studentId): bool
    {
        $result = $this->collection->updateOne(
            [
                'id' => (string) $lectureId,
                'studentIds' => (string) $studentId,
            ],
            [
                '$pull' => [
                    'studentIds' => (string) $studentId,
                ],
            ],
        );

        return $result->getModifiedCount() > 0;
    }

    #[Override]
    public function isStudentEnrolled(StringId $lectureId, StringId $studentId): bool
    {
        $document = $this->collection->findOne([
            'id' => (string) $lectureId,
            'studentIds' => (string) $studentId,
        ]);

        return $document instanceof BSONDocument;
    }

    #[Override]
    public function countStudents(StringId $lectureId): int
    {
        $document = $this->collection->findOne(
            ['id' => (string) $lectureId],
            ['projection' => ['studentIds' => 1]],
        );

        if (!$document instanceof BSONDocument) {
            return 0;
        }

        return count($this->extractStudentIds($document));
    }

    /**
     * @return list<StringId>
     */
    #[Override]
    public function findLectureIdsByStudent(StringId $studentId): array
    {
        $documents = $this->collection->find(
            ['studentIds' => (string) $studentId],
            [
                'projection' => ['id' => 1, '_id' => 0],
                'sort' => ['id' => 1],
            ],
        );

        $lectureIds = [];

        foreach ($documents as $document) {
            if (!$document instanceof BSONDocument) {
                continue;
            }

            $lectureId = $document['id'] ?? null;

            if (!is_string($lectureId)) {
                continue;
            }

            $lectureIds[] = new StringId(value: $lectureId);
        }

        return $lectureIds;
    }

    /**
     * @return list<string>
     */
    private function extractStudentIds(mixed $document): array
    {
        if (!$document instanceof BSONDocument) {
            return [];
        }

        $studentIds = $document['studentIds'] ?? [];

        if ($studentIds instanceof BSONArray) {
            $studentIds = $studentIds->getArrayCopy();
        }

        if (!is_array($studentIds)) {
            return [];
        }

        return array_values(array_filter(
            $studentIds,
            static fn(mixed $studentId): bool => is_string($studentId),
        ));
    }
}
