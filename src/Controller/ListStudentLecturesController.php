<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller;

use Gwo\AppsRecruitmentTask\Controller\Dto\LectureListResponseDto;
use Gwo\AppsRecruitmentTask\Lecture\GetStudentLecturesQuery;
use Gwo\AppsRecruitmentTask\Lecture\Lecture;
use Gwo\AppsRecruitmentTask\Messenger\QueryBus;
use Gwo\AppsRecruitmentTask\User\User;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/students/me/lectures', name: 'student_lectures_list', methods: ['GET'])]
final readonly class ListStudentLecturesController
{
    public function __construct(
        private QueryBus $queryBus,
    ) {
    }

    public function __invoke(#[CurrentUser] User $student): JsonResponse
    {
        $lectures = $this->dispatchGetStudentLecturesQuery(
            new GetStudentLecturesQuery(studentId: $student->getId()),
        );

        return new JsonResponse(data: LectureListResponseDto::fromLectures($lectures));
    }

    /**
     * @return Lecture[]
     */
    private function dispatchGetStudentLecturesQuery(GetStudentLecturesQuery $query): array
    {
        $result = $this->queryBus->ask($query);

        if (!is_array($result)) {
            throw new RuntimeException('Get student lectures handler did not return an array result.');
        }

        foreach ($result as $lecture) {
            if (!$lecture instanceof Lecture) {
                throw new RuntimeException('Get student lectures handler returned invalid payload.');
            }
        }

        /** @var Lecture[] $result */
        return $result;
    }
}
