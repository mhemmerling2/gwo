<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Tests\Security;

use Gwo\AppsRecruitmentTask\Security\ApiAccessDeniedHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ApiAccessDeniedHandlerTest extends TestCase
{
    #[Test]
    public function itReturnsForbiddenJsonResponse(): void
    {
        $handler = new ApiAccessDeniedHandler();

        $response = $handler->handle(new Request(), new AccessDeniedException('Nope.'));

        self::assertNotNull($response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame(
            '{"error":"forbidden","message":"Access denied. Insufficient permissions."}',
            $response->getContent(),
        );
    }
}
