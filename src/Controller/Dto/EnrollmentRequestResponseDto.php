<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller\Dto;

use Gwo\AppsRecruitmentTask\Lecture\EnrollmentRequest;
use JsonSerializable;
use Override;

final readonly class EnrollmentRequestResponseDto implements JsonSerializable
{
    public function __construct(
        private string $requestId,
        private string $status,
        private string $lectureId,
        private string $studentId,
        private string $createdAt,
        private string $updatedAt,
        private ?string $processedAt,
        private ?string $failureCode,
        private ?string $failureMessage,
    ) {
    }

    public static function fromEnrollmentRequest(EnrollmentRequest $request): self
    {
        return new self(
            requestId: (string) $request->getId(),
            status: $request->getStatus()->value,
            lectureId: (string) $request->getLectureId(),
            studentId: (string) $request->getStudentId(),
            createdAt: $request->getCreatedAt()->format(DATE_ATOM),
            updatedAt: $request->getUpdatedAt()->format(DATE_ATOM),
            processedAt: $request->getProcessedAt()?->format(DATE_ATOM),
            failureCode: $request->getFailureCode()?->value,
            failureMessage: $request->getFailureMessage(),
        );
    }

    /**
     * @return array{
     *   requestId: string,
     *   status: string,
     *   lectureId: string,
     *   studentId: string,
     *   createdAt: string,
     *   updatedAt: string,
     *   processedAt: string|null,
     *   failureCode: string|null,
     *   failureMessage: string|null
     * }
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'requestId' => $this->requestId,
            'status' => $this->status,
            'lectureId' => $this->lectureId,
            'studentId' => $this->studentId,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'processedAt' => $this->processedAt,
            'failureCode' => $this->failureCode,
            'failureMessage' => $this->failureMessage,
        ];
    }
}
