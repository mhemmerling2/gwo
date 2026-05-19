<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Command;

use Gwo\AppsRecruitmentTask\Persistence\MongoIndexManager;
use Gwo\AppsRecruitmentTask\User\User;
use Gwo\AppsRecruitmentTask\User\UserRepositoryInterface;
use Gwo\AppsRecruitmentTask\User\UserRole;
use Gwo\AppsRecruitmentTask\Util\StringId;
use MongoDB\Client;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ResetMongoDevDataCommandTest extends KernelTestCase
{
    #[Test]
    public function itDropsExistingDataAndRecreatesIndexes(): void
    {
        self::bootKernel();

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $user = new User(
            id: new StringId('00000000-0000-0000-0000-000000000201'),
            name: 'Temporary User',
            apiKey: str_repeat('a', 64),
            role: UserRole::STUDENT,
        );
        $userRepository->save($user);
        self::assertNotNull($userRepository->getByApiKey(str_repeat('a', 64)));

        $commandTester = $this->createCommandTester();
        self::assertSame(0, $commandTester->execute([]));

        self::assertStringContainsString(
            'MongoDB data was reset and indexes were recreated.',
            $commandTester->getDisplay(),
        );
        self::assertNull($userRepository->getByApiKey(str_repeat('a', 64)));
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        /** @var Client $mongoClient */
        $mongoClient = static::getContainer()->get(Client::class);
        $databaseName = static::getContainer()->getParameter('database_name');
        self::assertIsString($databaseName);
        $mongoClient->dropDatabase($databaseName);

        /** @var MongoIndexManager $indexManager */
        $indexManager = static::getContainer()->get(MongoIndexManager::class);
        $indexManager->ensureIndexes();
    }

    private function createCommandTester(): CommandTester
    {
        $kernel = static::$kernel;
        self::assertNotNull($kernel);

        $application = new Application($kernel);

        return new CommandTester($application->find('app:mongodb:reset-dev-data'));
    }
}
