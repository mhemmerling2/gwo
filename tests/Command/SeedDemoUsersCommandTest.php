<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Command;

use Gwo\AppsRecruitmentTask\Persistence\DatabaseClient;
use Gwo\AppsRecruitmentTask\Persistence\MongoIndexManager;
use Gwo\AppsRecruitmentTask\User\UserRepositoryInterface;
use Gwo\AppsRecruitmentTask\User\UserRole;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SeedDemoUsersCommandTest extends KernelTestCase
{
    #[Test]
    public function itCreatesDemoUsersAndPrintsApiKeys(): void
    {
        $commandTester = $this->createCommandTester();

        self::assertSame(0, $commandTester->execute([]));

        $output = $commandTester->getDisplay();

        preg_match('/DEMO_LECTURER_API_KEY=([a-f0-9]{64})/', $output, $lecturerMatch);
        preg_match('/DEMO_STUDENT_API_KEY=([a-f0-9]{64})/', $output, $studentMatch);

        self::assertArrayHasKey(1, $lecturerMatch);
        self::assertArrayHasKey(1, $studentMatch);

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $lecturer = $userRepository->getByApiKey($lecturerMatch[1]);
        $student = $userRepository->getByApiKey($studentMatch[1]);

        self::assertNotNull($lecturer);
        self::assertNotNull($student);
        self::assertSame('Demo Lecturer', $lecturer->getName());
        self::assertSame(UserRole::LECTURER, $lecturer->getRole());
        self::assertSame('Demo Student', $student->getName());
        self::assertSame(UserRole::STUDENT, $student->getRole());
    }

    #[Test]
    public function rerunningTheCommandRotatesDemoApiKeys(): void
    {
        $commandTester = $this->createCommandTester();

        self::assertSame(0, $commandTester->execute([]));
        preg_match('/DEMO_LECTURER_API_KEY=([a-f0-9]{64})/', $commandTester->getDisplay(), $firstRunMatch);
        self::assertArrayHasKey(1, $firstRunMatch);

        self::assertSame(0, $commandTester->execute([]));
        preg_match('/DEMO_LECTURER_API_KEY=([a-f0-9]{64})/', $commandTester->getDisplay(), $secondRunMatch);
        self::assertArrayHasKey(1, $secondRunMatch);

        self::assertNotSame($firstRunMatch[1], $secondRunMatch[1]);

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        self::assertNull($userRepository->getByApiKey($firstRunMatch[1]));
        self::assertNotNull($userRepository->getByApiKey($secondRunMatch[1]));
    }

    protected function setUp(): void
    {
        self::bootKernel();

        /** @var DatabaseClient $databaseClient */
        $databaseClient = static::getContainer()->get(DatabaseClient::class);
        $databaseClient->dropDatabase();

        /** @var MongoIndexManager $indexManager */
        $indexManager = static::getContainer()->get(MongoIndexManager::class);
        $indexManager->ensureIndexes();
    }

    private function createCommandTester(): CommandTester
    {
        $application = new Application(static::$kernel);

        return new CommandTester($application->find('app:users:seed-demo'));
    }
}
