<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Security;

use Gwo\AppsRecruitmentTask\Security\HeaderUserAuthenticator;
use Gwo\AppsRecruitmentTask\User\User;
use Gwo\AppsRecruitmentTask\User\UserRepositoryInterface;
use Gwo\AppsRecruitmentTask\User\UserRole;
use Gwo\AppsRecruitmentTask\Util\StringId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final class HeaderUserAuthenticatorTest extends TestCase
{
    #[Test]
    public function itSupportsRequestsContainingApiKeyHeader(): void
    {
        $authenticator = new HeaderUserAuthenticator(new StubUserRepository());

        self::assertTrue($authenticator->supports(new Request(server: ['HTTP_X_API_KEY' => 'api-key-1'])));
        self::assertFalse($authenticator->supports(new Request()));
    }

    #[Test]
    public function itRejectsEmptyApiKeyHeader(): void
    {
        $authenticator = new HeaderUserAuthenticator(new StubUserRepository());

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Missing or empty X-Api-Key header.');

        $authenticator->authenticate(new Request(server: ['HTTP_X_API_KEY' => '   ']));
    }

    #[Test]
    public function itAuthenticatesExistingUserFromRepository(): void
    {
        $user = new User(new StringId('user-1'), 'Lecturer', 'api-key-1', UserRole::LECTURER);
        $authenticator = new HeaderUserAuthenticator(new StubUserRepository($user));

        $passport = $authenticator->authenticate(new Request(server: ['HTTP_X_API_KEY' => 'api-key-1']));

        self::assertSame($user, $passport->getUser());
    }

    #[Test]
    public function itReturnsUnauthorizedResponseOnAuthenticationFailure(): void
    {
        $authenticator = new HeaderUserAuthenticator(new StubUserRepository());

        $response = $authenticator->onAuthenticationFailure(
            new Request(),
            new CustomUserMessageAuthenticationException('Invalid API key.'),
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(
            '{"error":"unauthorized","message":"Invalid API key."}',
            $response->getContent(),
        );
    }

    #[Test]
    public function itStartsAuthenticationWithUnauthorizedResponse(): void
    {
        $authenticator = new HeaderUserAuthenticator(new StubUserRepository());

        $response = $authenticator->start(new Request());

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(
            '{"error":"unauthorized","message":"Authentication required. Provide X-Api-Key header."}',
            $response->getContent(),
        );
    }

    #[Test]
    public function itContinuesRequestAfterAuthenticationSuccess(): void
    {
        $authenticator = new HeaderUserAuthenticator(new StubUserRepository());

        self::assertNull(
            $authenticator->onAuthenticationSuccess(
                new Request(),
                $authenticator->createToken(
                    $authenticator->authenticate(new Request(server: ['HTTP_X_API_KEY' => 'api-key-1'])),
                    'main',
                ),
                'main',
            ),
        );
    }
}

final readonly class StubUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private ?User $user = new User(new StringId('user-1'), 'Lecturer', 'api-key-1', UserRole::LECTURER),
    ) {
    }

    public function save(User $user): void
    {
    }

    public function getById(StringId $id): ?User
    {
        if ($this->user === null) {
            return null;
        }

        return $this->user->getId()->equals($id) ? $this->user : null;
    }

    public function getByApiKey(string $apiKey): ?User
    {
        if ($this->user === null) {
            return null;
        }

        return $this->user->getApiKey() === $apiKey ? $this->user : null;
    }
}
