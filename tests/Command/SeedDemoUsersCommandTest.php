<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Command;

use Gwo\AppsRecruitmentTask\Persistence\DatabaseClient;
use Gwo\AppsRecruitmentTask\Persistence\MongoIndexManager;
use Gwo\AppsRecruitmentTask\User\UserRepositoryInterface;
use Gwo\AppsRecruitmentTask\User\UserRole;
use Override;
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

        $lecturerApiKey = $this->extractDemoApiKey($commandTester->getDisplay(), 'DEMO_LECTURER_API_KEY');
        $studentApiKey = $this->extractDemoApiKey($commandTester->getDisplay(), 'DEMO_STUDENT_API_KEY');

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        $lecturer = $userRepository->getByApiKey($lecturerApiKey);
        $student = $userRepository->getByApiKey($studentApiKey);

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
        $firstLecturerApiKey = $this->extractDemoApiKey($commandTester->getDisplay(), 'DEMO_LECTURER_API_KEY');

        self::assertSame(0, $commandTester->execute([]));
        $secondLecturerApiKey = $this->extractDemoApiKey($commandTester->getDisplay(), 'DEMO_LECTURER_API_KEY');

        self::assertNotSame($firstLecturerApiKey, $secondLecturerApiKey);

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        self::assertNull($userRepository->getByApiKey($firstLecturerApiKey));
        self::assertNotNull($userRepository->getByApiKey($secondLecturerApiKey));
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

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
        $kernel = static::$kernel;
        self::assertNotNull($kernel);

        $application = new Application($kernel);

        return new CommandTester($application->find('app:users:seed-demo'));
    }

    private function extractDemoApiKey(string $output, string $variableName): string
    {
        $pattern = sprintf('/%s=([a-f0-9]{64})/', preg_quote($variableName, '/'));

        if (preg_match($pattern, $output, $matches) !== 1) {
            self::fail(sprintf('Expected %s in command output.', $variableName));
        }

        if (!isset($matches[1])) {
            self::fail(sprintf('Expected capture group for %s.', $variableName));
        }

        return $matches[1];
    }
}
