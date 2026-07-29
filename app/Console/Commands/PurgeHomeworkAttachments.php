<?php

namespace App\Console\Commands;

use App\Models\Homework;
use App\Models\Term;
use App\Services\HomeworkStorageService;
use Illuminate\Console\Command;

class PurgeHomeworkAttachments extends Command
{
    protected $signature = 'homework:purge-attachments
        {--term_id= : Purge homework uploads for one term ID}
        {--closed-terms : Purge homework uploads for all finalized and locked terms}';

    protected $description = 'Delete homework uploaded files while keeping homework records and submission tracking.';

    public function handle(HomeworkStorageService $storage): int
    {
        $termId = $this->option('term_id');
        $closedTerms = (bool) $this->option('closed-terms');

        if (! $termId && ! $closedTerms) {
            $this->error('Provide --term_id=ID or --closed-terms.');
            return self::FAILURE;
        }

        $terms = $termId
            ? Term::whereKey((int) $termId)->get()
            : Term::whereIn('status', [Term::STATUS_FINALIZED, Term::STATUS_LOCKED])->orderBy('id')->get();

        if ($terms->isEmpty()) {
            $this->info('No matching terms found.');
            return self::SUCCESS;
        }

        $totalAttachments = 0;
        $totalBytes = 0;

        foreach ($terms as $term) {
            $before = Homework::withTrashed()
                ->where('term_id', $term->id)
                ->where(function ($query) {
                    $query->whereNotNull('attachment_path')
                        ->orWhereNotNull('image_path');
                })
                ->count();

            if ($before === 0) {
                $this->line("{$term->name}: no attached homework files found.");
                continue;
            }

            $summary = $storage->purgeTermAttachments($term, null, 'artisan_cleanup');

            $totalAttachments += (int) $summary['attachments_removed'];
            $totalBytes += (int) $summary['bytes_released'];

            $this->info("{$term->name}: removed {$summary['attachments_removed']} attachment(s), released {$storage->formatBytes((int) $summary['bytes_released'])}.");
        }

        $this->info("Done. Removed {$totalAttachments} attachment(s), released {$storage->formatBytes($totalBytes)}.");

        return self::SUCCESS;
    }
}
