<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Clock;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
