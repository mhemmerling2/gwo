<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Messenger;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class HandledMessageResultExtractor
{
    public static function extract(Envelope $envelope): mixed
    {
        $handledStamp = $envelope->last(HandledStamp::class);

        if (!$handledStamp instanceof HandledStamp) {
            throw new \RuntimeException(sprintf(
                'No handled result available for message "%s". Ensure this message is handled synchronously on the current bus.',
                $envelope->getMessage()::class,
            ));
        }

        return $handledStamp->getResult();
    }
}
