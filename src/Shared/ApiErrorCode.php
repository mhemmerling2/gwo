<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Shared;

enum ApiErrorCode: string
{
    case UNAUTHORIZED = 'unauthorized';
    case INVALID_JSON = 'invalid_json';
    case INVALID_LECTURE_DATA = 'invalid_lecture_data';
    case INVALID_REQUEST = 'invalid_request';
    case LECTURE_NOT_FOUND = 'lecture_not_found';
    case LECTURE_STARTED = 'lecture_started';
    case ALREADY_ENROLLED = 'already_enrolled';
    case LECTURE_FULL = 'lecture_full';
    case ENROLLMENT_ERROR = 'enrollment_error';
    case FORBIDDEN = 'forbidden';
    case ENROLLMENT_NOT_FOUND = 'enrollment_not_found';
    case ENROLLMENT_REQUEST_NOT_FOUND = 'enrollment_request_not_found';
    case ENROLLMENT_IN_PROGRESS = 'enrollment_in_progress';
}
