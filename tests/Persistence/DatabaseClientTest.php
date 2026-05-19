<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Persistence;

use Gwo\AppsRecruitmentTask\Persistence\DatabaseClient;
use Gwo\AppsRecruitmentTask\Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

final class DatabaseClientTest extends ApiTestCase
{
    #[Test]
    public function itStoresAndFetchesDocumentsUsingQueryOptions(): void
    {
        $databaseClient = $this->databaseClient();

        $databaseClient->upsert('coverage_examples', ['id' => 'doc-1'], [
            'id' => 'doc-1',
            'name' => 'Second',
            'score' => 20,
        ]);
        $databaseClient->upsert('coverage_examples', ['id' => 'doc-2'], [
            'id' => 'doc-2',
            'name' => 'First',
            'score' => 10,
        ]);

        $documents = $databaseClient->getByQuery(
            'coverage_examples',
            [],
            ['sort' => ['score' => 1]],
        );

        self::assertCount(2, $documents);
        self::assertSame('First', $documents[0]['name']);
        self::assertSame(10, $documents[0]['score']);
        self::assertSame('Second', $documents[1]['name']);
        self::assertSame(20, $documents[1]['score']);
    }

    #[Test]
    public function itUpdatesExistingDocumentOnUpsert(): void
    {
        $databaseClient = $this->databaseClient();

        $databaseClient->upsert('coverage_examples', ['id' => 'doc-1'], [
            'id' => 'doc-1',
            'name' => 'Before update',
            'score' => 10,
        ]);
        $databaseClient->upsert('coverage_examples', ['id' => 'doc-1'], [
            'id' => 'doc-1',
            'name' => 'After update',
            'score' => 15,
        ]);

        $documents = $databaseClient->getByQuery('coverage_examples', ['id' => 'doc-1']);

        self::assertCount(1, $documents);
        self::assertSame('After update', $documents[0]['name']);
        self::assertSame(15, $documents[0]['score']);
    }

    #[Test]
    public function itDropsDatabaseAndRemovesExistingDocuments(): void
    {
        $databaseClient = $this->databaseClient();

        $databaseClient->upsert('coverage_examples', ['id' => 'doc-1'], [
            'id' => 'doc-1',
            'name' => 'Will disappear',
        ]);

        self::assertCount(1, $databaseClient->getByQuery('coverage_examples', []));

        $databaseClient->dropDatabase();

        self::assertSame([], $databaseClient->getByQuery('coverage_examples', []));
    }

    private function databaseClient(): DatabaseClient
    {
        /** @var DatabaseClient $databaseClient */
        $databaseClient = $this->httpClient->getContainer()->get(DatabaseClient::class);

        return $databaseClient;
    }
}
