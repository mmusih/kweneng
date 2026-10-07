<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\Tt\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ParentDashboardTimetableTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 2)->startOfDay());
        $year = AcademicYear::create(['year_name' => '2026', 'active' => true, 'status' => 'open']);
        foreach ([Setting::TYPE_DAY => 6, Setting::TYPE_AFTERNOON => 5] as $type => $length) {
            Setting::factory()->published()->create([
                'academic_year_id' => $year->id,
                'schedule_type' => $type,
                'cycle_length' => $length,
                'cycle_anchor_date' => '2026-09-28',
                'cycle_anchor_day' => 1,
            ]);
        }
        $user = User::factory()->create(['role' => 'parent', 'status' => 'active']);
        $parent = ParentModel::create(['user_id' => $user->id]);
        $this->student = Student::create([
            'user_id' => User::factory()->create(['role' => 'student', 'status' => 'active'])->id,
            'admission_no' => 'PARENT-TIMETABLE-001',
            'gender' => 'female',
            'date_of_birth' => '2010-01-01',
        ]);
        $parent->students()->attach($this->student->id, ['relationship' => 'parent']);
        Sanctum::actingAs($user);
    }

    public function test_home_and_both_independent_timetables_load(): void
    {
        $this->getJson('/api/parent/dashboard')->assertOk()
            ->assertJsonPath('day_label', 'Friday|Day 5')
            ->assertJsonPath('children.0.id', $this->student->id);

        $this->getJson('/api/parent/timetable?student_id='.$this->student->id.'&date=2026-10-05')
            ->assertOk()
            ->assertJsonCount(2, 'schedules')
            ->assertJsonPath('schedules.0.template.schedule_type', 'day')
            ->assertJsonPath('schedules.0.selected_day_number', 6)
            ->assertJsonCount(6, 'schedules.0.days')
            ->assertJsonPath('schedules.1.template.schedule_type', 'afternoon')
            ->assertJsonPath('schedules.1.selected_day_number', 1)
            ->assertJsonCount(5, 'schedules.1.days');
    }

    public function test_home_still_loads_when_the_timetable_schema_is_not_ready(): void
    {
        Schema::drop('tt_settings');

        $this->getJson('/api/parent/dashboard')->assertOk()
            ->assertJsonPath('day_label', 'Friday')
            ->assertJsonPath('children.0.id', $this->student->id);
    }
}
