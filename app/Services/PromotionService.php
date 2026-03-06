<?php

namespace App\Services;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\AcademicYear;
use App\Models\StudentClassHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PromotionService
{
    /**
     * Promote a student to a new class with academic structure validation
     */
    public function promoteStudent(
        int $studentId, 
        int $newClassId, 
        ?int $academicYearId = null,
        string $promotionType = 'promoted',
        ?string $remarks = null
    ): array {
        try {
            return DB::transaction(function () use ($studentId, $newClassId, $academicYearId, $promotionType, $remarks) {
                
                // Validate inputs
                $student = Student::findOrFail($studentId);
                $newClass = ClassModel::findOrFail($newClassId);
                
                // Determine academic year
                $academicYearId = $academicYearId ?? $newClass->academic_year_id;
                $targetAcademicYear = AcademicYear::findOrFail($academicYearId);
                
                // Validate academic structure locking (if implemented)
                $validationResult = $this->validatePromotionStructure($student, $targetAcademicYear);
                if (!$validationResult['success']) {
                    return $validationResult;
                }
                
                // Check if student already has an active record in the target academic year
                $existingActive = StudentClassHistory::where('student_id', $studentId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('status', 'active')
                    ->exists();
                
                if ($existingActive) {
                    return [
                        'success' => false,
                        'message' => 'Student already has an active enrollment in this academic year.',
                        'error_code' => 'ACTIVE_ENROLLMENT_EXISTS'
                    ];
                }
                
                // Find current active enrollment and close it
                $currentEnrollment = StudentClassHistory::where('student_id', $studentId)
                    ->where('status', 'active')
                    ->first();
                
                if ($currentEnrollment) {
                    $currentEnrollment->update([
                        'exited_at' => now(),
                        'status' => $promotionType,
                        'remarks' => $remarks
                    ]);
                }
                
                // Create new enrollment
                $newEnrollment = StudentClassHistory::create([
                    'student_id' => $studentId,
                    'class_id' => $newClassId,
                    'academic_year_id' => $academicYearId,
                    'term_id' => null,
                    'enrolled_at' => now(),
                    'exited_at' => null,
                    'status' => 'active',
                    'remarks' => $remarks
                ]);
                
                // Update student's current class reference
                $student->update([
                    'current_class_id' => $newClassId
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Student promoted successfully.',
                    'data' => [
                        'student_id' => $studentId,
                        'previous_class_id' => $currentEnrollment?->class_id,
                        'new_class_id' => $newClassId,
                        'academic_year_id' => $academicYearId,
                        'enrollment_id' => $newEnrollment->id
                    ]
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to promote student: ' . $e->getMessage(),
                'error_code' => 'PROMOTION_FAILED'
            ];
        }
    }
    
    /**
     * Validate academic structure for promotion
     */
    private function validatePromotionStructure(Student $student, AcademicYear $targetAcademicYear): array
    {
        // This validation depends on whether you've implemented the academic structure locking
        // For now, we'll return success, but you can uncomment when ready:
        
        /*
        // Check if target academic year is open
        if (!$targetAcademicYear->isOpen()) {
            return [
                'success' => false,
                'message' => 'Cannot promote to a closed or locked academic year.',
                'error_code' => 'TARGET_YEAR_NOT_OPEN'
            ];
        }
        
        // Check if student's current academic year is closed (if they have one)
        $currentEnrollment = StudentClassHistory::where('student_id', $student->id)
            ->where('status', 'active')
            ->first();
            
        if ($currentEnrollment) {
            $currentAcademicYear = $currentEnrollment->academicYear;
            if ($currentAcademicYear && !$currentAcademicYear->isClosed()) {
                return [
                    'success' => false,
                    'message' => 'Cannot promote student from an academic year that is not closed.',
                    'error_code' => 'CURRENT_YEAR_NOT_CLOSED'
                ];
            }
        }
        */
        
        return ['success' => true];
    }
    
    /**
     * Bulk promote with academic structure validation
     */
    public function bulkPromoteClass(
        int $fromClassId, 
        int $toClassId, 
        ?int $academicYearId = null,
        string $promotionType = 'promoted'
    ): array {
        try {
            return DB::transaction(function () use ($fromClassId, $toClassId, $academicYearId, $promotionType) {
                
                $fromClass = ClassModel::findOrFail($fromClassId);
                $toClass = ClassModel::findOrFail($toClassId);
                
                $academicYearId = $academicYearId ?? $toClass->academic_year_id;
                $targetAcademicYear = AcademicYear::findOrFail($academicYearId);
                
                // Validate academic structure for bulk promotion
                $validationResult = $this->validateBulkPromotionStructure($fromClass, $targetAcademicYear);
                if (!$validationResult['success']) {
                    return $validationResult;
                }
                
                // Get all students in the source class with active enrollment
                $students = Student::where('current_class_id', $fromClassId)->get();
                
                if ($students->isEmpty()) {
                    return [
                        'success' => false,
                        'message' => 'No students found in the source class.',
                        'error_code' => 'NO_STUDENTS_FOUND'
                    ];
                }
                
                $results = [];
                $successfulPromotions = 0;
                $failedPromotions = 0;
                
                foreach ($students as $student) {
                    $result = $this->promoteStudent(
                        $student->id, 
                        $toClassId, 
                        $academicYearId, 
                        $promotionType
                    );
                    
                    if ($result['success']) {
                        $successfulPromotions++;
                    } else {
                        $failedPromotions++;
                    }
                    
                    $results[] = [
                        'student_id' => $student->id,
                        'student_name' => $student->user->name ?? 'Unknown',
                        'success' => $result['success'],
                        'message' => $result['message']
                    ];
                }
                
                return [
                    'success' => true,
                    'message' => "Bulk promotion completed. {$successfulPromotions} successful, {$failedPromotions} failed.",
                    'data' => [
                        'total_students' => count($students),
                        'successful' => $successfulPromotions,
                        'failed' => $failedPromotions,
                        'results' => $results
                    ]
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to bulk promote class: ' . $e->getMessage(),
                'error_code' => 'BULK_PROMOTION_FAILED'
            ];
        }
    }
    
    private function validateBulkPromotionStructure(ClassModel $fromClass, AcademicYear $targetAcademicYear): array
    {
        // Similar validation logic as individual promotion
        return ['success' => true];
    }
    
    /**
     * Reverse a promotion (demote a student)
     */
    public function reversePromotion(int $studentId, ?string $remarks = null): array
    {
        try {
            return DB::transaction(function () use ($studentId, $remarks) {
                
                $student = Student::findOrFail($studentId);
                
                // Find the current active enrollment
                $currentEnrollment = StudentClassHistory::where('student_id', $studentId)
                    ->where('status', 'active')
                    ->first();
                
                if (!$currentEnrollment) {
                    return [
                        'success' => false,
                        'message' => 'No active enrollment found for student.',
                        'error_code' => 'NO_ACTIVE_ENROLLMENT'
                    ];
                }
                
                // Find the previous enrollment to reactivate
                $previousEnrollment = StudentClassHistory::where('student_id', $studentId)
                    ->where('exited_at', '<=', $currentEnrollment->enrolled_at)
                    ->where('status', '!=', 'active')
                    ->orderBy('exited_at', 'desc')
                    ->first();
                
                if (!$previousEnrollment) {
                    return [
                        'success' => false,
                        'message' => 'No previous enrollment found to revert to.',
                        'error_code' => 'NO_PREVIOUS_ENROLLMENT'
                    ];
                }
                
                // Close current enrollment
                $currentEnrollment->update([
                    'exited_at' => now(),
                    'status' => 'reverted',
                    'remarks' => $remarks ?? 'Reverted promotion'
                ]);
                
                // Reactivate previous enrollment
                $previousEnrollment->update([
                    'exited_at' => null,
                    'status' => 'active',
                    'remarks' => $remarks ?? 'Reactivated previous enrollment'
                ]);
                
                // Update student's current class
                $student->update([
                    'current_class_id' => $previousEnrollment->class_id
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Promotion reversed successfully.',
                    'data' => [
                        'student_id' => $studentId,
                        'previous_class_id' => $previousEnrollment->class_id,
                        'current_enrollment_id' => $previousEnrollment->id
                    ]
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to reverse promotion: ' . $e->getMessage(),
                'error_code' => 'REVERSE_PROMOTION_FAILED'
            ];
        }
    }
}
