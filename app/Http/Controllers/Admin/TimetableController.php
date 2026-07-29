<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\TimetableCycleAnchor;
use App\Models\TimetableDay;
use App\Models\TimetableEntry;
use App\Models\TimetableGroup;
use App\Models\TimetablePeriod;
use App\Models\TimetableRoom;
use App\Models\TimetableTemplate;
use App\Services\TimetableService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TimetableController extends Controller
{
    public function __construct(private readonly TimetableService $timetables) {}

    public function index(Request $request): View
    {
        $academicYear = AcademicYear::current();
        $templates = TimetableTemplate::with('academicYear')
            ->latest('is_active')
            ->latest('id')
            ->get();

        $template = $request->integer('template_id')
            ? TimetableTemplate::find($request->integer('template_id'))
            : $templates->firstWhere('is_active', true) ?? $templates->first();

        $template?->load([
            'academicYear',
            'cycleAnchors',
            'days.periods',
            'days.entries.startPeriod',
            'days.entries.endPeriod',
            'days.entries.class',
            'days.entries.group',
            'days.entries.subject',
            'days.entries.teacher.user',
            'days.entries.room',
        ]);

        $yearId = $template?->academic_year_id ?? $academicYear?->id;
        $unscheduledAssignments = collect();

        if ($template && $yearId) {
            $unscheduledAssignments = TeacherSubject::with(['teacher.user', 'subject', 'class'])
                ->where('academic_year_id', $yearId)
                ->get()
                ->reject(fn (TeacherSubject $assignment) => TimetableEntry::query()
                    ->where('timetable_template_id', $template->id)
                    ->where('teacher_id', $assignment->teacher_id)
                    ->where('subject_id', $assignment->subject_id)
                    ->where(function ($query) use ($assignment) {
                        $query->where('class_id', $assignment->class_id)
                            ->orWhereHas(
                                'group.classes',
                                fn ($classQuery) => $classQuery->where('classes.id', $assignment->class_id),
                            );
                    })
                    ->exists())
                ->values();
        }

        return view('admin.timetable.index', [
            'academicYear' => $academicYear,
            'academicYears' => AcademicYear::orderByDesc('id')->get(),
            'templates' => $templates,
            'template' => $template,
            'rooms' => TimetableRoom::orderBy('name')->get(),
            'classes' => ClassModel::when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
                ->orderBy('name')
                ->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(),
            'teachers' => Teacher::with('user')->whereHas('user', fn ($query) => $query->where('status', 'active'))
                ->get()
                ->sortBy(fn (Teacher $teacher) => $teacher->user?->name)
                ->values(),
            'students' => Student::with(['user', 'currentClass'])
                ->when($yearId, fn ($query) => $query->whereHas(
                    'currentClass',
                    fn ($classQuery) => $classQuery->where('academic_year_id', $yearId),
                ))
                ->get()
                ->sortBy(fn (Student $student) => $student->user?->name)
                ->values(),
            'groups' => TimetableGroup::with(['subject', 'classes', 'students.user'])
                ->when($yearId, fn ($query) => $query->where('academic_year_id', $yearId))
                ->orderBy('name')
                ->get(),
            'unscheduledAssignments' => $unscheduledAssignments,
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:255'],
            'cycle_type' => ['required', Rule::in([
                TimetableTemplate::CYCLE_WEEKLY,
                TimetableTemplate::CYCLE_ROTATING,
            ])],
            'cycle_length' => ['required', 'integer', 'between:1,7'],
            'cycle_start_date' => ['nullable', 'date', 'required_if:cycle_type,rotating'],
            'cycle_start_day_number' => [
                'nullable',
                'integer',
                'min:1',
                'lte:cycle_length',
                'required_if:cycle_type,rotating',
            ],
        ]);

        if (
            $data['cycle_type'] === TimetableTemplate::CYCLE_ROTATING
            && Carbon::parse($data['cycle_start_date'])->isWeekend()
        ) {
            throw ValidationException::withMessages([
                'cycle_start_date' => 'The first rotating-cycle date must be a weekday.',
            ]);
        }

        $template = DB::transaction(function () use ($data) {
            $template = TimetableTemplate::create([
                'academic_year_id' => $data['academic_year_id'],
                'name' => $data['name'],
                'cycle_type' => $data['cycle_type'],
                'cycle_length' => $data['cycle_length'],
                'cycle_start_date' => $data['cycle_type'] === TimetableTemplate::CYCLE_ROTATING
                    ? $data['cycle_start_date']
                    : null,
            ]);

            $weeklyNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            foreach (range(1, (int) $data['cycle_length']) as $dayNumber) {
                $template->days()->create([
                    'day_number' => $dayNumber,
                    'name' => $data['cycle_type'] === TimetableTemplate::CYCLE_WEEKLY
                        ? $weeklyNames[$dayNumber - 1]
                        : "Cycle Day {$dayNumber}",
                    'weekday' => $data['cycle_type'] === TimetableTemplate::CYCLE_WEEKLY
                        ? $dayNumber
                        : null,
                ]);
            }

            if ($data['cycle_type'] === TimetableTemplate::CYCLE_ROTATING) {
                $template->cycleAnchors()->create([
                    'anchor_date' => $data['cycle_start_date'],
                    'day_number' => $data['cycle_start_day_number'],
                    'note' => 'Initial cycle date',
                ]);
            }

            return $template;
        });

        return redirect()
            ->route('admin.timetable.index', ['template_id' => $template->id])
            ->with('success', 'Timetable template created. Add the school-day periods next.');
    }

    public function storeCycleAnchor(
        Request $request,
        TimetableTemplate $template,
    ): RedirectResponse {
        abort_unless($template->cycle_type === TimetableTemplate::CYCLE_ROTATING, 404);

        $data = $request->validate([
            'anchor_date' => ['required', 'date'],
            'day_number' => ['required', 'integer', 'between:1,'.$template->cycle_length],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if (Carbon::parse($data['anchor_date'])->isWeekend()) {
            throw ValidationException::withMessages([
                'anchor_date' => 'Choose a weekday. Weekends do not have a rotating cycle day.',
            ]);
        }

        $template->cycleAnchors()->updateOrCreate(
            ['anchor_date' => $data['anchor_date']],
            [
                'day_number' => $data['day_number'],
                'note' => $data['note'] ?? null,
            ],
        );

        return back()->with(
            'success',
            "Cycle reset saved: {$data['anchor_date']} is Cycle Day {$data['day_number']}.",
        );
    }

    public function destroyCycleAnchor(
        TimetableTemplate $template,
        TimetableCycleAnchor $anchor,
    ): RedirectResponse {
        abort_unless($anchor->timetable_template_id === $template->id, 404);
        $anchor->delete();

        return back()->with('success', 'Cycle reset removed.');
    }

    public function storePeriod(
        Request $request,
        TimetableTemplate $template,
        TimetableDay $day,
    ): RedirectResponse {
        abort_unless($day->timetable_template_id === $template->id, 404);

        $data = $request->validate([
            'sequence' => [
                'required',
                'integer',
                'between:1,50',
                Rule::unique('timetable_periods')->where('timetable_day_id', $day->id),
            ],
            'name' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'type' => ['required', Rule::in(TimetablePeriod::TYPES)],
        ]);

        if (
            $day->periods()
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'start_time' => 'This block overlaps another block already configured for the day.',
            ]);
        }

        $day->periods()->create($data);

        return back()->with('success', "{$data['name']} added to {$day->name}.");
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:40', 'unique:timetable_rooms,code'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', 'string', 'max:100'],
        ]);

        TimetableRoom::create($data);

        return back()->with('success', 'Room added.');
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('timetable_groups')->where('academic_year_id', $request->integer('academic_year_id')),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => [
                'integer',
                Rule::exists('classes', 'id')->where('academic_year_id', $request->integer('academic_year_id')),
            ],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $allowedStudentIds = Student::whereIn('id', $data['student_ids'])
            ->whereIn('current_class_id', $data['class_ids'])
            ->pluck('id');

        if ($allowedStudentIds->count() !== count(array_unique($data['student_ids']))) {
            throw ValidationException::withMessages([
                'student_ids' => 'Every selected student must belong to one of the selected classes.',
            ]);
        }

        DB::transaction(function () use ($data) {
            $group = TimetableGroup::create([
                'academic_year_id' => $data['academic_year_id'],
                'subject_id' => $data['subject_id'],
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'description' => $data['description'] ?? null,
            ]);
            $group->classes()->sync($data['class_ids']);
            $group->students()->sync($data['student_ids']);
        });

        return back()->with('success', 'Split or option group created.');
    }

    public function storeEntry(Request $request, TimetableTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'timetable_day_id' => [
                'required',
                Rule::exists('timetable_days', 'id')->where('timetable_template_id', $template->id),
            ],
            'start_period_id' => ['required', 'exists:timetable_periods,id'],
            'end_period_id' => ['required', 'exists:timetable_periods,id'],
            'class_id' => ['nullable', 'exists:classes,id', 'required_without:timetable_group_id'],
            'timetable_group_id' => ['nullable', 'exists:timetable_groups,id', 'required_without:class_id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'timetable_room_id' => ['nullable', 'exists:timetable_rooms,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (
            filled($data['class_id'] ?? null)
            && ClassModel::whereKey($data['class_id'])->value('academic_year_id') !== $template->academic_year_id
        ) {
            throw ValidationException::withMessages([
                'class_id' => 'The selected class does not belong to this timetable academic year.',
            ]);
        }

        if (
            filled($data['timetable_group_id'] ?? null)
            && TimetableGroup::whereKey($data['timetable_group_id'])->value('academic_year_id') !== $template->academic_year_id
        ) {
            throw ValidationException::withMessages([
                'timetable_group_id' => 'The selected group does not belong to this timetable academic year.',
            ]);
        }

        $this->timetables->validateEntry($data);
        $template->entries()->create($data);

        return back()->with('success', 'Lesson added to the timetable.');
    }

    public function publish(TimetableTemplate $template): RedirectResponse
    {
        $template->load('days.periods');

        if ($template->days->isEmpty() || $template->days->contains(fn ($day) => $day->periods->isEmpty())) {
            throw ValidationException::withMessages([
                'template' => 'Every timetable day needs at least one period before publishing.',
            ]);
        }

        DB::transaction(function () use ($template) {
            TimetableTemplate::where('academic_year_id', $template->academic_year_id)
                ->where('id', '!=', $template->id)
                ->update(['is_active' => false]);

            $template->update([
                'is_active' => true,
                'is_published' => true,
            ]);
        });

        return back()->with('success', 'Timetable published. Teachers, students, and parents can now view it.');
    }

    public function destroyEntry(
        TimetableTemplate $template,
        TimetableEntry $entry,
    ): RedirectResponse {
        abort_unless($entry->timetable_template_id === $template->id, 404);
        $entry->delete();

        return back()->with('success', 'Timetable lesson removed.');
    }
}
