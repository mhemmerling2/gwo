<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Command;

use Gwo\AppsRecruitmentTask\User\User;
use Gwo\AppsRecruitmentTask\User\UserRepositoryInterface;
use Gwo\AppsRecruitmentTask\User\UserRole;
use Gwo\AppsRecruitmentTask\Util\StringId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:users:seed-demo',
    description: 'Creates demo lecturer and student accounts for manual API verification.',
)]
final class SeedDemoUsersCommand extends Command
{
    private const DEMO_LECTURER_ID = '00000000-0000-0000-0000-000000000101';
    private const DEMO_STUDENT_ID = '00000000-0000-0000-0000-000000000102';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $lecturer = new User(
            id: new StringId(self::DEMO_LECTURER_ID),
            name: 'Demo Lecturer',
            apiKey: $this->generateApiKey(),
            role: UserRole::LECTURER,
        );
        $student = new User(
            id: new StringId(self::DEMO_STUDENT_ID),
            name: 'Demo Student',
            apiKey: $this->generateApiKey(),
            role: UserRole::STUDENT,
        );

        $this->userRepository->save($lecturer);
        $this->userRepository->save($student);

        $io->success('Demo users created or updated. API keys are shown only once below.');
        $io->table(
            ['Role', 'User ID', 'Name', 'API Key'],
            [
                [
                    $lecturer->getRole()->value,
                    (string) $lecturer->getId(),
                    $lecturer->getName(),
                    $lecturer->getApiKey(),
                ],
                [
                    $student->getRole()->value,
                    (string) $student->getId(),
                    $student->getName(),
                    $student->getApiKey(),
                ],
            ],
        );
        $io->section('Shell-friendly values');
        $io->writeln(sprintf('DEMO_LECTURER_ID=%s', (string) $lecturer->getId()));
        $io->writeln(sprintf('DEMO_LECTURER_API_KEY=%s', $lecturer->getApiKey()));
        $io->writeln(sprintf('DEMO_STUDENT_ID=%s', (string) $student->getId()));
        $io->writeln(sprintf('DEMO_STUDENT_API_KEY=%s', $student->getApiKey()));
        $io->writeln('Use the values with the X-Api-Key request header.');

        return Command::SUCCESS;
    }

    private function generateApiKey(): string
    {
        return bin2hex(random_bytes(32));
    }
}
