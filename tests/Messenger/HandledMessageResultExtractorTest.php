<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Messenger;

use Gwo\AppsRecruitmentTask\Messenger\HandledMessageResultExtractor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class HandledMessageResultExtractorTest extends TestCase
{
    #[Test]
    public function itExtractsHandledStampResult(): void
    {
        $message = new \stdClass();
        $envelope = new Envelope(
            message: $message,
            stamps: [new HandledStamp(result: 'ok', handlerName: 'handler.service')],
        );

        self::assertSame('ok', HandledMessageResultExtractor::extract($envelope));
    }

    #[Test]
    public function itThrowsHelpfulErrorWhenHandledStampIsMissing(): void
    {
        $message = new \stdClass();
        $envelope = new Envelope(message: $message);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Ensure this message is handled synchronously');

        HandledMessageResultExtractor::extract($envelope);
    }
}
