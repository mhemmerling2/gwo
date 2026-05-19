<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Messenger;

use Gwo\AppsRecruitmentTask\Messenger\SymfonyQueryBus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class SymfonyQueryBusTest extends TestCase
{
    #[Test]
    public function itReturnsHandledResultFromSymfonyBus(): void
    {
        $query = new \stdClass();
        $envelope = new Envelope(
            message: $query,
            stamps: [new HandledStamp(result: ['ok' => true], handlerName: 'query.handler')],
        );

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($query)
            ->willReturn($envelope);

        $queryBus = new SymfonyQueryBus($messageBus);

        self::assertSame(['ok' => true], $queryBus->ask($query));
    }

    #[Test]
    public function itThrowsWhenQueryIsNotHandledSynchronously(): void
    {
        $query = new \stdClass();
        $envelope = new Envelope(message: $query);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($query)
            ->willReturn($envelope);

        $queryBus = new SymfonyQueryBus($messageBus);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Ensure this message is handled synchronously');

        $queryBus->ask($query);
    }
}
