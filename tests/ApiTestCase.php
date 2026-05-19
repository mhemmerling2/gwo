<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests;

use Gwo\AppsRecruitmentTask\Persistence\DatabaseClient;
use Gwo\AppsRecruitmentTask\Persistence\MongoIndexManager;
use Gwo\AppsRecruitmentTask\User\User;
use Gwo\AppsRecruitmentTask\User\UserRepositoryInterface;
use Gwo\AppsRecruitmentTask\User\UserRole;
use Gwo\AppsRecruitmentTask\Util\StringId;
use MongoDB\Client;
use Override;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

abstract class ApiTestCase extends WebTestCase
{
    use InteractsWithMessenger;

    protected KernelBrowser $httpClient;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = static::createClient();

        /** @var DatabaseClient $databaseClient */
        $databaseClient = $this->httpClient->getContainer()->get(DatabaseClient::class);
        $databaseClient->dropDatabase();

        /** @var MongoIndexManager $indexManager */
        $indexManager = $this->httpClient->getContainer()->get(MongoIndexManager::class);
        $indexManager->ensureIndexes();
    }

    /**
     * @param array<string, string> $headers
     */
    protected function makeRequest(string $method, string $uri, string $content = '', array $headers = []): Response
    {
        $this->httpClient->request(
            $method,
            $uri,
            [],
            [],
            $headers,
            $content,
        );

        return $this->httpClient->getResponse();
    }

    /**
     * @param array<string, mixed>|string $payload
     * @param array<string, string> $headers
     */
    protected function makeJsonRequest(string $method, string $uri, array|string $payload = [], array $headers = []): Response
    {
        $content = is_string($payload)
            ? $payload
            : json_encode($payload, JSON_THROW_ON_ERROR);

        return $this->makeRequest(
            $method,
            $uri,
            $content,
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                ...$headers,
            ],
        );
    }

    protected function createLecturer(string $name = 'Lecturer'): User
    {
        return new User(StringId::new(), $name, $this->generateApiKey(), UserRole::LECTURER);
    }

    protected function createStudent(string $name = 'Student'): User
    {
        return new User(StringId::new(), $name, $this->generateApiKey(), UserRole::STUDENT);
    }

    protected function persistUser(User $user): void
    {
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $this->httpClient->getContainer()->get(UserRepositoryInterface::class);
        $userRepository->save($user);
    }

    /**
     * @return array<string, string>
     */
    protected function authHeaders(User $user): array
    {
        return [
            'HTTP_X_API_KEY' => $user->getApiKey(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJsonResponse(Response $response): array
    {
        $content = $response->getContent();

        if ($content === false) {
            self::fail('Response content could not be read.');
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    protected function assertJsonErrorResponse(Response $response, int $statusCode, string $error, ?string $message = null): void
    {
        self::assertSame($statusCode, $response->getStatusCode());

        $payload = $this->decodeJsonResponse($response);

        self::assertSame($error, $payload['error'] ?? null);

        if ($message !== null) {
            self::assertSame($message, $payload['message'] ?? null);
        }
    }

    protected function mongoClient(): Client
    {
        /** @var Client $client */
        $client = $this->httpClient->getContainer()->get(Client::class);

        return $client;
    }

    protected function databaseName(): string
    {
        $databaseName = $this->httpClient->getContainer()->getParameter('database_name');
        self::assertIsString($databaseName);

        return $databaseName;
    }

    protected function processEnrollmentQueue(int $messages = -1): void
    {
        try {
            if ($messages > 0) {
                $this->transport('async')->processOrFail($messages);

                return;
            }

            $this->transport('async')->processOrFail();
        } catch (HandlerFailedException $exception) {
            throw $this->unwrapHandlerFailedException($exception);
        }
    }

    private function generateApiKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function unwrapHandlerFailedException(HandlerFailedException $exception): Throwable
    {
        $current = $exception;

        while ($current->getPrevious() !== null) {
            $current = $current->getPrevious();
        }

        return $current;
    }
}
