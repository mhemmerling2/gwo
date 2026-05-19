<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Persistence;

use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

final class MongoStudentIds
{
    /**
     * @return list<string>
     */
    public static function fromDocument(mixed $document): array
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
