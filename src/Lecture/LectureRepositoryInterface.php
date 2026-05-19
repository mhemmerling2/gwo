<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Util\StringId;

interface LectureRepositoryInterface
{
    public function save(Lecture $lecture): void;

    public function getById(StringId $id): ?Lecture;

    /**
     * @param list<StringId> $ids
     * @return list<Lecture>
     */
    public function getByIds(array $ids): array;
}
