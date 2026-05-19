<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Security;

use Gwo\AppsRecruitmentTask\Controller\Dto\ErrorResponseDto;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Gwo\AppsRecruitmentTask\User\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class HeaderUserAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private const API_KEY_HEADER = 'X-Api-Key';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    #[\Override]
    public function supports(Request $request): bool
    {
        return $request->headers->has(self::API_KEY_HEADER);
    }

    #[\Override]
    public function authenticate(Request $request): SelfValidatingPassport
    {
        $apiKey = $request->headers->get(self::API_KEY_HEADER);

        if ($apiKey === null || trim($apiKey) === '') {
            throw new CustomUserMessageAuthenticationException(
                sprintf('Missing or empty %s header.', self::API_KEY_HEADER),
            );
        }

        return new SelfValidatingPassport(
            new UserBadge(
                $apiKey,
                function (string $presentedApiKey) {
                    $user = $this->userRepository->getByApiKey($presentedApiKey);

                    if ($user === null) {
                        throw new CustomUserMessageAuthenticationException('Invalid API key.');
                    }

                    return $user;
                },
            ),
        );
    }

    #[\Override]
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return null;
    }

    #[\Override]
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): Response {
        return $this->createUnauthorizedResponse($exception->getMessageKey());
    }

    #[\Override]
    public function start(
        Request $request,
        ?AuthenticationException $authException = null
    ): Response {
        return $this->createUnauthorizedResponse(
            sprintf('Authentication required. Provide %s header.', self::API_KEY_HEADER),
        );
    }

    private function createUnauthorizedResponse(string $message): JsonResponse
    {
        return new JsonResponse(
            new ErrorResponseDto(
                error: ApiErrorCode::UNAUTHORIZED,
                message: $message,
            ),
            Response::HTTP_UNAUTHORIZED,
        );
    }
}
