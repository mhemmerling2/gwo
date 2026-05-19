<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Messenger;

use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class HandlerFailedExceptionUnwrapper
{
    /**
     * @param class-string<\Throwable>[] $preferredExceptionClasses
     */
    public static function unwrap(HandlerFailedException $exception, array $preferredExceptionClasses = []): \Throwable
    {
        $exceptionChain = self::buildExceptionChain($exception);

        if ($preferredExceptionClasses !== []) {
            foreach ($exceptionChain as $nestedException) {
                foreach ($preferredExceptionClasses as $exceptionClass) {
                    if ($nestedException instanceof $exceptionClass) {
                        return $nestedException;
                    }
                }
            }
        }

        foreach ($exceptionChain as $nestedException) {
            if (!$nestedException instanceof HandlerFailedException) {
                return $nestedException;
            }
        }

        return $exceptionChain[0];
    }

    /**
     * @return list<\Throwable>
     */
    private static function buildExceptionChain(\Throwable $exception): array
    {
        $exceptionChain = [];
        $current = $exception;

        do {
            $exceptionChain[] = $current;
            $current = $current->getPrevious();
        } while ($current !== null);

        return $exceptionChain;
    }
}
