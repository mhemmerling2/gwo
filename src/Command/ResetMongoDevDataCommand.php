<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Command;

use Gwo\AppsRecruitmentTask\Persistence\DatabaseClient;
use Gwo\AppsRecruitmentTask\Persistence\MongoIndexManager;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:mongodb:reset-dev-data',
    description: 'Drops current MongoDB data and recreates indexes for a clean local environment.',
)]
final class ResetMongoDevDataCommand extends Command
{
    public function __construct(
        private readonly DatabaseClient $databaseClient,
        private readonly MongoIndexManager $mongoIndexManager,
        #[Autowire(param: 'kernel.environment')]
        private readonly string $environment,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!in_array($this->environment, ['dev', 'test'], true)) {
            $output->writeln('<error>This command can only run in the dev or test environment.</error>');

            return Command::FAILURE;
        }

        $this->databaseClient->dropDatabase();
        $this->mongoIndexManager->ensureIndexes();

        $output->writeln('<info>MongoDB data was reset and indexes were recreated.</info>');

        return Command::SUCCESS;
    }
}
