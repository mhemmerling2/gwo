<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Messenger;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class SymfonyCommandBus implements CommandBus
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    #[\Override]
    public function dispatch(object $command): Envelope
    {
        return $this->messageBus->dispatch($command);
    }
}
