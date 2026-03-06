<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Facades\DB;

class AcademicStructureService
{
    /**
     * Close an academic year
     */
    public function closeAcademicYear(int $yearId): array
    {
        try {
            return DB::transaction(function () use ($yearId) {
                $academicYear = AcademicYear::findOrFail($yearId);
                
                // Validate that we can close this year
                if ($academicYear->isClosed()) {
                    return [
                        'success' => false,
                        'message' => 'Academic year is already closed.'
                    ];
                }
                
                if ($academicYear->isLocked()) {
                    return [
                        'success' => false,
                        'message' => 'Cannot close a locked academic year.'
                    ];
                }
                
                // Close the academic year
                $academicYear->update(['status' => AcademicYear::STATUS_CLOSED]);
                
                return [
                    'success' => true,
                    'message' => 'Academic year closed successfully.'
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to close academic year: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Lock an academic year
     */
    public function lockAcademicYear(int $yearId): array
    {
        try {
            return DB::transaction(function () use ($yearId) {
                $academicYear = AcademicYear::findOrFail($yearId);
                
                // Validate that we can lock this year
                if ($academicYear->isLocked()) {
                    return [
                        'success' => false,
                        'message' => 'Academic year is already locked.'
                    ];
                }
                
                // Lock the academic year
                $academicYear->update(['status' => AcademicYear::STATUS_LOCKED]);
                
                return [
                    'success' => true,
                    'message' => 'Academic year locked successfully.'
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to lock academic year: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Finalize a term
     */
    public function finalizeTerm(int $termId): array
    {
        try {
            return DB::transaction(function () use ($termId) {
                $term = Term::findOrFail($termId);
                
                // Validate that we can finalize this term
                if ($term->isFinalized()) {
                    return [
                        'success' => false,
                        'message' => 'Term is already finalized.'
                    ];
                }
                
                if ($term->isLocked()) {
                    return [
                        'success' => false,
                        'message' => 'Cannot finalize a locked term.'
                    ];
                }
                
                // Finalize the term
                $term->update(['status' => Term::STATUS_FINALIZED]);
                
                return [
                    'success' => true,
                    'message' => 'Term finalized successfully.'
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to finalize term: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Lock a term
     */
    public function lockTerm(int $termId): array
    {
        try {
            return DB::transaction(function () use ($termId) {
                $term = Term::findOrFail($termId);
                
                // Validate that we can lock this term
                if ($term->isLocked()) {
                    return [
                        'success' => false,
                        'message' => 'Term is already locked.'
                    ];
                }
                
                // Lock the term
                $term->update(['status' => Term::STATUS_LOCKED]);
                
                return [
                    'success' => true,
                    'message' => 'Term locked successfully.'
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to lock term: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Activate a term (make it the only active term in its academic year)
     */
    public function activateTerm(int $termId): array
    {
        try {
            return DB::transaction(function () use ($termId) {
                $term = Term::findOrFail($termId);
                
                // Deactivate all other terms in the same academic year
                Term::where('academic_year_id', $term->academic_year_id)
                    ->where('id', '!=', $term->id)
                    ->where('status', Term::STATUS_ACTIVE)
                    ->update(['status' => Term::STATUS_LOCKED]);
                
                // Activate this term
                $term->update(['status' => Term::STATUS_ACTIVE]);
                
                return [
                    'success' => true,
                    'message' => 'Term activated successfully.'
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to activate term: ' . $e->getMessage()
            ];
        }
    }
}
