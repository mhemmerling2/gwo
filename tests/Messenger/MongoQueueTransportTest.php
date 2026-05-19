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
