<?php

declare(strict_types=1);

namespace Gwo\AppsRecruitmentTask\Controller;

use Gwo\AppsRecruitmentTask\Controller\Mapper\LectureEnrollmentErrorResponseMapper;
use Gwo\AppsRecruitmentTask\Lecture\LectureEnrollmentException;
use Gwo\AppsRecruitmentTask\Lecture\RemoveStudentFromLectureCommand;
use Gwo\AppsRecruitmentTask\Messenger\CommandBus;
use Gwo\AppsRecruitmentTask\Messenger\HandlerFailedExceptionUnwrapper;
use Gwo\AppsRecruitmentTask\User\User;
use Gwo\AppsRecruitmentTask\Util\StringId;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/lectures/{lectureId}/enrollments/{studentId}', name: 'lecture_remove_student', methods: ['DELETE'])]
final readonly class RemoveStudentFromLectureController
{
    public function __construct(
        private CommandBus $commandBus,
        private LectureEnrollmentErrorResponseMapper $errorResponseMapper,
    ) {
    }

    public function __invoke(
        string $lectureId,
        string $studentId,
        #[CurrentUser] User $lecturer
    ): Response {
        try {
            $this->commandBus->dispatch(
                new RemoveStudentFromLectureCommand(
                    lectureId: new StringId(value: $lectureId),
                    studentId: new StringId(value: $studentId),
                    lecturerId: $lecturer->getId(),
                ),
            );
        } catch (LectureEnrollmentException $exception) {
            return $this->errorResponseMapper->map($exception);
        } catch (HandlerFailedException $exception) {
            $unwrappedException = HandlerFailedExceptionUnwrapper::unwrap(
                exception: $exception,
                preferredExceptionClasses: [LectureEnrollmentException::class],
            );

            if ($unwrappedException instanceof LectureEnrollmentException) {
                return $this->errorResponseMapper->map($unwrappedException);
            }

            throw $exception;
        }

        return new Response(content: '', status: Response::HTTP_NO_CONTENT);
    }
}
