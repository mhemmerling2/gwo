<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Messenger\Transport;

use MongoDB\Client;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * @implements TransportFactoryInterface<MongoQueueTransport>
 */
final readonly class MongoQueueTransportFactory implements TransportFactoryInterface
{
    private const DSN_PREFIX = 'mongoqueue://';

    public function __construct(
        private Client $client,
        private string $databaseName,
    ) {
    }

    /**
     * @phpstan-param array<mixed> $options
     */
    #[\Override]
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $parsedOptions = $this->parseDsn($dsn);
        $options = [...$parsedOptions, ...$options];
        $collection = $options['collection'] ?? 'messenger_messages';
        $queue = $options['queue'] ?? 'default';
        $claimTimeout = $options['claim_timeout'] ?? 300;

        if (!is_string($collection) || trim($collection) === '') {
            throw new \InvalidArgumentException('Messenger collection name must be a non-empty string.');
        }

        if (!is_string($queue) || trim($queue) === '') {
            throw new \InvalidArgumentException('Messenger queue name must be a non-empty string.');
        }

        if (is_string($claimTimeout) && is_numeric($claimTimeout)) {
            $claimTimeout = (int) $claimTimeout;
        }

        if (!is_int($claimTimeout) || $claimTimeout < 1) {
            throw new \InvalidArgumentException('Messenger claim timeout must be a positive integer.');
        }

        return new MongoQueueTransport(
            collection: $this->client->selectCollection(
                $this->databaseName,
                $collection,
            ),
            serializer: $serializer,
            queue: $queue,
            claimTimeoutInSeconds: $claimTimeout,
        );
    }

    /**
     * @phpstan-param array<mixed> $options
     */
    #[\Override]
    public function supports(string $dsn, array $options): bool
    {
        return str_starts_with($dsn, self::DSN_PREFIX);
    }

    /**
     * @return array{queue: string, collection?: string, claim_timeout?: int}
     */
    private function parseDsn(string $dsn): array
    {
        $parts = parse_url($dsn);

        if ($parts === false) {
            throw new \InvalidArgumentException(sprintf('Invalid messenger transport DSN: %s', $dsn));
        }

        $options = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $options);
        }

        $queue = $parts['host'] ?? null;

        if (!is_string($queue) || trim($queue) === '') {
            $queue = 'default';
        }

        $options['queue'] = $options['queue'] ?? $queue;

        if (isset($options['claim_timeout']) && is_string($options['claim_timeout']) && is_numeric($options['claim_timeout'])) {
            $options['claim_timeout'] = (int) $options['claim_timeout'];
        }

        if (isset($options['collection']) && !is_string($options['collection'])) {
            unset($options['collection']);
        }

        if (isset($options['claim_timeout']) && !is_int($options['claim_timeout'])) {
            unset($options['claim_timeout']);
        }

        /** @var array{queue: string, collection?: string, claim_timeout?: int} $options */
        return $options;
    }
}
