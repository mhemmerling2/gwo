<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Clock;

final readonly class SystemClock implements ClockInterface
{
    #[\Override]
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
