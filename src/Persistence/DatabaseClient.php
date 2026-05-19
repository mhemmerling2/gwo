<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Persistence;

use MongoDB\Client;

final readonly class DatabaseClient
{
    public function __construct(
        private Client $client,
        private string $databaseName,
    ) {
    }

    public function dropDatabase(): void
    {
        $this->client->dropDatabase($this->databaseName);
    }
}
