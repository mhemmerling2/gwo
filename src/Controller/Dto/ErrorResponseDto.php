<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller\Dto;

use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;

final readonly class ErrorResponseDto implements \JsonSerializable
{
    public function __construct(
        private ApiErrorCode $error,
        private string $message,
    ) {
    }

    /**
     * @return array{error: string, message: string}
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'error' => $this->error->value,
            'message' => $this->message,
        ];
    }
}
