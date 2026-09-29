<?php

namespace Tests\Feature;

use App\Models\ParentModel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentProfileOverviewTest extends TestCase
{
    use RefreshDatabase;

    private function student(): Student
    {
        return Student::create([
            'user_id' => User::factory()->create(['name' => 'Profile Learner', 'role' => 'student', 'status' => 'active'])->id,
            'admission_no' => 'PROFILE-001', 'gender' => 'female', 'date_of_birth' => '2012-01-01',
            'nationality' => 'Botswana', 'identity_document_type' => 'birth_certificate', 'identity_document_number' => 'BC-PROFILE',
            'photo' => 'students/profile.jpg', 'emergency_contact_name' => 'Emergency Guardian',
            'emergency_contact_relationship' => 'Aunt', 'emergency_contact_phone' => '71234567',
            'emergency_contact_alt_phone' => '72345678', 'emergency_contact_address' => 'Emergency address example',
            'medical_notes' => 'Recorded allergy',
        ]);
    }

    public function test_staff_profiles_show_photo_contacts_and_results_and_return_to_filtered_list(): void
    {
        $student = $this->student();
        foreach (['admin', 'headmaster'] as $role) {
            $staff = User::factory()->create(['role' => $role, 'status' => 'active']);
            $response = $this->actingAs($staff)->get(route($role.'.students.show', ['student' => $student, 'search' => 'Profile', 'page' => 2]));
            $response->assertOk()->assertSee('students/profile.jpg')->assertSee('Emergency Guardian')
                ->assertSee('72345678')->assertSee('Emergency address example')->assertSee('Recorded allergy')
                ->assertSee('Academic progress')->assertSee('No marks recorded for this term.')
                ->assertSee('Attendance and support')->assertSee('No attendance recorded for this term.')
                ->assertSee(route($role.'.students.index', ['search' => 'Profile', 'page' => 2]));
            // Profile Back must be deterministic even after changing terms or saving.
            $response->assertDontSee('window.history.back()', escape: false);
        }
    }

    public function test_profile_photo_uses_the_site_address_instead_of_the_configured_storage_host(): void
    {
        $student = $this->student();
        config(['filesystems.disks.public.url' => 'http://localhost/storage']);
        \Illuminate\Support\Facades\URL::forceRootUrl('http://school.test:8080');

        try {
            $html = \Illuminate\Support\Facades\Blade::render('<x-student-photo :student="$student" />', compact('student'));
            $this->assertStringContainsString('src="http://school.test:8080/storage/students/profile.jpg"', $html);
            $this->assertStringNotContainsString('src="http://localhost/', $html);
        } finally {
            \Illuminate\Support\Facades\URL::forceRootUrl(null);
        }
    }

    public function test_admin_can_upload_photo_and_returns_to_profile_after_save(): void
    {
        Storage::fake('public');
        $student = $this->student();
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->put(route('admin.students.update', ['student' => $student, 'search' => 'Profile']), [
            'name' => $student->user->name, 'email' => $student->user->email,
            'gender' => $student->gender, 'date_of_birth' => '2012-01-01',
            'nationality' => 'Botswana', 'identity_document_type' => 'birth_certificate', 'identity_document_number' => 'BC-PROFILE',
            'photo' => UploadedFile::fake()->image('portrait.jpg'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.students.show', ['student' => $student, 'search' => 'Profile']));
        $student->refresh();
        Storage::disk('public')->assertExists($student->photo);
        $this->get(route('admin.students.edit', ['student' => $student, 'search' => 'Profile']))
            ->assertOk()->assertSee(route('admin.students.show', ['student' => $student, 'search' => 'Profile']));
    }

    public function test_admin_can_upload_and_replace_only_the_photo_with_missing_identity_details(): void
    {
        Storage::fake('public');
        $student = $this->student();
        $student->update([
            'nationality' => null, 'identity_document_type' => null,
            'identity_document_number' => null, 'photo' => null,
            'results_access' => true, 'fees_blocked' => true,
        ]);
        $before = $student->fresh()->getAttributes();
        unset($before['photo'], $before['updated_at']);
        $userBefore = $student->user->getAttributes();
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $context = ['student' => $student, 'search' => 'Profile', 'class_id' => 4, 'page' => 2, 'term_id' => 3];

        foreach (['first.jpg', 'replacement.png'] as $filename) {
            $previousPhoto = $student->photo;
            $this->actingAs($admin)->put(route('admin.students.photo.update', $context), [
                'photo' => UploadedFile::fake()->image($filename),
                'nationality' => 'Must not overwrite', 'results_access' => false,
            ])->assertSessionHasNoErrors()->assertRedirect(route('admin.students.edit', $context));
            $student->refresh();
            Storage::disk('public')->assertExists($student->photo);
            if ($previousPhoto) {
                Storage::disk('public')->assertMissing($previousPhoto);
            }
            $after = $student->getAttributes();
            unset($after['photo'], $after['updated_at']);
            $this->assertSame($before, $after);
            $this->assertSame($userBefore, $student->user->getAttributes());
        }

        $this->get(route('admin.students.edit', $context))->assertOk()
            ->assertSee('Save Photo')->assertSee(route('admin.students.photo.update', $context));
    }

    public function test_photo_update_rejects_invalid_files_and_non_admins_without_losing_existing_photo(): void
    {
        Storage::fake('public');
        $student = $this->student();
        Storage::disk('public')->put($student->photo, 'existing photo');
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        foreach ([null, UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'), UploadedFile::fake()->image('large.jpg')->size(2049)] as $photo) {
            $this->actingAs($admin)->put(route('admin.students.photo.update', $student), ['photo' => $photo])
                ->assertSessionHasErrors('photo');
            $this->assertSame('students/profile.jpg', $student->fresh()->photo);
            Storage::disk('public')->assertExists('students/profile.jpg');
        }
        $parent = User::factory()->create(['role' => 'parent', 'status' => 'active']);
        $this->actingAs($parent)->put(route('admin.students.photo.update', $student), [
            'photo' => UploadedFile::fake()->image('portrait.jpg'),
        ])->assertForbidden();
        $this->assertSame('students/profile.jpg', $student->fresh()->photo);
    }

    public function test_parent_can_see_only_linked_profile_and_cannot_replace_photo(): void
    {
        $student = $this->student();
        $parentUser = User::factory()->create(['role' => 'parent', 'status' => 'active']);
        $parent = ParentModel::create(['user_id' => $parentUser->id]);
        $parent->students()->attach($student->id, ['relationship' => 'parent']);
        $this->actingAs($parentUser)->get(route('parent.children.profile.edit', $student))
            ->assertOk()->assertSee('students/profile.jpg')->assertSee('Student photos are managed by school staff.')
            ->assertDontSee('type="file"', escape: false);
        $this->put(route('parent.children.profile.update', $student), [
            'nationality' => 'Botswana', 'identity_document_type' => 'birth_certificate', 'identity_document_number' => 'BC-PROFILE',
            'emergency_contact_name' => 'Updated contact', 'emergency_contact_relationship' => 'Mother',
            'emergency_contact_phone' => '73456789', 'photo' => 'students/unauthorized.jpg',
        ])->assertSessionHasNoErrors();
        $this->assertSame('students/profile.jpg', $student->fresh()->photo);
        $this->assertSame('Updated contact', $student->fresh()->emergency_contact_name);
        $this->get(route('admin.students.edit', $student))->assertForbidden();
        $this->get(route('headmaster.students.show', $student))->assertForbidden();
        $parent->students()->detach($student);
        $this->get(route('parent.children.profile.edit', $student))->assertForbidden();
    }
}
