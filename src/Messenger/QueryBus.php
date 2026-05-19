<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Messenger;

interface QueryBus
{
    public function ask(object $query): mixed;
}
