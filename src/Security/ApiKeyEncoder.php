<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Security;

final readonly class ApiKeyEncoder
{
    public function __construct(
        private string $secret,
    ) {
    }

    public function encode(string $apiKey): string
    {
        return hash_hmac('sha256', $apiKey, $this->secret);
    }
}
