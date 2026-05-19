<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Gwo\AppsRecruitmentTask\Util\StringId;

interface EnrollmentRequestRepositoryInterface
{
    public function queue(StringId $requestId, StringId $lectureId, StringId $studentId): void;

    public function markProcessing(StringId $requestId): void;

    public function markCompleted(StringId $requestId): void;

    public function markFailed(StringId $requestId, ApiErrorCode $failureCode, string $failureMessage): void;

    public function getByIdForStudent(StringId $requestId, StringId $studentId): ?EnrollmentRequest;

    public function hasActiveForStudentAndLecture(StringId $lectureId, StringId $studentId): bool;
}
