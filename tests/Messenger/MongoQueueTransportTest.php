<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Messenger;

use Gwo\AppsRecruitmentTask\Lecture\EnrollStudentToLectureCommand;
use Gwo\AppsRecruitmentTask\Messenger\Transport\MongoQueueTransport;
use Gwo\AppsRecruitmentTask\Messenger\Transport\MongoQueueTransportFactory;
use Gwo\AppsRecruitmentTask\Util\StringId;
use MongoDB\Client;
use MongoDB\Collection;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

final class MongoQueueTransportTest extends KernelTestCase
{
    private Client $client;
    private string $databaseName;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $client = static::getContainer()->get(Client::class);
        self::assertInstanceOf(Client::class, $client);
        $this->client = $client;
        $databaseName = static::getContainer()->getParameter('database_name');
        self::assertIsString($databaseName);
        $this->databaseName = $databaseName;
        $this->client->dropDatabase($this->databaseName);
    }

    #[Test]
    public function itSendsReceivesAndAcknowledgesQueuedMessages(): void
    {
        $transport = $this->createTransport('transport_test_ack');
        $command = new EnrollStudentToLectureCommand(new StringId('lecture-1'), new StringId('student-1'));

        $sentEnvelope = $transport->send(new Envelope($command));
        self::assertInstanceOf(TransportMessageIdStamp::class, $sentEnvelope->last(TransportMessageIdStamp::class));

        $received = iterator_to_array($transport->get());

        self::assertCount(1, $received);
        self::assertInstanceOf(EnrollStudentToLectureCommand::class, $received[0]->getMessage());

        $transport->ack($received[0]);

        self::assertSame([], iterator_to_array($transport->get()));
    }

    #[Test]
    public function itRejectsAndRemovesQueuedMessages(): void
    {
        $transport = $this->createTransport('transport_test_reject');
        $transport->send(new Envelope(
            new EnrollStudentToLectureCommand(new StringId('lecture-1'), new StringId('student-1')),
        ));

        $received = iterator_to_array($transport->get());
        self::assertCount(1, $received);

        $transport->reject($received[0]);

        self::assertSame([], iterator_to_array($transport->get()));
    }

    #[Test]
    public function itDeliversMessageOnlyAfterDelay(): void
    {
        $transport = $this->createTransport('transport_test_delay');
        $transport->send(new Envelope(
            message: new EnrollStudentToLectureCommand(new StringId('lecture-1'), new StringId('student-1')),
            stamps: [new DelayStamp(150)],
        ));

        self::assertSame([], iterator_to_array($transport->get()));

        usleep(250_000);

        $received = iterator_to_array($transport->get());
        self::assertCount(1, $received);
    }

    #[Test]
    public function itDiscardsMalformedQueueDocumentInsteadOfLeavingItClaimed(): void
    {
        $transport = $this->createTransport('transport_test_invalid_structure');
        $collection = $this->collection('transport_test_invalid_structure');
        $collection->insertOne([
            '_id' => 'broken-message',
            'queue' => 'enrollments',
            'body' => ['unexpected-array-body'],
            'headers' => [],
            'availableAt' => 0,
            'claimedAt' => null,
            'createdAt' => 0,
        ]);

        try {
            iterator_to_array($transport->get());
            self::fail('Expected malformed queue message to raise a logic exception.');
        } catch (LogicException $exception) {
            self::assertSame('Queue document has invalid structure.', $exception->getMessage());
        }

        self::assertSame(0, $collection->countDocuments(['_id' => 'broken-message']));
    }

    #[Test]
    public function itDiscardsUndecodableQueueDocumentInsteadOfRetryingForever(): void
    {
        $transport = $this->createTransport('transport_test_invalid_payload');
        $collection = $this->collection('transport_test_invalid_payload');
        $collection->insertOne([
            '_id' => 'undecodable-message',
            'queue' => 'enrollments',
            'body' => '{"not":"a messenger envelope"}',
            'headers' => [],
            'availableAt' => 0,
            'claimedAt' => null,
            'createdAt' => 0,
        ]);

        try {
            iterator_to_array($transport->get());
            self::fail('Expected undecodable queue message to raise a logic exception.');
        } catch (LogicException $exception) {
            self::assertSame('Queue document could not be decoded.', $exception->getMessage());
        }

        self::assertSame(0, $collection->countDocuments(['_id' => 'undecodable-message']));
    }

    #[Test]
    public function factorySupportsMongoQueueDsnAndCreatesTransport(): void
    {
        $factory = new MongoQueueTransportFactory($this->client, $this->databaseName);

        self::assertTrue($factory->supports('mongoqueue://enrollments?collection=messenger_messages', []));
        self::assertFalse($factory->supports('sync://', []));
        self::assertInstanceOf(
            MongoQueueTransport::class,
            $factory->createTransport(
                'mongoqueue://enrollments?collection=messenger_messages',
                [],
                new PhpSerializer(),
            ),
        );
    }

    private function createTransport(string $collectionName): MongoQueueTransport
    {
        return new MongoQueueTransport(
            collection: $this->collection($collectionName),
            serializer: new PhpSerializer(),
            queue: 'enrollments',
            claimTimeoutInSeconds: 60,
        );
    }

    private function collection(string $name): Collection
    {
        return $this->client->selectCollection($this->databaseName, $name);
    }
}
