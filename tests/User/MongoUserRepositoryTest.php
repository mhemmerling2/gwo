<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\User;

use Gwo\AppsRecruitmentTask\Persistence\DatabaseClient;
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

        /** @var DatabaseClient $databaseClient */
        $databaseClient = $this->httpClient->getContainer()->get(DatabaseClient::class);
        $documents = $databaseClient->getByQuery('users', ['id' => (string) $user->getId()]);

        self::assertCount(1, $documents);
        self::assertArrayHasKey('apiKeyHash', $documents[0]);
        self::assertArrayNotHasKey('apiKey', $documents[0]);

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $this->httpClient->getContainer()->get(UserRepositoryInterface::class);
        $resolvedUser = $userRepository->getByApiKey($user->getApiKey());

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
        self::assertSame('', $loadedUser->getApiKey());

        $userRepository->save($loadedUser);

        $resolvedUser = $userRepository->getByApiKey($user->getApiKey());
        self::assertNotNull($resolvedUser);
        self::assertTrue($resolvedUser->getId()->equals($user->getId()));
    }
}
