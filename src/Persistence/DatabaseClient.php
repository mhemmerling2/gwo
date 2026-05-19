<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Persistence;

use MongoDB\Client;
use MongoDB\Driver\Command;
use MongoDB\Model\BSONDocument;

final readonly class DatabaseClient
{
    private Client $mongoClient;

    public function __construct(
        private string $databaseUri,
        private string $databaseName,
    ) {
        $this->mongoClient = new Client($this->databaseUri);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $document
     */
    public function upsert(string $collectionName, array $query, array $document): void
    {
        $upsert = new Command([
            'update' => $collectionName,
            'updates' => [
                [
                    'q' => $query,
                    'u' => $document,
                    'upsert' => true,
                    'multi' => false,
                ],
            ],
        ]);

        $this->mongoClient->getManager()->executeCommand($this->databaseName, $upsert);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $options
     * @return array<int, array<string, mixed>>
     */
    public function getByQuery(string $collectionName, array $query, array $options = []): array
    {
        $documents = $this->mongoClient
            ->selectCollection($this->databaseName, $collectionName)
            ->find($query, $options);

        return array_map(static function (mixed $document): array {
            if ($document instanceof BSONDocument) {
                return $document->getArrayCopy();
            }

            return [];
        }, array_values($documents->toArray()));
    }

    public function dropDatabase(): void
    {
        $this->mongoClient->dropDatabase($this->databaseName);
    }
}
