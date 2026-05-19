<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Persistence;

use MongoDB\Client;

final readonly class MongoIndexManager
{
    public function __construct(
        private Client $client,
        private string $databaseName,
    ) {
    }

    public function ensureIndexes(): void
    {
        $lectures = $this->client->selectCollection($this->databaseName, 'lectures');
        $lectures->createIndex(['id' => 1], ['unique' => true]);
        $lectures->createIndex(['lecturerId' => 1]);
        $lectures->createIndex(['studentIds' => 1]);

        $users = $this->client->selectCollection($this->databaseName, 'users');
        $users->createIndex(['id' => 1], ['unique' => true]);
        $users->createIndex(['apiKeyHash' => 1], ['unique' => true]);

        $enrollmentRequests = $this->client->selectCollection($this->databaseName, 'enrollment_requests');
        $enrollmentRequests->createIndex(['id' => 1], ['unique' => true]);
        $enrollmentRequests->createIndex(['studentId' => 1, 'createdAt' => -1]);

        $messengerMessages = $this->client->selectCollection($this->databaseName, 'messenger_messages');
        $messengerMessages->createIndex(['queue' => 1, 'availableAt' => 1, 'claimedAt' => 1, 'createdAt' => 1]);

        $failedMessengerMessages = $this->client->selectCollection($this->databaseName, 'messenger_messages_failed');
        $failedMessengerMessages->createIndex(['queue' => 1, 'availableAt' => 1, 'claimedAt' => 1, 'createdAt' => 1]);
    }
}
