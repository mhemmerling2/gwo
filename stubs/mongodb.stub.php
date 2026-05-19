<?php

declare(strict_types=1);

namespace MongoDB;

class Client
{
    public function __construct(?string $uri = null, array $uriOptions = [], array $driverOptions = [])
    {
    }

    public function selectCollection(string $databaseName, string $collectionName): Collection
    {
        return new Collection();
    }

    public function dropDatabase(string $databaseName): void
    {
    }
}

class Collection
{
    public function findOne(array $filter = [], array $options = []): mixed
    {
        return null;
    }

    /**
     * @return iterable<mixed>
     */
    public function find(array $filter = [], array $options = []): iterable
    {
        return [];
    }

    public function updateOne(array $filter, array $update, array $options = []): UpdateResult
    {
        return new UpdateResult();
    }

    public function replaceOne(array $filter, array $document, array $options = []): UpdateResult
    {
        return new UpdateResult();
    }

    public function insertOne(array $document): void
    {
    }

    public function deleteOne(array $filter): void
    {
    }

    public function createIndex(array|object $key, array $options = []): string
    {
        return '';
    }
}

class UpdateResult
{
    public function getModifiedCount(): int
    {
        return 0;
    }
}

namespace MongoDB\Model;

/**
 * @extends \ArrayObject<string, mixed>
 */
class BSONDocument extends \ArrayObject
{
}

/**
 * @extends \ArrayObject<int, mixed>
 */
class BSONArray extends \ArrayObject
{
}

namespace MongoDB\BSON;

class UTCDateTime
{
    public function __construct(mixed $milliseconds = null)
    {
    }

    public function toDateTime(): \DateTime
    {
        return new \DateTime();
    }
}

namespace MongoDB\Operation;

class FindOneAndUpdate
{
    public const RETURN_DOCUMENT_AFTER = 2;
}

namespace MongoDB\Driver\Exception;

class BulkWriteException extends \RuntimeException
{
}
