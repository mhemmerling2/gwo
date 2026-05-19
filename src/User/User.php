<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\User;

use Gwo\AppsRecruitmentTask\Util\StringId;
use LogicException;
use Override;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @phpstan-type SecurityRole non-empty-string
 */
final readonly class User implements UserInterface
{
    public function __construct(
        private StringId $id,
        private string $name,
        private string $apiKey,
        private UserRole $role,
    ) {
    }

    public function getId(): StringId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function getRoles(): array
    {
        return [$this->role->toSecurityRole()];
    }

    #[Override]
    public function getUserIdentifier(): string
    {
        $identifier = (string) $this->id;

        if ($identifier === '') {
            throw new LogicException('User identifier cannot be empty.');
        }

        return $identifier;
    }
}
