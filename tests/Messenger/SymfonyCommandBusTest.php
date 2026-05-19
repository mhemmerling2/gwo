<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Messenger;

use Gwo\AppsRecruitmentTask\Messenger\SymfonyCommandBus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class SymfonyCommandBusTest extends TestCase
{
    #[Test]
    public function itDispatchesCommandThroughSymfonyBus(): void
    {
        $command = new \stdClass();
        $envelope = new Envelope($command);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willReturn($envelope);

        $commandBus = new SymfonyCommandBus($messageBus);

        self::assertSame($envelope, $commandBus->dispatch($command));
    }
}
