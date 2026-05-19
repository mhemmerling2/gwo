<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\User;

use Gwo\AppsRecruitmentTask\Tests\ApiTestCase;
use Gwo\AppsRecruitmentTask\User\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;

final class MongoUserRepositoryTest extends ApiTestCase
{
    #[Test]
    public function itStoresOnlyApiKeyHashAndCanAuthenticateByApiKey(): void
    {
        $user = $this->createLecturer();
        $this->persistUser($user);

        $document = $this->mongoClient()
            ->selectCollection($this->databaseName(), 'users')
            ->findOne(['id' => (string) $user->getId()]);

        self::assertNotNull($document);
        /** @var array<string, mixed> $storedUser */
        $storedUser = $document->getArrayCopy();
        self::assertArrayHasKey('apiKeyHash', $storedUser);
        self::assertArrayNotHasKey('apiKey', $storedUser);

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $this->httpClient->getContainer()->get(UserRepositoryInterface::class);
        $apiKey = $user->getApiKey();
        self::assertIsString($apiKey);

        $resolvedUser = $userRepository->getByApiKey($apiKey);

        self::assertNotNull($resolvedUser);
        self::assertTrue($resolvedUser->getId()->equals($user->getId()));
    }

    #[Test]
    public function itPreservesExistingApiKeyHashWhenSavingLoadedUserWithoutPlainApiKey(): void
    {
        $user = $this->createStudent();
        $this->persistUser($user);

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $this->httpClient->getContainer()->get(UserRepositoryInterface::class);

        $loadedUser = $userRepository->getById($user->getId());
        self::assertNotNull($loadedUser);
        self::assertNull($loadedUser->getApiKey());

        $userRepository->save($loadedUser);

        $apiKey = $user->getApiKey();
        self::assertIsString($apiKey);

        $resolvedUser = $userRepository->getByApiKey($apiKey);
        self::assertNotNull($resolvedUser);
        self::assertTrue($resolvedUser->getId()->equals($user->getId()));
    }
}
