<?php

namespace App\Services;

use App\Models\Homework;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class HomeworkStorageService
{
    /**
     * Delete a single homework attachment from storage and clear its storage fields.
     *
     * The homework record itself remains available for submission tracking and audit.
     */
    public function deleteAttachment(Homework $homework, ?int $userId = null, string $reason = 'manual_delete'): array
    {
        $disk = $homework->attachmentDisk();
        $path = $homework->attachmentStoragePath();
        $size = $homework->attachmentSize();
        $deleted = false;

        if ($path && Storage::disk($disk)->exists($path)) {
            $deleted = Storage::disk($disk)->delete($path);

            if (! $deleted) {
                throw new RuntimeException('Unable to delete homework attachment from storage: ' . $path);
            }
        }

        $homework->forceFill([
            'attachment_path' => null,
            'attachment_original_name' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
            'image_disk' => null,
            'image_path' => null,
            'image_original_name' => null,
            'image_mime_type' => null,
            'image_size' => null,
            'attachment_deleted_at' => now(),
            'attachment_deleted_by' => $userId,
            'attachment_deleted_reason' => $reason,
            'attachment_storage_released_bytes' => $size,
        ])->save();

        return [
            'deleted' => $deleted,
            'path' => $path,
            'disk' => $disk,
            'bytes_released' => $size,
        ];
    }

    /**
     * Remove all teacher-uploaded homework files for a term while keeping homework records.
     */
    public function purgeTermAttachments(Term|int $term, ?int $userId = null, string $reason = 'term_closed'): array
    {
        $termId = $term instanceof Term ? $term->id : (int) $term;
        $summary = [
            'homeworks_scanned' => 0,
            'attachments_removed' => 0,
            'bytes_released' => 0,
        ];

        Homework::withTrashed()
            ->where('term_id', $termId)
            ->where(function ($query) {
                $query->whereNotNull('attachment_path')
                    ->orWhereNotNull('image_path');
            })
            ->orderBy('id')
            ->chunkById(100, function ($homeworks) use (&$summary, $userId, $reason) {
                foreach ($homeworks as $homework) {
                    $summary['homeworks_scanned']++;
                    $result = $this->deleteAttachment($homework, $userId, $reason);

                    if ($result['path']) {
                        $summary['attachments_removed']++;
                    }

                    $summary['bytes_released'] += (int) $result['bytes_released'];
                }
            });

        return $summary;
    }

    /**
     * Soft-delete a homework and remove its uploaded file.
     */
    public function deleteHomework(Homework $homework, ?int $userId = null, string $reason = 'homework_deleted'): array
    {
        return DB::transaction(function () use ($homework, $userId, $reason) {
            $result = $homework->hasAttachment()
                ? $this->deleteAttachment($homework, $userId, $reason)
                : ['deleted' => false, 'path' => null, 'disk' => $homework->attachmentDisk(), 'bytes_released' => 0];

            $homework->delete();

            return $result;
        });
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $unit) {
            if ($value < 1024 || $unit === 'TB') {
                return number_format($value, $value >= 10 ? 1 : 2) . ' ' . $unit;
            }

            $value /= 1024;
        }

        return $bytes . ' B';
    }
}
