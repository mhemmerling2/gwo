<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Messenger\Transport;

use Gwo\AppsRecruitmentTask\Util\StringId;
use MongoDB\Collection;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use MongoDB\Operation\FindOneAndUpdate;
use Override;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Throwable;

final readonly class MongoQueueTransport implements TransportInterface
{
    public function __construct(
        private Collection $collection,
        private SerializerInterface $serializer,
        private string $queue,
        private int $claimTimeoutInSeconds = 300,
    ) {
    }

    #[Override]
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

    #[Override]
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

        return [$this->decodeEnvelope($document)];
    }

    #[Override]
    public function ack(Envelope $envelope): void
    {
        $this->deleteByTransportMessageId($envelope);
    }

    #[Override]
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

        $messageId = $messageIdStamp->getId();

        if (!is_string($messageId) || $messageId === '') {
            throw new LogicException('Transport message id must be a non-empty string.');
        }

        $this->deleteRawMessage($messageId);
    }

    private function decodeEnvelope(BSONDocument $document): Envelope
    {
        $messageId = $document['_id'] ?? null;
        $body = $document['body'] ?? null;
        $headers = $document['headers'] ?? [];

        if ($headers instanceof BSONDocument || $headers instanceof BSONArray) {
            $headers = $headers->getArrayCopy();
        }

        if (!is_string($messageId) || !is_string($body) || !is_array($headers)) {
            $this->discardInvalidMessage($messageId, 'Queue document has invalid structure.');
        }

        try {
            return $this->serializer->decode([
                'body' => $body,
                'headers' => $this->normalizeHeaders($headers),
            ])->with(new TransportMessageIdStamp($messageId));
        } catch (Throwable $exception) {
            $this->discardInvalidMessage($messageId, 'Queue document could not be decoded.', $exception);
        }
    }

    private function deleteRawMessage(string $messageId): void
    {
        $this->collection->deleteOne([
            '_id' => $messageId,
            'queue' => $this->queue,
        ]);
    }

    private function discardInvalidMessage(mixed $messageId, string $reason, ?Throwable $previous = null): never
    {
        if (is_string($messageId)) {
            $this->deleteRawMessage($messageId);
        }

        throw new LogicException($reason, previous: $previous);
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
