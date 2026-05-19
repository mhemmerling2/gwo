<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

use Gwo\AppsRecruitmentTask\Shared\ApiErrorCode;

final class LectureEnrollmentException extends \DomainException
{
    private function __construct(
        string $message,
        private readonly ApiErrorCode $errorCode,
    ) {
        parent::__construct($message);
    }

    public static function lectureNotFound(): self
    {
        return new self('Lecture not found.', ApiErrorCode::LECTURE_NOT_FOUND);
    }

    public static function lectureStarted(): self
    {
        return new self('Cannot enroll to a lecture that has already started.', ApiErrorCode::LECTURE_STARTED);
    }

    public static function alreadyEnrolled(): self
    {
        return new self('Student is already enrolled to this lecture.', ApiErrorCode::ALREADY_ENROLLED);
    }

    public static function lectureFull(): self
    {
        return new self('Lecture student limit exceeded.', ApiErrorCode::LECTURE_FULL);
    }

    public static function forbiddenRemoval(): self
    {
        return new self(
            'Lecturer can remove students only from own lectures.',
            ApiErrorCode::FORBIDDEN,
        );
    }

    public static function enrollmentNotFound(): self
    {
        return new self('Student is not enrolled to this lecture.', ApiErrorCode::ENROLLMENT_NOT_FOUND);
    }

    public static function couldNotPersist(): self
    {
        return new self('Enrollment could not be persisted.', ApiErrorCode::ENROLLMENT_ERROR);
    }

    public function getErrorCode(): ApiErrorCode
    {
        return $this->errorCode;
    }
}
