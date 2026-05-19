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
        self::assertArrayHasKey('apiKeyHash', $document);
        self::assertArrayNotHasKey('apiKey', $document);

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
