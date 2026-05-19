<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Messenger;

use Symfony\Component\Messenger\Envelope;

interface CommandBus
{
    public function dispatch(object $command): Envelope;
}
