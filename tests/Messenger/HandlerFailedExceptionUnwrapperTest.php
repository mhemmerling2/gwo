<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Messenger;

use Gwo\AppsRecruitmentTask\Messenger\HandlerFailedExceptionUnwrapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class HandlerFailedExceptionUnwrapperTest extends TestCase
{
    #[Test]
    public function itUnwrapsToFirstNonHandlerExceptionInChain(): void
    {
        $rootException = new \RuntimeException('root');
        $inner = new HandlerFailedException(
            envelope: new Envelope(message: new \stdClass()),
            exceptions: ['inner.handler' => $rootException],
        );
        $outer = new HandlerFailedException(
            envelope: new Envelope(message: new \stdClass()),
            exceptions: ['outer.handler' => $inner],
        );

        self::assertSame($rootException, HandlerFailedExceptionUnwrapper::unwrap($outer));
    }

    #[Test]
    public function itPrioritizesPreferredExceptionClasses(): void
    {
        $preferredException = new \InvalidArgumentException('preferred');
        $inner = new HandlerFailedException(
            envelope: new Envelope(message: new \stdClass()),
            exceptions: ['inner.handler' => $preferredException],
        );
        $outer = new HandlerFailedException(
            envelope: new Envelope(message: new \stdClass()),
            exceptions: ['outer.handler' => $inner],
        );

        $result = HandlerFailedExceptionUnwrapper::unwrap(
            exception: $outer,
            preferredExceptionClasses: [\InvalidArgumentException::class],
        );

        self::assertSame($preferredException, $result);
    }
}
