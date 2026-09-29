<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\ParentModel;
use App\Models\PrefectAppointment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PrefectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_appoint_edit_and_print_a_prefect_certificate(): void
    {
        [$year, $class, $student] = $this->schoolData();
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)->get(route('admin.prefects.create'))
            ->assertOk()
            ->assertSee('Appoint a prefect')
            ->assertSee($student->user->name);

        $this->actingAs($admin)->post(route('admin.prefects.store'), [
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'title' => 'Library Prefect',
            'duties' => 'Assist with daily library order and guide students during borrowing periods.',
            'appointed_on' => '2026-08-01',
            'service_ends_on' => '2026-11-30',
            'status' => 'active',
            'notes' => 'Review at the end of term.',
        ])->assertRedirect(route('admin.prefects.index'));

        $prefect = PrefectAppointment::firstOrFail();
        $this->assertSame($class->name, $prefect->class_name_snapshot);
        $this->assertStringStartsWith('KIS-PREF-', $prefect->certificate_reference);

        $this->actingAs($admin)->get(route('admin.prefects.index'))
            ->assertOk()
            ->assertSee('Library Prefect')
            ->assertSee('Assist with daily library order');

        $this->actingAs($admin)->put(route('admin.prefects.update', $prefect), [
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'title' => 'Senior Library Prefect',
            'duties' => 'Coordinate the library prefect team and support orderly borrowing.',
            'appointed_on' => '2026-08-01',
            'service_ends_on' => '2026-11-30',
            'status' => 'completed',
            'notes' => null,
        ])->assertRedirect(route('admin.prefects.index'));

        $this->assertDatabaseHas('prefect_appointments', [
            'id' => $prefect->id,
            'title' => 'Senior Library Prefect',
            'status' => 'completed',
        ]);

        $this->actingAs($admin)->get(route('admin.prefects.certificate', $prefect))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_prefect_appears_on_student_parent_and_admin_student_profiles(): void
    {
        [$year, , $student] = $this->schoolData();
        $prefect = $this->prefect($student, $year);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $parentUser = User::factory()->create(['role' => 'parent', 'status' => 'active']);
        $parent = ParentModel::create(['user_id' => $parentUser->id]);
        $parent->students()->attach($student->id, ['relationship' => 'parent']);

        $this->actingAs($student->user)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Student leadership')
            ->assertSee('Senior Prefect')
            ->assertSee('Support morning assembly and corridor supervision.')
            ->assertSee(route('student.prefects.certificate', $prefect, absolute: false));

        $this->actingAs($parentUser)->get(route('parent.awards.index'))
            ->assertOk()
            ->assertSee('Prefect leadership')
            ->assertSee('Senior Prefect')
            ->assertSee(route('parent.prefects.certificate', $prefect, absolute: false));

        $this->actingAs($admin)->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSee('Prefect Leadership')
            ->assertSee('Senior Prefect');
    }

    public function test_prefect_certificate_is_limited_to_the_student_linked_parent_and_school_leaders(): void
    {
        [$year, , $student] = $this->schoolData();
        $prefect = $this->prefect($student, $year);
        $parentUser = User::factory()->create(['role' => 'parent', 'status' => 'active']);
        $parent = ParentModel::create(['user_id' => $parentUser->id]);
        $parent->students()->attach($student->id, ['relationship' => 'parent']);
        $otherParentUser = User::factory()->create(['role' => 'parent', 'status' => 'active']);
        ParentModel::create(['user_id' => $otherParentUser->id]);

        $this->actingAs($student->user)->get(route('student.prefects.certificate', $prefect))->assertOk();
        $this->actingAs($parentUser)->get(route('parent.prefects.certificate', $prefect))->assertOk();
        $this->actingAs($otherParentUser)->get(route('parent.prefects.certificate', $prefect))->assertForbidden();

        $prefect->update(['status' => 'revoked']);
        $this->actingAs($student->user)->get(route('student.prefects.certificate', $prefect))->assertNotFound();
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->get(route('admin.prefects.certificate', $prefect))->assertOk();
    }

    public function test_parent_mobile_dashboard_includes_prefect_duties_and_certificate(): void
    {
        [$year, , $student] = $this->schoolData();
        $prefect = $this->prefect($student, $year);
        $parentUser = User::factory()->create(['role' => 'parent', 'status' => 'active']);
        $parent = ParentModel::create(['user_id' => $parentUser->id]);
        $parent->students()->attach($student->id, ['relationship' => 'parent']);
        Sanctum::actingAs($parentUser);

        $this->getJson('/api/parent/dashboard')
            ->assertOk()
            ->assertJsonPath('children.0.prefect_appointments.0.title', 'Senior Prefect')
            ->assertJsonPath('children.0.prefect_appointments.0.duties', 'Support morning assembly and corridor supervision.')
            ->assertJsonPath('children.0.prefect_appointments.0.certificate_reference', $prefect->certificate_reference);

        $this->get("/api/parent/children/{$student->id}/prefects/{$prefect->id}/certificate")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_prefect_certificate_uses_honour_first_and_prints_the_assigned_duties(): void
    {
        [$year, , $student] = $this->schoolData();
        $prefect = $this->prefect($student, $year)->load(['student.user', 'student.currentClass', 'academicYear']);

        $html = view('pdf.prefect-certificate', ['prefect' => $prefect, 'logoPath' => ''])->render();

        $this->assertStringContainsString('Honour First', $html);
        $this->assertStringContainsString('Certificate of Appointment', $html);
        $this->assertStringContainsString(PrefectAppointment::CERTIFICATE_CITATION_PREFECT, $html);
        $this->assertStringContainsString('Support morning assembly and corridor supervision.', $html);
        $this->assertStringNotContainsString('Cambridge Excellence', $html);
    }

    public function test_prefect_certificate_citations_are_selected_from_the_leadership_role(): void
    {
        $citations = [
            'Prefect' => PrefectAppointment::CERTIFICATE_CITATION_PREFECT,
            'Library Prefect' => PrefectAppointment::CERTIFICATE_CITATION_PREFECT,
            'Deputy Head Girl' => PrefectAppointment::CERTIFICATE_CITATION_DEPUTY_HEAD_GIRL,
            'Deputy Head Boy' => PrefectAppointment::CERTIFICATE_CITATION_DEPUTY_HEAD_BOY,
            'Head Girl' => PrefectAppointment::CERTIFICATE_CITATION_HEAD_GIRL,
            'Head Boy' => PrefectAppointment::CERTIFICATE_CITATION_HEAD_BOY,
            '  deputy   head girl  ' => PrefectAppointment::CERTIFICATE_CITATION_DEPUTY_HEAD_GIRL,
        ];

        foreach ($citations as $title => $expected) {
            $prefect = new PrefectAppointment(['title' => $title]);
            $this->assertSame($expected, $prefect->certificateCitation());
        }
    }

    public function test_school_leaders_can_download_filtered_prefect_certificates_as_one_pdf(): void
    {
        [$year, $class, $student] = $this->schoolData();
        $this->prefect($student, $year);

        $secondUser = User::factory()->create(['name' => 'Second Student Leader', 'role' => 'student', 'status' => 'active']);
        $secondStudent = Student::create([
            'user_id' => $secondUser->id,
            'admission_no' => 'PREF02',
            'gender' => 'male',
            'date_of_birth' => '2010-02-02',
            'current_class_id' => $class->id,
            'results_access' => true,
        ]);
        PrefectAppointment::create([
            'student_id' => $secondStudent->id,
            'academic_year_id' => $year->id,
            'title' => 'Head Boy',
            'duties' => 'Lead assemblies and coordinate the prefect body.',
            'appointed_on' => '2026-08-01',
            'status' => 'active',
            'class_name_snapshot' => $class->name,
            'certificate_reference' => 'KIS-PREF-TEST-0002',
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $index = $this->actingAs($admin)->get(route('admin.prefects.index', ['academic_year_id' => $year->id]));
        $index->assertOk()
            ->assertSee('Download certificates (one PDF)')
            ->assertSee(route('admin.prefects.certificates-pdf', ['academic_year_id' => $year->id], absolute: false));

        $response = $this->actingAs($admin)->get(route('admin.prefects.certificates-pdf', ['academic_year_id' => $year->id]));
        $response->assertOk()->assertHeader('content-type', 'application/pdf');

        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
        preg_match_all('/\/Type\s*\/Page\b/', $content, $pages);
        $this->assertCount(2, $pages[0]);

        $headmaster = User::factory()->create(['role' => 'headmaster', 'status' => 'active']);
        $this->actingAs($headmaster)
            ->get(route('headmaster.prefects.certificates-pdf', ['academic_year_id' => $year->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function schoolData(): array
    {
        $year = AcademicYear::create(['year_name' => '2026', 'active' => true, 'status' => 'open']);
        $class = ClassModel::create(['name' => 'Form 4A', 'level' => 4, 'academic_year_id' => $year->id]);
        $user = User::factory()->create(['name' => 'Student Leader', 'role' => 'student', 'status' => 'active']);
        $student = Student::create([
            'user_id' => $user->id,
            'admission_no' => 'PREF01',
            'gender' => 'female',
            'date_of_birth' => '2010-01-01',
            'current_class_id' => $class->id,
            'results_access' => true,
        ]);

        return [$year, $class, $student->load('user')];
    }

    private function prefect(Student $student, AcademicYear $year): PrefectAppointment
    {
        return PrefectAppointment::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'title' => 'Senior Prefect',
            'duties' => 'Support morning assembly and corridor supervision.',
            'appointed_on' => '2026-08-01',
            'status' => 'active',
            'class_name_snapshot' => $student->currentClass?->name,
            'certificate_reference' => 'KIS-PREF-TEST-0001',
        ]);
    }
}
