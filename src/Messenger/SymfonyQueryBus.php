<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Messenger;

use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class SymfonyQueryBus implements QueryBus
{
    public function __construct(
        #[Target('query.bus')]
        private MessageBusInterface $messageBus,
    ) {
    }

    #[\Override]
    public function ask(object $query): mixed
    {
        return HandledMessageResultExtractor::extract($this->messageBus->dispatch($query));
    }
}
