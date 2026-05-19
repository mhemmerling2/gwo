<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use DateTimeImmutable;
use Gwo\AppsRecruitmentTask\Persistence\MongoStudentIds;
use Gwo\AppsRecruitmentTask\Util\StringId;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;
use Override;

final class MongoLectureEnrollmentRepository implements LectureEnrollmentRepositoryInterface
{
    private const COLLECTION_NAME = 'lectures';

    private Collection $collection;

    public function __construct(Client $client, string $databaseName)
    {
        $this->collection = $client->selectCollection($databaseName, self::COLLECTION_NAME);
    }

    #[Override]
    public function save(LectureEnrollment $enrollment): bool
    {
        $result = $this->collection->updateOne(
            [
                'id' => (string) $enrollment->getLectureId(),
                'startDate' => ['$gt' => (new DateTimeImmutable())->format(DATE_ATOM)],
                'studentIds' => ['$ne' => (string) $enrollment->getStudentId()],
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
                    'studentIds' => (string) $enrollment->getStudentId(),
                ],
            ],
        );

        return $result->getModifiedCount() > 0;
    }

    #[Override]
    public function deleteByLectureAndStudent(StringId $lectureId, StringId $studentId): bool
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
    public function existsByLectureAndStudent(StringId $lectureId, StringId $studentId): bool
    {
        $document = $this->collection->findOne([
            'id' => (string) $lectureId,
            'studentIds' => (string) $studentId,
        ]);

        return $document instanceof BSONDocument;
    }

    #[Override]
    public function countByLecture(StringId $lectureId): int
    {
        $document = $this->collection->findOne(
            ['id' => (string) $lectureId],
            ['projection' => ['studentIds' => 1]],
        );

        if (!$document instanceof BSONDocument) {
            return 0;
        }

        return count(MongoStudentIds::fromDocument($document));
    }

    /**
     * @return list<StringId>
     */
    #[Override]
    public function getLectureIdsByStudent(StringId $studentId): array
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
}
