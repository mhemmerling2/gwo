<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller\Dto;

use Gwo\AppsRecruitmentTask\Lecture\EnrollStudentToLectureCommand;
use Gwo\AppsRecruitmentTask\Lecture\EnrollmentRequestStatus;
use RuntimeException;

final readonly class QueuedEnrollmentResponseDto implements \JsonSerializable
{
    public function __construct(
        private string $requestId,
        private EnrollmentRequestStatus $status,
        private string $lectureId,
        private string $studentId,
    ) {
    }

    public static function fromCommand(EnrollStudentToLectureCommand $command): self
    {
        if ($command->requestId === null) {
            throw new RuntimeException('Queued enrollment response requires a request id.');
        }

        return new self(
            requestId: (string) $command->requestId,
            status: EnrollmentRequestStatus::QUEUED,
            lectureId: (string) $command->lectureId,
            studentId: (string) $command->studentId,
        );
    }

    /**
     * @return array{requestId: string, status: string, lectureId: string, studentId: string}
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'requestId' => $this->requestId,
            'status' => $this->status->value,
            'lectureId' => $this->lectureId,
            'studentId' => $this->studentId,
        ];
    }
}
