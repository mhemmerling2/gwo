<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Persistence;

use Gwo\AppsRecruitmentTask\Persistence\DatabaseClient;
use Gwo\AppsRecruitmentTask\Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

final class DatabaseClientTest extends ApiTestCase
{
    #[Test]
    public function itDropsDatabaseAndRemovesExistingDocuments(): void
    {
        $client = $this->mongoClient();
        $databaseName = $this->databaseName();

        $client
            ->selectCollection($databaseName, 'coverage_examples')
            ->insertOne(['id' => 'doc-1', 'name' => 'Will disappear']);

        self::assertSame(1, $client->selectCollection($databaseName, 'coverage_examples')->countDocuments());

        $this->databaseClient()->dropDatabase();

        self::assertSame(0, $client->selectCollection($databaseName, 'coverage_examples')->countDocuments());
    }

    private function databaseClient(): DatabaseClient
    {
        /** @var DatabaseClient $databaseClient */
        $databaseClient = $this->httpClient->getContainer()->get(DatabaseClient::class);

        return $databaseClient;
    }
}
