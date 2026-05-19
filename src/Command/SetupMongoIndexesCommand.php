<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Command;

use Gwo\AppsRecruitmentTask\Persistence\MongoIndexManager;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:mongodb:setup-indexes',
    description: 'Creates required MongoDB indexes for the application.',
)]
final class SetupMongoIndexesCommand extends Command
{
    public function __construct(
        private readonly MongoIndexManager $mongoIndexManager,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->mongoIndexManager->ensureIndexes();
        $output->writeln('<info>MongoDB indexes are ensured.</info>');

        return Command::SUCCESS;
    }
}
