<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->backfillSingleTeacherAssignments();
        $this->deduplicateStudentSubjectTeacherGroups();

        Schema::table('student_subjects', function (Blueprint $table) {
            $table->index(
                ['teacher_id', 'class_id', 'subject_id', 'academic_year_id'],
                'student_subjects_teacher_scope_index'
            );

            $table->index(
                ['class_id', 'subject_id', 'academic_year_id'],
                'student_subjects_class_subject_year_index'
            );

            $table->unique(
                ['student_id', 'subject_id', 'class_id', 'academic_year_id'],
                'student_subjects_one_teacher_per_student_subject_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('student_subjects', function (Blueprint $table) {
            $table->dropUnique('student_subjects_one_teacher_per_student_subject_unique');
            $table->dropIndex('student_subjects_class_subject_year_index');
            $table->dropIndex('student_subjects_teacher_scope_index');
        });
    }

    private function backfillSingleTeacherAssignments(): void
    {
        DB::table('student_subjects')
            ->whereNull('teacher_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $teacherIds = DB::table('teacher_subjects')
                        ->where('class_id', $row->class_id)
                        ->where('subject_id', $row->subject_id)
                        ->where('academic_year_id', $row->academic_year_id)
                        ->pluck('teacher_id')
                        ->filter()
                        ->unique()
                        ->values();

                    if ($teacherIds->count() === 1) {
                        DB::table('student_subjects')
                            ->where('id', $row->id)
                            ->update([
                                'teacher_id' => $teacherIds->first(),
                                'updated_at' => now(),
                            ]);
                    }
                }
            });
    }

    private function deduplicateStudentSubjectTeacherGroups(): void
    {
        $groups = DB::table('student_subjects')
            ->select(
                'student_id',
                'subject_id',
                'class_id',
                'academic_year_id',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('student_id', 'subject_id', 'class_id', 'academic_year_id')
            ->having('total', '>', 1)
            ->get();

        foreach ($groups as $group) {
            $rows = DB::table('student_subjects')
                ->where('student_id', $group->student_id)
                ->where('subject_id', $group->subject_id)
                ->where('class_id', $group->class_id)
                ->where('academic_year_id', $group->academic_year_id)
                ->orderByRaw('teacher_id IS NULL')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            if ($rows->count() <= 1) {
                continue;
            }

            $latestMarkTeacherId = DB::table('marks')
                ->where('student_id', $group->student_id)
                ->where('subject_id', $group->subject_id)
                ->where('class_id', $group->class_id)
                ->where('academic_year_id', $group->academic_year_id)
                ->whereNotNull('teacher_id')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->value('teacher_id');

            $keep = $latestMarkTeacherId
                ? $rows->firstWhere('teacher_id', $latestMarkTeacherId)
                : null;

            if (! $keep) {
                $keep = $rows->firstWhere('teacher_id', '!=', null) ?? $rows->first();
            }

            DB::table('student_subjects')
                ->whereIn('id', $rows->pluck('id')->reject(fn ($id) => (int) $id === (int) $keep->id)->values())
                ->delete();
        }
    }
};
