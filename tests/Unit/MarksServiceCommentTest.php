<?php

namespace Tests\Unit;

use App\Services\MarksService;
use PHPUnit\Framework\TestCase;

class MarksServiceCommentTest extends TestCase
{
    private MarksService $marksService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marksService = new MarksService;
    }

    public function test_it_generates_a_comment_from_marks(): void
    {
        $this->assertSame(
            'The learner showed good performance in the midterm assessment but did not write the end-of-term assessment.',
            $this->marksService->generateTeacherComment(75, null)
        );

        $this->assertSame(
            'The learner has shown clear improvement since midterm. This progress is encouraging; continued effort is needed.',
            $this->marksService->generateTeacherComment(55, 70)
        );
    }

    public function test_it_refreshes_a_previously_generated_comment_when_marks_change(): void
    {
        $previousComment = $this->marksService->generateTeacherComment(55, 70);

        $this->assertSame(
            'The learner’s performance has declined significantly since midterm. Immediate improvement and greater commitment are required.',
            $this->marksService->resolveTeacherComment(
                75,
                65,
                $previousComment,
                55,
                70,
                $previousComment
            )
        );
    }

    public function test_endterm_marks_replace_a_stale_midterm_only_comment(): void
    {
        $midtermOnlyComment = $this->marksService->generateTeacherComment(72, null);

        $this->assertTrue(
            $this->marksService->isGeneratedTeacherComment($midtermOnlyComment)
        );

        $this->assertSame(
            'The learner’s performance has declined significantly since midterm. Immediate improvement and greater commitment are required.',
            $this->marksService->resolveTeacherComment(
                72,
                58,
                $midtermOnlyComment,
                72,
                58,
                $midtermOnlyComment
            )
        );
    }

    public function test_it_preserves_a_custom_teacher_comment_when_marks_change(): void
    {
        $this->assertSame(
            'Good participation, but homework needs attention.',
            $this->marksService->resolveTeacherComment(
                75,
                65,
                'Good participation, but homework needs attention.',
                55,
                70,
                'Good participation, but homework needs attention.'
            )
        );
    }

    public function test_it_removes_the_generated_comment_when_all_marks_are_cleared(): void
    {
        $previousComment = $this->marksService->generateTeacherComment(80, null);

        $this->assertNull(
            $this->marksService->resolveTeacherComment(
                null,
                null,
                $previousComment,
                80,
                null,
                $previousComment
            )
        );
    }
}
