<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\User;

use Gwo\AppsRecruitmentTask\Util\StringId;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function getById(StringId $id): ?User;

    public function getByApiKey(string $apiKey): ?User;
}
