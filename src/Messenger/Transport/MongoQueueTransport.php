<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Messenger\Transport;

use Gwo\AppsRecruitmentTask\Util\StringId;
use MongoDB\Collection;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use MongoDB\Operation\FindOneAndUpdate;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

final readonly class MongoQueueTransport implements TransportInterface
{
    public function __construct(
        private Collection $collection,
        private SerializerInterface $serializer,
        private string $queue,
        private int $claimTimeoutInSeconds = 300,
    ) {
    }

    #[\Override]
    public function send(Envelope $envelope): Envelope
    {
        $messageId = (string) StringId::new();
        $encodedEnvelope = $this->serializer->encode($envelope);
        $now = $this->now();

        $this->collection->insertOne([
            '_id' => $messageId,
            'queue' => $this->queue,
            'body' => $encodedEnvelope['body'],
            'headers' => $encodedEnvelope['headers'] ?? [],
            'availableAt' => $this->availableAt($envelope, $now),
            'claimedAt' => null,
            'createdAt' => $now,
        ]);

        return $envelope->with(new TransportMessageIdStamp($messageId));
    }

    #[\Override]
    public function get(): iterable
    {
        $now = $this->now();
        $document = $this->collection->findOneAndUpdate(
            filter: [
                'queue' => $this->queue,
                'availableAt' => ['$lte' => $now],
                '$or' => [
                    ['claimedAt' => null],
                    ['claimedAt' => ['$lte' => $this->staleClaimThreshold($now)]],
                ],
            ],
            update: ['$set' => ['claimedAt' => $now]],
            options: [
                'sort' => ['createdAt' => 1],
                'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
            ],
        );

        if (!$document instanceof BSONDocument) {
            return [];
        }

        $messageId = $document['_id'] ?? null;
        $body = $document['body'] ?? null;
        $headers = $document['headers'] ?? [];

        if ($headers instanceof BSONDocument) {
            $headers = $headers->getArrayCopy();
        }

        if ($headers instanceof BSONArray) {
            $headers = $headers->getArrayCopy();
        }

        if (!is_string($messageId) || !is_string($body) || !is_array($headers)) {
            return [];
        }

        return [
            $this->serializer->decode([
                'body' => $body,
                'headers' => $this->normalizeHeaders($headers),
            ])->with(new TransportMessageIdStamp($messageId)),
        ];
    }

    #[\Override]
    public function ack(Envelope $envelope): void
    {
        $this->deleteByTransportMessageId($envelope);
    }

    #[\Override]
    public function reject(Envelope $envelope): void
    {
        $this->deleteByTransportMessageId($envelope);
    }

    /**
     * @param array<array-key, mixed> $headers
     * @return array<string, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalizedHeaders = [];

        foreach ($headers as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                continue;
            }

            $normalizedHeaders[$key] = $value;
        }

        return $normalizedHeaders;
    }

    private function deleteByTransportMessageId(Envelope $envelope): void
    {
        $messageIdStamp = $envelope->last(TransportMessageIdStamp::class);

        if (!$messageIdStamp instanceof TransportMessageIdStamp) {
            throw new LogicException('No TransportMessageIdStamp found on the Envelope.');
        }

        $this->collection->deleteOne([
            '_id' => $messageIdStamp->getId(),
            'queue' => $this->queue,
        ]);
    }

    private function availableAt(Envelope $envelope, int $now): int
    {
        /** @var DelayStamp|null $delayStamp */
        $delayStamp = $envelope->last(DelayStamp::class);

        if (!$delayStamp instanceof DelayStamp) {
            return $now;
        }

        return $now + $delayStamp->getDelay();
    }

    private function staleClaimThreshold(int $now): int
    {
        return $now - ($this->claimTimeoutInSeconds * 1000);
    }

    private function now(): int
    {
        return $this->milliseconds();
    }

    private function milliseconds(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
