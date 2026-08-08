<?php

namespace App\Console\Commands;

use App\Services\Timetable\Asc\ImportReport;
use App\Services\Timetable\Asc\XmlImporter;
use Illuminate\Console\Command;
use Throwable;

class TimetableImportAsc extends Command
{
    protected $signature = 'timetable:import-asc
        {file : Path to the aSc TimeTables XML export}
        {--dry-run : Report what would be imported, then roll everything back}
        {--year= : Academic year id to attach the timetable to (defaults to the active year)}
        {--name= : Name for the created timetable setting (defaults to the file name)}
        {--term= : Term label, e.g. "Term 2"}
        {--json= : Also write the full report to this path as JSON}';

    protected $description = 'Import an aSc TimeTables XML export into the tt_* timetable tables';

    public function handle(XmlImporter $importer): int
    {
        $file = (string) $this->argument('file');
        $dryRun = (bool) $this->option('dry-run');

        $this->components->info(sprintf(
            '%s %s',
            $dryRun ? 'Dry run against' : 'Importing',
            $file,
        ));

        try {
            $report = $importer->import($file, [
                'dry_run' => $dryRun,
                'academic_year_id' => $this->option('year') !== null ? (int) $this->option('year') : null,
                'name' => $this->option('name'),
                'term_label' => $this->option('term'),
            ]);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->renderCounts($report);
        $this->renderUnmatched($report);
        $this->renderEmptyGroups($report);
        $this->renderWarnings($report);
        $this->writeJson($report);

        return $this->renderOutcome($report);
    }

    /**
     * The real export lists 69 unmatched students, which is more than a terminal keeps.
     * The console output stays a summary; --json is the copy you can actually work
     * through, diff between runs, or hand to someone else.
     */
    private function writeJson(ImportReport $report): void
    {
        $path = $this->option('json');

        if ($path === null || $path === '') {
            return;
        }

        $json = json_encode(
            $report->toArray(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        if ($json === false || @file_put_contents($path, $json) === false) {
            $this->components->warn("Could not write the report to [{$path}].");

            return;
        }

        $this->components->info("Full report written to {$path}");
    }

    private function renderCounts(ImportReport $report): void
    {
        $counts = $report->counts();

        if ($counts === []) {
            $this->components->warn('Nothing in the file was recognised.');

            return;
        }

        ksort($counts);

        $this->newLine();
        $this->table(
            ['Entity', 'Created', 'Updated', 'Matched', 'Skipped'],
            collect($counts)->map(fn (array $row, string $type) => [
                $type,
                $row['created'] ?: '-',
                $row['updated'] ?: '-',
                $row['matched'] ?: '-',
                $row['skipped'] ?: '-',
            ])->values()->all(),
        );
    }

    /**
     * The heart of the command. An aSc entity with no local counterpart is the thing
     * the admin has to act on, so it is listed in full rather than summarised — and
     * grouped by type, because "11 subjects unmatched" and "1 class unmatched" call for
     * very different responses.
     */
    private function renderUnmatched(ImportReport $report): void
    {
        $unmatched = $report->unmatchedEntities();

        if ($unmatched === []) {
            $this->components->info('Every aSc entity matched a local record.');

            return;
        }

        $this->newLine();
        $this->components->warn(sprintf('%d aSc entities had no local match:', count($unmatched)));

        foreach (collect($unmatched)->groupBy('type') as $type => $rows) {
            $this->newLine();
            $this->line("  <options=bold>{$type}</> (".count($rows).')');

            foreach ($rows as $row) {
                $this->line(sprintf('    <fg=yellow>%s</> — %s', $row['name'], $row['reason']));
            }
        }
    }

    private function renderEmptyGroups(ImportReport $report): void
    {
        $empty = $report->emptyGroups();

        if ($empty === []) {
            return;
        }

        $this->newLine();
        $this->components->warn(sprintf(
            '%d option group(s) resolve to zero students. Membership comes from subject '
            .'elections, so these mean the elections need fixing:',
            count($empty),
        ));

        $this->table(
            ['Group', 'Class', 'Subject', 'Teacher'],
            collect($empty)->map(fn (array $row) => [
                $row['group'],
                $row['class'],
                $row['subject'] ?? '-',
                $row['teacher'] ?? '-',
            ])->all(),
        );
    }

    private function renderWarnings(ImportReport $report): void
    {
        $warnings = $report->warnings();

        if ($warnings === []) {
            return;
        }

        $this->newLine();
        $this->line('<options=bold>Warnings</>');

        foreach ($warnings as $warning) {
            $this->line("  <fg=yellow>·</> {$warning}");
        }
    }

    private function renderOutcome(ImportReport $report): int
    {
        $this->newLine();

        if ($report->dryRun) {
            $this->components->info('Dry run complete — everything was rolled back.');

            return self::SUCCESS;
        }

        $this->components->info(sprintf(
            'Imported as timetable setting #%d, saved as an inactive draft. '
            .'Review it before publishing.',
            $report->settingId(),
        ));

        return self::SUCCESS;
    }
}
