<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Scheme;
use App\Models\SchemeItem;
use App\Models\SchemeItemSubtopic;
use App\Models\SchemeProgressLog;
use App\Models\Syllabus;
use App\Models\SyllabusTopic;
use App\Models\TeacherSubject;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class SchemeController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $teacher = $user?->teacher;
        abort_unless($teacher, 403, 'Teacher profile not found.');

        $academicYear = AcademicYear::current();

        $schemes = Scheme::with(['teacherSubject.class', 'teacherSubject.subject', 'academicYear', 'items.subtopics', 'logs'])
            ->whereHas('teacherSubject', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->latest()
            ->get();

        $isHod = $user->isDepartmentHod($academicYear?->id);

        return view('teacher.schemes.index', compact('schemes', 'academicYear', 'isHod'));
    }

    public function create()
    {
        $user = auth()->user();
        $teacher = $user?->teacher;
        abort_unless($teacher, 403, 'Teacher profile not found.');

        $academicYear = AcademicYear::current();

        $assignments = TeacherSubject::with(['class', 'subject', 'academicYear'])
            ->where('teacher_id', $teacher->id)
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->orderByDesc('academic_year_id')
            ->get();

        if ($assignments->isEmpty()) {
            $assignments = TeacherSubject::with(['class', 'subject', 'academicYear'])
                ->where('teacher_id', $teacher->id)
                ->orderByDesc('academic_year_id')
                ->get();
        }

        $syllabuses = Syllabus::with(['subject', 'class', 'academicYear'])
            ->whereIn('status', ['draft', 'active'])
            ->orderByDesc('id')
            ->get();

        return view('teacher.schemes.create', compact('assignments', 'syllabuses', 'academicYear'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $teacher = $user?->teacher;
        abort_unless($teacher, 403, 'Teacher profile not found.');

        $validated = $request->validate([
            'teacher_subject_id' => ['required', 'integer', 'exists:teacher_subjects,id'],
            'syllabus_id' => ['nullable', 'integer', 'exists:syllabuses,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $assignment = TeacherSubject::with(['class', 'subject', 'academicYear'])
            ->where('id', $validated['teacher_subject_id'])
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        if (! empty($validated['syllabus_id'])) {
            $this->validateSyllabusMatchesAssignment((int) $validated['syllabus_id'], $assignment);
        }

        $existingScheme = Scheme::where('teacher_subject_id', $assignment->id)
            ->where('academic_year_id', $assignment->academic_year_id)
            ->first();

        if ($existingScheme) {
            return redirect()->route('teacher.schemes.show', $existingScheme)
                ->with('success', 'A scheme already exists for this teaching assignment.');
        }

        $title = $validated['title']
            ?: trim(($assignment->subject?->name ?? 'Subject') . ' - ' . ($assignment->class?->name ?? 'Class') . ' Scheme of Work');

        $scheme = DB::transaction(function () use ($validated, $assignment, $title, $user) {
            $scheme = Scheme::create([
                'teacher_subject_id' => $assignment->id,
                'syllabus_id' => $validated['syllabus_id'] ?? null,
                'academic_year_id' => $assignment->academic_year_id,
                'created_by' => $user->id,
                'title' => $title,
                'status' => Scheme::STATUS_DRAFT,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (!empty($validated['syllabus_id'])) {
                $this->cloneSyllabusIntoScheme($scheme, (int) $validated['syllabus_id']);
            }

            return $scheme;
        });

        return redirect()->route('teacher.schemes.show', $scheme)->with('success', 'Scheme created. You can now plan topics into terms and weeks.');
    }

    public function show(Scheme $scheme)
    {
        $this->authoriseOwnScheme($scheme);

        $scheme->load([
            'teacherSubject.teacher.user',
            'teacherSubject.class',
            'teacherSubject.subject',
            'academicYear.terms',
            'items.subtopics',
            'items.term',
            'logs.user',
        ]);

        $terms = Term::where('academic_year_id', $scheme->academic_year_id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $bankItems = $scheme->items->whereNull('term_id')->values();
        $plannedItems = $scheme->items->whereNotNull('term_id')->groupBy(fn ($item) => $item->term_id . '-' . $item->week_number);
        $statuses = SchemeItem::statuses();

        return view('teacher.schemes.show', compact('scheme', 'terms', 'bankItems', 'plannedItems', 'statuses'));
    }

    public function importText(Request $request, Scheme $scheme)
    {
        $this->authoriseOwnScheme($scheme);
        $this->ensureTeacherCanEditPlan($scheme);

        $validated = $request->validate([
            'raw_text' => ['required', 'string', 'min:3'],
        ]);

        $created = DB::transaction(function () use ($scheme, $validated) {
            $topics = $this->parseTopicText($validated['raw_text']);
            $count = 0;
            $maxOrder = (int) $scheme->items()->max('planned_order');

            foreach ($topics as $topicData) {
                $item = $scheme->items()->create([
                    'title' => $topicData['title'],
                    'description' => null,
                    'estimated_periods' => 1,
                    'planned_order' => ++$maxOrder,
                    'status' => SchemeItem::STATUS_NOT_STARTED,
                ]);

                foreach ($topicData['subtopics'] as $index => $subtopic) {
                    $item->subtopics()->create([
                        'title' => $subtopic,
                        'sort_order' => $index + 1,
                        'status' => SchemeItemSubtopic::STATUS_NOT_STARTED,
                    ]);
                }

                $count++;
            }

            return $count;
        });

        return redirect()->route('teacher.schemes.show', $scheme)->with('success', "Imported {$created} topic(s) into the topic bank.");
    }

    public function addTopic(Request $request, Scheme $scheme)
    {
        $this->authoriseOwnScheme($scheme);
        $this->ensureTeacherCanEditPlan($scheme);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtopics' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($scheme, $validated) {
            $item = $scheme->items()->create([
                'title' => $validated['title'],
                'estimated_periods' => 1,
                'planned_order' => ((int) $scheme->items()->max('planned_order')) + 1,
                'status' => SchemeItem::STATUS_NOT_STARTED,
            ]);

            $subtopics = collect(preg_split('/\r\n|\r|\n/', $validated['subtopics'] ?? ''))
                ->map(fn ($line) => trim(ltrim($line, "-•* \t")))
                ->filter()
                ->values();

            foreach ($subtopics as $index => $title) {
                $item->subtopics()->create([
                    'title' => $title,
                    'sort_order' => $index + 1,
                    'status' => SchemeItemSubtopic::STATUS_NOT_STARTED,
                ]);
            }
        });

        return redirect()->route('teacher.schemes.show', $scheme)->with('success', 'Topic added to the bank.');
    }

    public function destroyItem(Scheme $scheme, SchemeItem $item)
    {
        $this->authoriseOwnScheme($scheme);
        $this->ensureTeacherCanEditPlan($scheme);
        abort_unless($item->scheme_id === $scheme->id, 404);

        $item->delete();

        return redirect()->route('teacher.schemes.show', $scheme)->with('success', 'Topic removed.');
    }

    public function savePlan(Request $request, Scheme $scheme)
    {
        $this->authoriseOwnScheme($scheme);
        $this->ensureTeacherCanEditPlan($scheme);

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:scheme_items,id'],
            'items.*.term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'items.*.week_number' => ['nullable', 'integer', 'min:1', 'max:60'],
            'items.*.planned_order' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($scheme, $validated) {
            foreach ($validated['items'] as $data) {
                SchemeItem::where('scheme_id', $scheme->id)
                    ->where('id', $data['id'])
                    ->update([
                        'term_id' => $data['term_id'] ?? null,
                        'week_number' => $data['week_number'] ?? null,
                        'planned_order' => $data['planned_order'],
                    ]);
            }
        });

        return response()->json(['message' => 'Plan saved successfully.']);
    }

    public function updateItemStatus(Request $request, Scheme $scheme, SchemeItem $item)
    {
        $this->authoriseOwnScheme($scheme);
        abort_unless($item->scheme_id === $scheme->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(SchemeItem::statuses()))],
            'teacher_comment' => ['nullable', 'string'],
        ]);

        $oldStatus = $item->status;
        $completedAt = $validated['status'] === SchemeItem::STATUS_COMPLETED ? now() : null;
        $completedBy = $validated['status'] === SchemeItem::STATUS_COMPLETED ? auth()->id() : null;

        $item->update([
            'status' => $validated['status'],
            'completed_at' => $completedAt,
            'completed_by' => $completedBy,
            'teacher_comment' => $validated['teacher_comment'] ?? $item->teacher_comment,
        ]);

        if ($validated['status'] === SchemeItem::STATUS_COMPLETED) {
            $item->subtopics()->where('status', '!=', SchemeItemSubtopic::STATUS_COMPLETED)->update([
                'status' => SchemeItemSubtopic::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => auth()->id(),
            ]);
        }

        SchemeProgressLog::create([
            'scheme_id' => $scheme->id,
            'scheme_item_id' => $item->id,
            'user_id' => auth()->id(),
            'action' => 'topic_status_updated',
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
            'comment' => $validated['teacher_comment'] ?? null,
        ]);

        return redirect()->route('teacher.schemes.show', $scheme)->with('success', 'Topic progress updated.');
    }

    public function toggleSubtopic(Request $request, Scheme $scheme, SchemeItem $item, SchemeItemSubtopic $subtopic)
    {
        $this->authoriseOwnScheme($scheme);
        abort_unless($item->scheme_id === $scheme->id && $subtopic->scheme_item_id === $item->id, 404);

        $oldStatus = $subtopic->status;
        $newStatus = $subtopic->status === SchemeItemSubtopic::STATUS_COMPLETED
            ? SchemeItemSubtopic::STATUS_NOT_STARTED
            : SchemeItemSubtopic::STATUS_COMPLETED;

        $subtopic->update([
            'status' => $newStatus,
            'completed_at' => $newStatus === SchemeItemSubtopic::STATUS_COMPLETED ? now() : null,
            'completed_by' => $newStatus === SchemeItemSubtopic::STATUS_COMPLETED ? auth()->id() : null,
        ]);

        $remaining = $item->subtopics()->where('status', '!=', SchemeItemSubtopic::STATUS_COMPLETED)->count();
        $item->update([
            'status' => $remaining === 0 ? SchemeItem::STATUS_COMPLETED : SchemeItem::STATUS_IN_PROGRESS,
            'completed_at' => $remaining === 0 ? now() : null,
            'completed_by' => $remaining === 0 ? auth()->id() : null,
        ]);

        SchemeProgressLog::create([
            'scheme_id' => $scheme->id,
            'scheme_item_id' => $item->id,
            'scheme_item_subtopic_id' => $subtopic->id,
            'user_id' => auth()->id(),
            'action' => 'subtopic_toggled',
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        return back()->with('success', 'Subtopic progress updated.');
    }

    public function submit(Scheme $scheme)
    {
        $this->authoriseOwnScheme($scheme);

        if (! in_array($scheme->status, [Scheme::STATUS_DRAFT, Scheme::STATUS_CHANGES_REQUESTED], true)) {
            return redirect()->route('teacher.schemes.show', $scheme)
                ->withErrors(['scheme' => 'Only draft schemes or schemes with requested changes can be submitted.']);
        }

        if ($scheme->items()->whereNotNull('term_id')->count() === 0) {
            return redirect()->route('teacher.schemes.show', $scheme)
                ->withErrors(['scheme' => 'Please plan at least one topic before submitting.']);
        }

        $scheme->update([
            'status' => Scheme::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'review_comment' => null,
        ]);

        return redirect()->route('teacher.schemes.show', $scheme)->with('success', 'Scheme submitted to HOD for review.');
    }

    private function authoriseOwnScheme(Scheme $scheme): void
    {
        $teacher = auth()->user()?->teacher;
        abort_unless($teacher, 403, 'Teacher profile not found.');

        $scheme->loadMissing('teacherSubject');
        abort_unless($scheme->teacherSubject?->teacher_id === $teacher->id, 403);
    }

    private function ensureTeacherCanEditPlan(Scheme $scheme): void
    {
        if (in_array($scheme->status, [Scheme::STATUS_SUBMITTED, Scheme::STATUS_APPROVED, Scheme::STATUS_ARCHIVED], true)) {
            throw ValidationException::withMessages([
                'scheme' => 'This scheme cannot be edited while it is submitted, approved, or archived.',
            ]);
        }
    }

    private function validateSyllabusMatchesAssignment(int $syllabusId, TeacherSubject $assignment): void
    {
        $syllabus = Syllabus::findOrFail($syllabusId);

        $mismatches = [];

        if ($syllabus->academic_year_id && $syllabus->academic_year_id !== $assignment->academic_year_id) {
            $mismatches[] = 'academic year';
        }

        if ($syllabus->subject_id && $syllabus->subject_id !== $assignment->subject_id) {
            $mismatches[] = 'subject';
        }

        if ($syllabus->class_id && $syllabus->class_id !== $assignment->class_id) {
            $mismatches[] = 'class';
        }

        if (! empty($mismatches)) {
            throw ValidationException::withMessages([
                'syllabus_id' => 'The selected syllabus does not match this teaching assignment by ' . implode(', ', $mismatches) . '.',
            ]);
        }
    }

    private function cloneSyllabusIntoScheme(Scheme $scheme, int $syllabusId): void
    {
        $topics = SyllabusTopic::with('subtopics')
            ->where('syllabus_id', $syllabusId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($topics as $topic) {
            $item = $scheme->items()->create([
                'syllabus_topic_id' => $topic->id,
                'title' => $topic->title,
                'description' => $topic->description,
                'estimated_periods' => $topic->estimated_periods ?: 1,
                'planned_order' => $topic->sort_order,
                'status' => SchemeItem::STATUS_NOT_STARTED,
            ]);

            foreach ($topic->subtopics as $subtopic) {
                $item->subtopics()->create([
                    'syllabus_subtopic_id' => $subtopic->id,
                    'title' => $subtopic->title,
                    'sort_order' => $subtopic->sort_order,
                    'status' => SchemeItemSubtopic::STATUS_NOT_STARTED,
                ]);
            }
        }
    }

    private function parseTopicText(string $text): array
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        $topics = [];
        $current = null;

        foreach ($lines as $line) {
            $isSubtopic = preg_match('/^[-•*]\s+/', $line) || preg_match('/^\d+\.\d+\s+/', $line);
            $clean = trim(preg_replace('/^([-•*]|\d+\.\d+)\s+/', '', $line));

            if (!$clean) {
                continue;
            }

            if ($isSubtopic && $current !== null) {
                $topics[$current]['subtopics'][] = $clean;
                continue;
            }

            $topics[] = [
                'title' => $clean,
                'subtopics' => [],
            ];
            $current = array_key_last($topics);
        }

        return $topics;
    }
}
