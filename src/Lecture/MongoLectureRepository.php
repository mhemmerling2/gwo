<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Util\StringId;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

final class MongoLectureRepository implements LectureRepositoryInterface
{
    private const COLLECTION_NAME = 'lectures';

    private Collection $collection;

    public function __construct(Client $client, string $databaseName)
    {
        $this->collection = $client->selectCollection($databaseName, self::COLLECTION_NAME);
    }

    #[\Override]
    public function save(Lecture $lecture): void
    {
        $existingDocument = $this->collection->findOne(
            ['id' => (string) $lecture->getId()],
            ['projection' => ['studentIds' => 1]],
        );

        $studentIds = $this->extractStudentIds($existingDocument);

        $this->collection->replaceOne(
            ['id' => (string) $lecture->getId()],
            [
                'id' => (string) $lecture->getId(),
                'lecturerId' => (string) $lecture->getLecturerId(),
                'name' => $lecture->getName(),
                'studentLimit' => $lecture->getStudentLimit(),
                'studentIds' => $studentIds,
                'startDate' => $lecture->getStartDate()->format(\DATE_ATOM),
                'endDate' => $lecture->getEndDate()->format(\DATE_ATOM),
            ],
            ['upsert' => true],
        );
    }

    #[\Override]
    public function getById(StringId $id): ?Lecture
    {
        $document = $this->collection->findOne(['id' => (string) $id]);

        if (!$document instanceof BSONDocument) {
            return null;
        }

        return $this->mapDocumentToLecture($document);
    }

    /**
     * @param list<StringId> $ids
     * @return list<Lecture>
     */
    #[\Override]
    public function getByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $documents = $this->collection->find(
            [
                'id' => [
                    '$in' => array_map(
                        static fn(StringId $id): string => (string) $id,
                        $ids,
                    ),
                ],
            ],
        );

        $lecturesById = [];

        foreach ($documents as $document) {
            if (!$document instanceof BSONDocument) {
                continue;
            }

            $lecture = $this->mapDocumentToLecture($document);

            if ($lecture === null) {
                continue;
            }

            $lecturesById[(string) $lecture->getId()] = $lecture;
        }

        $lectures = [];

        foreach ($ids as $id) {
            $lecture = $lecturesById[(string) $id] ?? null;

            if ($lecture !== null) {
                $lectures[] = $lecture;
            }
        }

        return $lectures;
    }

    private function mapDocumentToLecture(BSONDocument $document): ?Lecture
    {
        $lectureId = $document['id'] ?? null;
        $lecturerId = $document['lecturerId'] ?? null;
        $name = $document['name'] ?? null;
        $studentLimit = $document['studentLimit'] ?? null;
        $startDate = $document['startDate'] ?? null;
        $endDate = $document['endDate'] ?? null;

        if (
            !is_string($lectureId) ||
            !is_string($lecturerId) ||
            !is_string($name) ||
            !is_int($studentLimit) ||
            !is_string($startDate) ||
            !is_string($endDate)
        ) {
            return null;
        }

        return new Lecture(
            id: new StringId(value: $lectureId),
            lecturerId: new StringId(value: $lecturerId),
            name: $name,
            studentLimit: $studentLimit,
            startDate: new \DateTimeImmutable(datetime: $startDate),
            endDate: new \DateTimeImmutable(datetime: $endDate),
        );
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
