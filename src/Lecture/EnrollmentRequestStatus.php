<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Lecture;

enum EnrollmentRequestStatus: string
{
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
