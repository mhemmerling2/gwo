<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Util;

use Ramsey\Uuid\Uuid;

final readonly class StringId implements \Stringable
{
    public function __construct(
        private string $value,
    ) {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('StringId value cannot be empty.');
        }
    }

    public static function new(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $another): bool
    {
        return $this->value === $another->value;
    }
}
