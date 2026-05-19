<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Security;

use Gwo\AppsRecruitmentTask\Controller\Dto\ErrorResponseDto;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Override;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

final class ApiAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    private const MESSAGE = 'Access denied. Insufficient permissions.';

    #[Override]
    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        return new JsonResponse(
            data: new ErrorResponseDto(
                error: ApiErrorCode::FORBIDDEN,
                message: self::MESSAGE,
            ),
            status: Response::HTTP_FORBIDDEN,
        );
    }
}
