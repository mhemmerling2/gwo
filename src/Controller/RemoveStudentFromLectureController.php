<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller;

use Gwo\AppsRecruitmentTask\Controller\Mapper\LectureEnrollmentErrorResponseMapper;
use Gwo\AppsRecruitmentTask\Lecture\LectureEnrollmentException;
use Gwo\AppsRecruitmentTask\Lecture\RemoveStudentFromLectureHandler;
use Gwo\AppsRecruitmentTask\User\User;
use Gwo\AppsRecruitmentTask\Util\StringId;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route(
    '/lectures/{lectureId}/enrollments/{studentId}',
    name: 'lecture_remove_student',
    methods: ['DELETE'],
    requirements: [
        'lectureId' => Requirement::UUID,
        'studentId' => Requirement::UUID,
    ],
)]
final readonly class RemoveStudentFromLectureController
{
    public function __construct(
        private RemoveStudentFromLectureHandler $removeStudentFromLectureHandler,
        private LectureEnrollmentErrorResponseMapper $errorResponseMapper,
    ) {
    }

    public function __invoke(
        string $lectureId,
        string $studentId,
        #[CurrentUser] User $lecturer,
    ): Response {
        try {
            ($this->removeStudentFromLectureHandler)(
                lectureId: StringId::fromRoute($lectureId),
                studentId: StringId::fromRoute($studentId),
                lecturerId: $lecturer->getId(),
            );
        } catch (LectureEnrollmentException $exception) {
            return $this->errorResponseMapper->map($exception);
        }

        return new Response(content: '', status: Response::HTTP_NO_CONTENT);
    }
}
