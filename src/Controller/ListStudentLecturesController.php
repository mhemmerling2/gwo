<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller;

use Gwo\AppsRecruitmentTask\Controller\Dto\LectureListResponseDto;
use Gwo\AppsRecruitmentTask\Lecture\GetStudentLecturesHandler;
use Gwo\AppsRecruitmentTask\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/students/me/lectures', name: 'student_lectures_list', methods: ['GET'])]
final readonly class ListStudentLecturesController
{
    public function __construct(
        private GetStudentLecturesHandler $getStudentLecturesHandler,
    ) {
    }

    public function __invoke(#[CurrentUser] User $student): JsonResponse
    {
        $lectures = ($this->getStudentLecturesHandler)($student->getId());

        return new JsonResponse(data: LectureListResponseDto::fromLectures($lectures));
    }
}
