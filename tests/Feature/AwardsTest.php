<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AwardCategory;
use App\Models\AwardRun;
use App\Models\ClassModel;
use App\Models\Mark;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\StudentAward;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\AwardCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AwardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_create_award_page(): void
    {
        $this->academicStructure(1);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('admin.awards.create'))
            ->assertOk()
            ->assertSee('Create award draft')
            ->assertSee('Top students per subject')
            ->assertSee('Subjects to award')
            ->assertSee('Weighted assessments')
            ->assertSee('Tie-breakers in priority order');
    }

    public function test_academic_awards_apply_cutoff_positions_and_ordered_tie_breaker(): void
    {
        [$year, $termOne, $termTwo, $class, $teacher, $subject] = $this->academicStructure(1);
        $alpha = $this->student('Alpha Student', 'A001', $class);
        $beta = $this->student('Beta Student', 'A002', $class);
        $gamma = $this->student('Gamma Student', 'A003', $class);

        foreach ([$alpha, $beta, $gamma] as $student) {
            StudentSubject::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'class_id' => $class->id, 'academic_year_id' => $year->id]);
        }
        $this->mark($alpha, $subject, $class, $teacher, $year, $termOne, 80, 80);
        $this->mark($beta, $subject, $class, $teacher, $year, $termOne, 80, 80);
        $this->mark($gamma, $subject, $class, $teacher, $year, $termOne, 70, 70);
        $this->mark($alpha, $subject, $class, $teacher, $year, $termTwo, 90, 90);
        $this->mark($beta, $subject, $class, $teacher, $year, $termTwo, 85, 85);

        $run = AwardRun::create([
            'type' => 'academic', 'title' => 'Academic Excellence', 'academic_year_id' => $year->id,
            'term_id' => $termOne->id, 'scope_type' => 'level', 'level' => 1, 'positions' => 2,
            'cutoff_percentage' => 75, 'calculation_mode' => 'selected_endterm', 'missing_marks_policy' => 'exclude',
            'tie_policy' => 'include_all', 'calculation_config' => ['tie_breakers' => [['term_id' => $termTwo->id, 'assessment' => 'endterm']]],
            'award_date' => now(), 'status' => 'draft',
        ]);

        $result = app(AwardCalculationService::class)->generate($run->load(['term', 'academicYear']));

        $this->assertSame([$alpha->id, $beta->id], $result['recipients']->pluck('student_id')->all());
        $this->assertSame([1, 2], $result['recipients']->pluck('position')->all());
        $this->assertSame(90.0, $result['recipients'][0]['tie_breakers'][0]['score']);
    }

    public function test_subject_awards_use_the_same_subject_mark_from_a_previous_term_as_the_tie_breaker(): void
    {
        [$year, $previousTerm, $currentTerm, $class, $teacher, $mathematics] = $this->academicStructure(1);
        $english = Subject::create(['name' => 'English', 'code' => 'ENG-TIE']);
        $alpha = $this->student('Alpha Student', 'TIE01', $class);
        $beta = $this->student('Beta Student', 'TIE02', $class);

        foreach ([$alpha, $beta] as $student) {
            foreach ([$mathematics, $english] as $subject) {
                StudentSubject::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'class_id' => $class->id, 'academic_year_id' => $year->id]);
            }
        }

        // Their current Mathematics marks tie. English points in the opposite direction,
        // proving the previous Mathematics mark—not an overall average—breaks the tie.
        $this->mark($alpha, $mathematics, $class, $teacher, $year, $previousTerm, 70, 70);
        $this->mark($beta, $mathematics, $class, $teacher, $year, $previousTerm, 80, 80);
        $this->mark($alpha, $english, $class, $teacher, $year, $previousTerm, 100, 100);
        $this->mark($beta, $english, $class, $teacher, $year, $previousTerm, 10, 10);
        $this->mark($alpha, $mathematics, $class, $teacher, $year, $currentTerm, 90, 90);
        $this->mark($beta, $mathematics, $class, $teacher, $year, $currentTerm, 90, 90);

        $run = AwardRun::create([
            'type' => 'academic', 'title' => 'Subject Prizes', 'academic_year_id' => $year->id,
            'term_id' => $currentTerm->id, 'scope_type' => 'level', 'level' => 1, 'positions' => 1,
            'cutoff_percentage' => 0, 'calculation_mode' => 'selected_endterm',
            'missing_marks_policy' => 'exclude', 'tie_policy' => 'include_all',
            'calculation_config' => [
                'ranking_basis' => 'subject',
                'subject_ids' => [$mathematics->id],
                'tie_breakers' => [['term_id' => $previousTerm->id, 'assessment' => 'endterm']],
            ],
            'award_date' => now(), 'status' => 'draft',
        ]);

        $result = app(AwardCalculationService::class)->generate($run->load(['term', 'academicYear']));

        $this->assertSame($beta->id, $result['recipients']->first()['student_id']);
        $this->assertSame(80.0, $result['recipients']->first()['tie_breakers'][0]['score']);
        $this->assertSame('Term 1 End-term Mathematics mark', $result['recipients']->first()['tie_breakers'][0]['label']);
        $this->assertSame('Mathematics', $result['recipients']->first()['subject_name']);
    }

    public function test_subject_awards_can_use_the_same_subject_midterm_mark_as_the_tie_breaker(): void
    {
        [$year, $term, , $class, $teacher, $mathematics] = $this->academicStructure(1);
        $english = Subject::create(['name' => 'English', 'code' => 'ENG-MID-TIE']);
        $alpha = $this->student('Alpha Midterm', 'MID01', $class);
        $beta = $this->student('Beta Midterm', 'MID02', $class);

        foreach ([$alpha, $beta] as $student) {
            foreach ([$mathematics, $english] as $subject) {
                StudentSubject::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'class_id' => $class->id, 'academic_year_id' => $year->id]);
            }
        }
        $this->mark($alpha, $mathematics, $class, $teacher, $year, $term, 88, 90);
        $this->mark($beta, $mathematics, $class, $teacher, $year, $term, 84, 90);
        $this->mark($alpha, $english, $class, $teacher, $year, $term, 10, 10);
        $this->mark($beta, $english, $class, $teacher, $year, $term, 100, 100);

        $run = AwardRun::create([
            'type' => 'academic', 'title' => 'Subject Prizes', 'academic_year_id' => $year->id,
            'term_id' => $term->id, 'scope_type' => 'level', 'level' => 1, 'positions' => 1,
            'cutoff_percentage' => 0, 'calculation_mode' => 'selected_endterm',
            'missing_marks_policy' => 'exclude', 'tie_policy' => 'include_all',
            'calculation_config' => [
                'ranking_basis' => 'subject',
                'subject_ids' => [$mathematics->id],
                'tie_breakers' => [['term_id' => $term->id, 'assessment' => 'midterm']],
            ],
            'award_date' => now(), 'status' => 'draft',
        ]);

        $winner = app(AwardCalculationService::class)->generate($run->load(['term', 'academicYear']))['recipients']->first();

        $this->assertSame($alpha->id, $winner['student_id']);
        $this->assertSame(88.0, $winner['tie_breakers'][0]['score']);
        $this->assertSame('Term 1 Midterm Mathematics mark', $winner['tie_breakers'][0]['label']);
    }

    public function test_all_levels_scope_selects_top_students_separately_for_each_form(): void
    {
        [$year, $term, , $formOne, $teacher, $subject] = $this->academicStructure(1);
        $formTwo = ClassModel::create(['name' => 'Form 2A', 'level' => 2, 'academic_year_id' => $year->id]);
        $students = [
            $this->student('Form One Best', 'F101', $formOne),
            $this->student('Form One Second', 'F102', $formOne),
            $this->student('Form Two Best', 'F201', $formTwo),
            $this->student('Form Two Second', 'F202', $formTwo),
        ];
        foreach ($students as $index => $student) {
            StudentSubject::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'class_id' => $student->current_class_id, 'academic_year_id' => $year->id]);
            $this->mark($student, $subject, $student->currentClass, $teacher, $year, $term, 90 - $index, 90 - $index);
        }
        $run = AwardRun::create([
            'type' => 'academic', 'title' => 'Top per Form', 'academic_year_id' => $year->id, 'term_id' => $term->id,
            'scope_type' => 'all_levels', 'positions' => 1, 'cutoff_percentage' => 0, 'calculation_mode' => 'selected_endterm',
            'missing_marks_policy' => 'exclude', 'tie_policy' => 'include_all', 'calculation_config' => [], 'award_date' => now(), 'status' => 'draft',
        ]);

        $result = app(AwardCalculationService::class)->generate($run->load(['term', 'academicYear']));

        $this->assertSame([$students[0]->id, $students[2]->id], $result['recipients']->pluck('student_id')->all());
        $this->assertSame([1, 1], $result['recipients']->pluck('position')->all());
    }

    public function test_all_levels_can_select_top_students_separately_for_every_subject(): void
    {
        [$year, $term, , $formOne, $teacher, $mathematics] = $this->academicStructure(1);
        $english = Subject::create(['name' => 'English', 'code' => 'ENG']);
        $formTwo = ClassModel::create(['name' => 'Form 2A', 'level' => 2, 'academic_year_id' => $year->id]);
        $students = [
            $this->student('Form One Alpha', 'S101', $formOne),
            $this->student('Form One Beta', 'S102', $formOne),
            $this->student('Form Two Alpha', 'S201', $formTwo),
            $this->student('Form Two Beta', 'S202', $formTwo),
        ];
        $scores = [[90, 96], [70, 95], [88, 92], [97, 75]];

        foreach ($students as $index => $student) {
            foreach ([$mathematics, $english] as $subject) {
                StudentSubject::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'class_id' => $student->current_class_id, 'academic_year_id' => $year->id]);
            }
            $this->mark($student, $mathematics, $student->currentClass, $teacher, $year, $term, $scores[$index][0], $scores[$index][0]);
            $this->mark($student, $english, $student->currentClass, $teacher, $year, $term, $scores[$index][1], $scores[$index][1]);
        }

        $run = AwardRun::create([
            'type' => 'academic', 'title' => 'Subject Prizes', 'academic_year_id' => $year->id, 'term_id' => $term->id,
            'scope_type' => 'all_levels', 'positions' => 1, 'cutoff_percentage' => 0, 'calculation_mode' => 'selected_endterm',
            'missing_marks_policy' => 'exclude', 'tie_policy' => 'include_all',
            'calculation_config' => ['ranking_basis' => 'subject'], 'award_date' => now(), 'status' => 'draft',
        ]);

        $result = app(AwardCalculationService::class)->generate($run->load(['term', 'academicYear']));
        $winners = $result['recipients']->map(fn (array $row) => [
            $row['student']->currentClass->level,
            $row['subject_name'],
            $row['student_name'],
            $row['award_title'],
        ])->values()->all();

        $this->assertCount(4, $winners);
        $this->assertContains([1, 'Mathematics', 'Form One Alpha', 'Best in Mathematics'], $winners);
        $this->assertContains([1, 'English', 'Form One Alpha', 'Best in English'], $winners);
        $this->assertContains([2, 'Mathematics', 'Form Two Beta', 'Best in Mathematics'], $winners);
        $this->assertContains([2, 'English', 'Form Two Alpha', 'Best in English'], $winners);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->post(route('admin.awards.store'), [
            'type' => 'academic',
            'title' => 'Subject Prizes',
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'scope_type' => 'all_levels',
            'positions' => 1,
            'cutoff_percentage' => 0,
            'calculation_mode' => 'selected_endterm',
            'missing_marks_policy' => 'exclude',
            'tie_policy' => 'include_all',
            'ranking_basis' => 'subject',
            'award_date' => now()->toDateString(),
            'parent_visible' => 1,
        ])->assertRedirect();

        $storedRun = AwardRun::latest('id')->firstOrFail();
        $this->assertCount(4, $storedRun->awards);
        $this->assertCount(2, $storedRun->awards->where('student_id', $students[0]->id));
        $this->assertSame(['English', 'Mathematics'], $storedRun->awards->where('student_id', $students[0]->id)->pluck('subject_name_snapshot')->sort()->values()->all());
    }

    public function test_excluded_students_include_their_exact_missing_marks(): void
    {
        [$year, $term, , $class, , $subject] = $this->academicStructure(1);
        $student = $this->student('Incomplete Student', 'MISS01', $class);
        StudentSubject::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
        ]);
        $run = AwardRun::create([
            'type' => 'academic', 'title' => 'Academic Excellence', 'academic_year_id' => $year->id,
            'term_id' => $term->id, 'scope_type' => 'level', 'level' => 1, 'positions' => 3,
            'cutoff_percentage' => 75, 'calculation_mode' => 'selected_midterm', 'missing_marks_policy' => 'exclude',
            'tie_policy' => 'include_all', 'calculation_config' => [], 'award_date' => now(), 'status' => 'draft',
        ]);

        $result = app(AwardCalculationService::class)->generate($run->load(['term', 'academicYear']));

        $this->assertSame('No usable marks', $result['excluded'][0]['reason']);
        $this->assertSame('Form 1A', $result['excluded'][0]['class_name']);
        $this->assertSame(['Term 1 Midterm: Mathematics mark missing'], $result['excluded'][0]['missing']);
    }

    public function test_parent_can_only_download_a_published_visible_certificate_for_a_linked_child(): void
    {
        [$year, $term, , $class] = $this->academicStructure(1);
        $student = $this->student('Award Winner', 'WIN01', $class);
        $parentUser = User::factory()->create(['role' => 'parent', 'status' => 'active']);
        $parent = ParentModel::create(['user_id' => $parentUser->id]);
        $parent->students()->attach($student->id, ['relationship' => 'parent']);
        $category = AwardCategory::where('name', 'Academic Excellence')->firstOrFail();
        $run = AwardRun::create([
            'award_category_id' => $category->id, 'type' => 'academic', 'title' => 'Academic Excellence',
            'academic_year_id' => $year->id, 'term_id' => $term->id, 'scope_type' => 'level', 'level' => 1,
            'positions' => 1, 'cutoff_percentage' => 75, 'calculation_mode' => 'selected_endterm',
            'calculation_config' => [], 'award_date' => now(), 'status' => 'draft', 'parent_visible' => true,
        ]);
        $award = StudentAward::create([
            'award_run_id' => $run->id, 'student_id' => $student->id, 'award_title' => $run->title,
            'student_name_snapshot' => 'Award Winner', 'admission_no_snapshot' => 'WIN01',
            'class_name_snapshot' => 'Form 1A', 'level_snapshot' => 1, 'position' => 1, 'main_score' => 91.5,
            'certificate_reference' => 'KIS-TEST-0001',
        ]);

        $this->actingAs($parentUser)->get(route('parent.awards.certificate', $award))->assertNotFound();
        $run->update(['status' => 'published', 'published_at' => now()]);
        $this->actingAs($parentUser)->get(route('parent.awards.certificate', $award))->assertOk()->assertHeader('content-type', 'application/pdf');

        $otherParent = User::factory()->create(['role' => 'parent', 'status' => 'active']);
        ParentModel::create(['user_id' => $otherParent->id]);
        $this->actingAs($otherParent)->get(route('parent.awards.certificate', $award))->assertForbidden();
    }

    public function test_admin_can_export_an_award_run_as_a_real_excel_workbook(): void
    {
        [$year, $term, , $class] = $this->academicStructure(1);
        $student = $this->student('Excel Award Winner', 'XLSX01', $class);
        $secondStudent = $this->student('Second Award Winner', 'XLSX02', $class);
        $run = AwardRun::create([
            'type' => 'academic', 'title' => 'Academic Excellence', 'academic_year_id' => $year->id,
            'term_id' => $term->id, 'scope_type' => 'level', 'level' => 1, 'positions' => 1,
            'cutoff_percentage' => 75, 'calculation_mode' => 'selected_endterm',
            'calculation_config' => [], 'generation_summary' => ['calculation_description' => 'Term 1 end-of-term average'],
            'award_date' => now(), 'status' => 'draft', 'parent_visible' => true,
        ]);
        StudentAward::create([
            'award_run_id' => $run->id, 'student_id' => $student->id, 'award_title' => $run->title,
            'student_name_snapshot' => 'Excel Award Winner', 'admission_no_snapshot' => 'XLSX01',
            'class_name_snapshot' => 'Form 1A', 'level_snapshot' => 1, 'position' => 1, 'main_score' => 93.25,
            'tie_breaker_scores' => [['label' => 'Term 1 Midterm', 'score' => 91.5]],
            'citation' => 'Awarded for outstanding academic excellence.',
            'certificate_reference' => 'KIS-XLSX-0001',
        ]);
        StudentAward::create([
            'award_run_id' => $run->id, 'student_id' => $secondStudent->id, 'award_title' => $run->title,
            'student_name_snapshot' => 'Second Award Winner', 'admission_no_snapshot' => 'XLSX02',
            'class_name_snapshot' => 'Form 1A', 'level_snapshot' => 1, 'position' => 2, 'main_score' => 90.5,
            'tie_breaker_scores' => [], 'citation' => 'Awarded for outstanding academic excellence.',
            'certificate_reference' => 'KIS-XLSX-0002',
        ]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $response = $this->actingAs($admin)->get(route('admin.awards.excel', $run));

        $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $content = $response->getContent();
        $this->assertStringStartsWith('PK', $content);
        $this->assertStringContainsString('[Content_Types].xml', $content);
        $this->assertStringContainsString('Excel Award Winner', $content);
        $this->assertStringContainsString('KIS-XLSX-0001', $content);

        $this->actingAs($admin)
            ->get(route('admin.awards.show', $run))
            ->assertOk()
            ->assertSee('Download Excel (Draft)')
            ->assertSee('One PDF (print together)')
            ->assertSee('ZIP (separate PDFs)');

        $combinedPdf = $this->actingAs($admin)->get(route('admin.awards.certificates-pdf', $run));
        $combinedPdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $combinedPdf->getContent());

        $certificates = $this->actingAs($admin)->get(route('admin.awards.certificates', $run));
        $certificates->assertOk()->assertStreamed()->assertHeader('content-type', 'application/zip');
        $archive = $certificates->streamedContent();
        $this->assertStringStartsWith('PK', $archive);
        $this->assertStringContainsString('excel-award-winner-KIS-XLSX-0001.pdf', $archive);
        $this->assertStringContainsString('second-award-winner-KIS-XLSX-0002.pdf', $archive);

        $archivePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('award-certificates-', true).'.zip';

        try {
            file_put_contents($archivePath, $archive);
            $zip = new \PharData($archivePath);
            $certificate = $zip['excel-award-winner-KIS-XLSX-0001.pdf']->getContent();
            $secondCertificate = $zip['second-award-winner-KIS-XLSX-0002.pdf']->getContent();
            $this->assertStringStartsWith('%PDF-', $certificate);
            $this->assertStringStartsWith('%PDF-', $secondCertificate);
            unset($zip);
        } finally {
            if (is_file($archivePath)) {
                unlink($archivePath);
            }
        }
    }

    public function test_headmaster_dashboard_shows_award_counts_and_shortcuts(): void
    {
        $headmaster = User::factory()->create(['role' => 'headmaster', 'status' => 'active']);

        $this->actingAs($headmaster)
            ->get(route('headmaster.dashboard'))
            ->assertOk()
            ->assertSee('Awards &amp; Recognition', escape: false)
            ->assertSee('Draft runs')
            ->assertSee('Published runs')
            ->assertSee('Recipients')
            ->assertSee(route('headmaster.awards.index', absolute: false))
            ->assertSee(route('headmaster.awards.create', absolute: false));
    }

    public function test_draft_award_lists_can_be_deleted_but_published_lists_are_protected(): void
    {
        [$year] = $this->academicStructure(1);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $draft = AwardRun::create([
            'type' => 'custom', 'title' => 'Temporary Draft', 'academic_year_id' => $year->id,
            'scope_type' => 'school', 'calculation_config' => [], 'award_date' => now(), 'status' => 'draft',
        ]);
        $published = AwardRun::create([
            'type' => 'custom', 'title' => 'Published Awards', 'academic_year_id' => $year->id,
            'scope_type' => 'school', 'calculation_config' => [], 'award_date' => now(), 'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.awards.destroy', $draft))
            ->assertRedirect(route('admin.awards.index'))
            ->assertSessionHas('success', 'Award draft deleted.');
        $this->assertDatabaseMissing('award_runs', ['id' => $draft->id]);

        $this->actingAs($admin)
            ->delete(route('admin.awards.destroy', $published))
            ->assertStatus(422);
        $this->assertDatabaseHas('award_runs', ['id' => $published->id, 'status' => 'published']);
    }

    public function test_admin_can_edit_a_draft_award_and_replace_its_recipients(): void
    {
        [$year] = $this->academicStructure(1);
        $class = ClassModel::where('academic_year_id', $year->id)->firstOrFail();
        $first = $this->student('First Recipient', 'EDIT01', $class);
        $second = $this->student('Second Recipient', 'EDIT02', $class);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $run = AwardRun::create([
            'type' => 'custom', 'title' => 'Original Award', 'academic_year_id' => $year->id,
            'scope_type' => 'school', 'calculation_config' => [], 'award_date' => '2026-08-20',
            'status' => 'draft', 'parent_visible' => true,
        ]);
        StudentAward::create([
            'award_run_id' => $run->id, 'student_id' => $first->id, 'award_title' => $run->title,
            'student_name_snapshot' => 'First Recipient', 'admission_no_snapshot' => 'EDIT01',
            'certificate_reference' => 'KIS-EDIT-0001', 'selection_source' => 'manual',
        ]);

        $this->actingAs($admin)->get(route('admin.awards.edit', $run))
            ->assertOk()
            ->assertSee('Edit award draft')
            ->assertSee('Original Award');

        $this->actingAs($admin)->put(route('admin.awards.update', $run), [
            'type' => 'custom',
            'title' => 'Updated Award',
            'academic_year_id' => $year->id,
            'scope_type' => 'school',
            'award_date' => '2026-08-31',
            'parent_visible' => 1,
            'citation' => 'For a newly recognised achievement.',
            'student_ids' => [$second->id],
        ])->assertRedirect(route('admin.awards.show', $run));

        $run->refresh()->load('awards');
        $this->assertSame('Updated Award', $run->title);
        $this->assertCount(1, $run->awards);
        $this->assertSame($second->id, $run->awards->first()->student_id);
        $this->assertSame('For a newly recognised achievement.', $run->awards->first()->citation);
    }

    public function test_draft_award_certificate_is_final_without_a_watermark_and_uses_only_the_honour_first_slogan(): void
    {
        [$year, $term, , $class] = $this->academicStructure(1);
        $student = $this->student('Certificate Student', 'CERT01', $class);
        $run = AwardRun::create([
            'type' => 'custom', 'title' => 'Achievement Award', 'academic_year_id' => $year->id,
            'term_id' => $term->id, 'scope_type' => 'school', 'calculation_config' => [],
            'award_date' => now(), 'status' => 'draft',
        ]);
        $award = StudentAward::create([
            'award_run_id' => $run->id, 'student_id' => $student->id, 'award_title' => $run->title,
            'student_name_snapshot' => 'Certificate Student', 'certificate_reference' => 'KIS-CERT-0001',
        ]);

        $html = view('pdf.award-certificate', ['run' => $run->load(['academicYear', 'term']), 'award' => $award, 'logoPath' => ''])->render();

        $this->assertStringContainsString('Honour First', $html);
        $this->assertStringNotContainsString('Cambridge Excellence', $html);
        $this->assertStringNotContainsString('DRAFT', $html);
    }

    private function academicStructure(int $level): array
    {
        $year = AcademicYear::create(['year_name' => '2026', 'active' => true, 'status' => 'open']);
        $termOne = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2026-01-10', 'end_date' => '2026-04-10', 'status' => 'active']);
        $termTwo = Term::create(['academic_year_id' => $year->id, 'name' => 'Term 2', 'start_date' => '2026-05-10', 'end_date' => '2026-08-10', 'status' => 'finalized']);
        $class = ClassModel::create(['name' => 'Form '.$level.'A', 'level' => $level, 'academic_year_id' => $year->id]);
        $teacherUser = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $teacher = Teacher::create(['user_id' => $teacherUser->id]);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH-'.$level]);

        return [$year, $termOne, $termTwo, $class, $teacher, $subject];
    }

    private function student(string $name, string $admission, ClassModel $class): Student
    {
        $user = User::factory()->create(['name' => $name, 'role' => 'student', 'status' => 'active']);

        return Student::create(['user_id' => $user->id, 'admission_no' => $admission, 'gender' => 'male', 'date_of_birth' => '2012-01-01', 'current_class_id' => $class->id]);
    }

    private function mark(Student $student, Subject $subject, ClassModel $class, Teacher $teacher, AcademicYear $year, Term $term, float $midterm, float $endterm): void
    {
        Mark::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'class_id' => $class->id, 'teacher_id' => $teacher->id, 'academic_year_id' => $year->id, 'term_id' => $term->id, 'midterm_score' => $midterm, 'endterm_score' => $endterm]);
    }
}
