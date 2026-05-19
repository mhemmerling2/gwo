<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\User;

use Gwo\AppsRecruitmentTask\Security\ApiKeyEncoder;
use Gwo\AppsRecruitmentTask\Util\StringId;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;

final class MongoUserRepository implements UserRepositoryInterface
{
    private const COLLECTION_NAME = 'users';
    private Collection $collection;

    public function __construct(
        Client $client,
        string $databaseName,
        private readonly ApiKeyEncoder $apiKeyEncoder,
    )
    {
        $this->collection = $client->selectCollection(
            $databaseName,
            self::COLLECTION_NAME
        );
    }

    #[\Override]
    public function save(User $user): void
    {
        $existingDocument = $this->collection->findOne(
            ['id' => (string) $user->getId()],
            ['projection' => ['apiKeyHash' => 1]],
        );

        $existingApiKeyHash = null;

        if ($existingDocument instanceof BSONDocument && is_string($existingDocument['apiKeyHash'] ?? null)) {
            $existingApiKeyHash = $existingDocument['apiKeyHash'];
        }

        $apiKeyHash = trim($user->getApiKey()) !== ''
            ? $this->apiKeyEncoder->encode($user->getApiKey())
            : $existingApiKeyHash;

        if ($apiKeyHash === null) {
            throw new \InvalidArgumentException('Cannot persist user without API key hash.');
        }

        $this->collection->replaceOne(
            ['id' => (string) $user->getId()],
            [
                'id' => (string) $user->getId(),
                'name' => $user->getName(),
                'apiKeyHash' => $apiKeyHash,
                'role' => $user->getRole()->value,
            ],
            ['upsert' => true],
        );
    }

    #[\Override]
    public function getById(StringId $id): ?User
    {
        $document = $this->collection->findOne(['id' => (string) $id]);

        if (!$document instanceof BSONDocument) {
            return null;
        }

        return $this->mapDocumentToUser($document);
    }

    #[\Override]
    public function getByApiKey(string $apiKey): ?User
    {
        $document = $this->collection->findOne([
            'apiKeyHash' => $this->apiKeyEncoder->encode($apiKey),
        ]);

        if (!$document instanceof BSONDocument) {
            return null;
        }

        return $this->mapDocumentToUser($document);
    }

    private function mapDocumentToUser(BSONDocument $document): User
    {
        $id = $document['id'] ?? null;
        $name = $document['name'] ?? null;
        $role = $document['role'] ?? null;

        if (!is_string($id) || !is_string($name) || !is_string($role)) {
            throw new \RuntimeException('Invalid user document structure.');
        }

        return new User(
            id: new StringId(value: $id),
            name: $name,
            apiKey: '',
            role: UserRole::from(value: $role),
        );
    }
}
