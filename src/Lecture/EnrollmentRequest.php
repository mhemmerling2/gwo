<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use DateTimeImmutable;
use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;
use Gwo\AppsRecruitmentTask\Util\StringId;

final readonly class EnrollmentRequest
{
    public function __construct(
        private StringId $id,
        private StringId $lectureId,
        private StringId $studentId,
        private EnrollmentRequestStatus $status,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $processedAt = null,
        private ?ApiErrorCode $failureCode = null,
        private ?string $failureMessage = null,
    ) {
    }

    public function getId(): StringId
    {
        return $this->id;
    }

    public function getLectureId(): StringId
    {
        return $this->lectureId;
    }

    public function getStudentId(): StringId
    {
        return $this->studentId;
    }

    public function getStatus(): EnrollmentRequestStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getProcessedAt(): ?DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function getFailureCode(): ?ApiErrorCode
    {
        return $this->failureCode;
    }

    public function getFailureMessage(): ?string
    {
        return $this->failureMessage;
    }
}
