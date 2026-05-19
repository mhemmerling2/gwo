<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\EventSubscriber;

use Override;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class EnsureProductionConfigurationSubscriber implements EventSubscriberInterface
{
    private const DEFAULT_APP_SECRET = 'app-secret-change-me';

    private static bool $validated = false;

    public function __construct(
        #[Autowire(param: 'kernel.environment')]
        private readonly string $environment,
        #[Autowire(param: 'kernel.secret')]
        private readonly string $appSecret,
    ) {
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 512],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || self::$validated || $this->environment !== 'prod') {
            return;
        }

        self::$validated = true;

        if ($this->appSecret === '' || $this->appSecret === self::DEFAULT_APP_SECRET) {
            throw new RuntimeException('APP_SECRET must be set to a strong value in production.');
        }
    }
}
