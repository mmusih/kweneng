<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\ClassSubject;
use App\Models\TeacherSubject;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubjectService
{
    /**
     * Create a new subject
     */
    public function createSubject(array $data): Subject
    {
        return Subject::create($data);
    }

    /**
     * Update a subject
     */
    public function updateSubject(Subject $subject, array $data): Subject
    {
        $subject->update($data);
        return $subject;
    }

    /**
     * Assign subject to class for specific academic year
     */
    public function assignSubjectToClass(
        int $classId,
        int $subjectId,
        int $academicYearId,
        array $settings = []
    ): ClassSubject {
        // Validate that academic year is open
        $academicYear = AcademicYear::findOrFail($academicYearId);
        if (!$academicYear->isOpen()) {
            throw new InvalidArgumentException('Cannot assign subjects to closed or locked academic year.');
        }

        return ClassSubject::updateOrCreate(
            [
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'academic_year_id' => $academicYearId,
            ],
            array_merge([
                'max_marks' => 100,
                'passing_marks' => 40,
            ], $settings)
        );
    }

    /**
     * Assign teacher to subject for specific class and academic year
     */
    public function assignTeacherToSubject(
        int $teacherId,
        int $subjectId,
        int $classId,
        int $academicYearId,
        array $settings = []
    ): TeacherSubject {
        // Validate that academic year is open
        $academicYear = AcademicYear::findOrFail($academicYearId);
        if (!$academicYear->isOpen()) {
            throw new InvalidArgumentException('Cannot assign teachers to closed or locked academic year.');
        }

        return TeacherSubject::updateOrCreate(
            [
                'teacher_id' => $teacherId,
                'subject_id' => $subjectId,
                'class_id' => $classId,
                'academic_year_id' => $academicYearId,
            ],
            array_merge([
                'is_primary' => false,
            ], $settings)
        );
    }

    /**
     * Remove subject from class
     */
    public function removeSubjectFromClass(
        int $classId,
        int $subjectId,
        int $academicYearId
    ): bool {
        $classSubject = ClassSubject::where([
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'academic_year_id' => $academicYearId,
        ])->first();

        if ($classSubject) {
            // Also remove teacher assignments for this subject-class combination
            TeacherSubject::where([
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'academic_year_id' => $academicYearId,
            ])->delete();

            return $classSubject->delete();
        }

        return false;
    }

    /**
     * Remove teacher from subject
     */
    public function removeTeacherFromSubject(
        int $teacherId,
        int $subjectId,
        int $classId,
        int $academicYearId
    ): bool {
        $teacherSubject = TeacherSubject::where([
            'teacher_id' => $teacherId,
            'subject_id' => $subjectId,
            'class_id' => $classId,
            'academic_year_id' => $academicYearId,
        ])->first();

        return $teacherSubject ? $teacherSubject->delete() : false;
    }

    /**
     * Get subjects for a specific class and academic year
     */
    public function getClassSubjects(int $classId, int $academicYearId)
    {
        return ClassSubject::where('class_id', $classId)
                          ->where('academic_year_id', $academicYearId)
                          ->with('subject')
                          ->get();
    }

    /**
     * Get teachers for a specific subject, class, and academic year
     */
    public function getSubjectTeachers(int $subjectId, int $classId, int $academicYearId)
    {
        return TeacherSubject::where('subject_id', $subjectId)
                            ->where('class_id', $classId)
                            ->where('academic_year_id', $academicYearId)
                            ->with('teacher.user')
                            ->get();
    }

    /**
     * Get all subjects with their class assignments for an academic year
     */
    public function getSubjectsByAcademicYear(int $academicYearId)
    {
        return Subject::where('is_active', true)
                     ->with(['classSubjects' => function($query) use ($academicYearId) {
                         $query->where('academic_year_id', $academicYearId)
                               ->with('class');
                     }])
                     ->orderBy('display_order')
                     ->get();
    }
}
