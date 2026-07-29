<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Facades\DB;

class AcademicStructureService
{
    public function __construct(
        private readonly HomeworkStorageService $homeworkStorageService
    ) {
    }

    /**
     * Close an academic year and make sure it is no longer the active year.
     */
    public function closeAcademicYear(int $yearId): array
    {
        try {
            return DB::transaction(function () use ($yearId) {
                $academicYear = AcademicYear::findOrFail($yearId);

                if ($academicYear->isClosed()) {
                    return [
                        'success' => false,
                        'message' => 'Academic year is already closed.',
                    ];
                }

                if ($academicYear->isLocked()) {
                    return [
                        'success' => false,
                        'message' => 'Cannot close a locked academic year.',
                    ];
                }

                $purgeSummary = ['attachments_removed' => 0, 'bytes_released' => 0];

                Term::where('academic_year_id', $academicYear->id)
                    ->where('status', Term::STATUS_ACTIVE)
                    ->get()
                    ->each(function (Term $term) use (&$purgeSummary) {
                        $summary = $this->homeworkStorageService->purgeTermAttachments(
                            $term,
                            auth()->id(),
                            'academic_year_closed'
                        );

                        $purgeSummary['attachments_removed'] += $summary['attachments_removed'];
                        $purgeSummary['bytes_released'] += $summary['bytes_released'];

                        $term->update([
                            'status' => Term::STATUS_FINALIZED,
                            'locked' => false,
                        ]);
                    });

                $academicYear->update([
                    'status' => AcademicYear::STATUS_CLOSED,
                    'active' => false,
                ]);

                return [
                    'success' => true,
                    'message' => $this->appendPurgeSummary('Academic year closed successfully.', $purgeSummary),
                ];
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to close academic year: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Lock an academic year and make sure it is no longer the active year.
     */
    public function lockAcademicYear(int $yearId): array
    {
        try {
            return DB::transaction(function () use ($yearId) {
                $academicYear = AcademicYear::findOrFail($yearId);

                if ($academicYear->isLocked()) {
                    return [
                        'success' => false,
                        'message' => 'Academic year is already locked.',
                    ];
                }

                $academicYear->update([
                    'status' => AcademicYear::STATUS_LOCKED,
                    'active' => false,
                ]);

                return [
                    'success' => true,
                    'message' => 'Academic year locked successfully.',
                ];
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to lock academic year: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Finalize a term. This is the operational end-of-term trigger, so homework files are removed.
     */
    public function finalizeTerm(int $termId): array
    {
        try {
            return DB::transaction(function () use ($termId) {
                $term = Term::findOrFail($termId);

                if ($term->isFinalized()) {
                    return [
                        'success' => false,
                        'message' => 'Term is already finalized.',
                    ];
                }

                if ($term->isLocked()) {
                    return [
                        'success' => false,
                        'message' => 'Cannot finalize a locked term.',
                    ];
                }

                $purgeSummary = $this->homeworkStorageService->purgeTermAttachments(
                    $term,
                    auth()->id(),
                    'term_finalized'
                );

                $term->update([
                    'status' => Term::STATUS_FINALIZED,
                    'locked' => false,
                ]);

                return [
                    'success' => true,
                    'message' => $this->appendPurgeSummary('Term finalized successfully.', $purgeSummary),
                    'purge_summary' => $purgeSummary,
                ];
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to finalize term: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Lock a term. If any upload is still attached, remove it before locking.
     */
    public function lockTerm(int $termId): array
    {
        try {
            return DB::transaction(function () use ($termId) {
                $term = Term::findOrFail($termId);

                if ($term->isLocked()) {
                    return [
                        'success' => false,
                        'message' => 'Term is already locked.',
                    ];
                }

                $purgeSummary = $this->homeworkStorageService->purgeTermAttachments(
                    $term,
                    auth()->id(),
                    'term_locked'
                );

                $term->update([
                    'status' => Term::STATUS_LOCKED,
                    'locked' => true,
                ]);

                return [
                    'success' => true,
                    'message' => $this->appendPurgeSummary('Term locked successfully.', $purgeSummary),
                    'purge_summary' => $purgeSummary,
                ];
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to lock term: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Activate one term and finalize every other active term.
     */
    public function activateTerm(int $termId): array
    {
        try {
            return DB::transaction(function () use ($termId) {
                $term = Term::with('academicYear')->findOrFail($termId);

                if ($term->academicYear && ! $term->academicYear->active) {
                    AcademicYear::where('active', true)
                        ->where('id', '!=', $term->academic_year_id)
                        ->update(['active' => false, 'status' => AcademicYear::STATUS_CLOSED]);

                    $term->academicYear->update([
                        'active' => true,
                        'status' => AcademicYear::STATUS_OPEN,
                    ]);
                }

                $purgeSummary = ['attachments_removed' => 0, 'bytes_released' => 0];

                Term::where('status', Term::STATUS_ACTIVE)
                    ->where('id', '!=', $term->id)
                    ->get()
                    ->each(function (Term $oldTerm) use (&$purgeSummary) {
                        $summary = $this->homeworkStorageService->purgeTermAttachments(
                            $oldTerm,
                            auth()->id(),
                            'term_deactivated'
                        );

                        $purgeSummary['attachments_removed'] += $summary['attachments_removed'];
                        $purgeSummary['bytes_released'] += $summary['bytes_released'];

                        $oldTerm->update([
                            'status' => Term::STATUS_FINALIZED,
                            'locked' => false,
                        ]);
                    });

                $term->update([
                    'status' => Term::STATUS_ACTIVE,
                    'locked' => false,
                ]);

                return [
                    'success' => true,
                    'message' => $this->appendPurgeSummary('Term activated successfully.', $purgeSummary),
                    'purge_summary' => $purgeSummary,
                ];
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to activate term: ' . $e->getMessage(),
            ];
        }
    }

    private function appendPurgeSummary(string $message, array $summary): string
    {
        $removed = (int) ($summary['attachments_removed'] ?? 0);

        if ($removed === 0) {
            return $message;
        }

        $bytes = (int) ($summary['bytes_released'] ?? 0);

        return $message . ' Removed ' . $removed . ' homework attachment(s) and released '
            . $this->homeworkStorageService->formatBytes($bytes) . ' of storage.';
    }
}
