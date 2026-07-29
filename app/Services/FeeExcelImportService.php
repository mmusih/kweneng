<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class FeeExcelImportService
{
    /**
     * Parse the first sheet of the Excel workbook.
     * Expected columns from the school spreadsheet:
     * B = surname, C = student names, J = opening balance, K = payment, L = closing balance.
     */
    public function parse(string $filePath): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive extension is required to read .xlsx files. Please enable ext-zip on the server.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('Unable to open Excel file. Please upload a valid .xlsx file.');
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('Unable to read the first worksheet from this Excel file.');
        }

        $sheet = simplexml_load_string($sheetXml);
        if (! $sheet || ! isset($sheet->sheetData->row)) {
            throw new RuntimeException('The Excel worksheet appears to be empty.');
        }

        $rows = [];

        foreach ($sheet->sheetData->row as $row) {
            $excelRowNumber = (int) ($row['r'] ?? 0);
            $cells = [];

            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                preg_match('/^[A-Z]+/', $reference, $matches);
                $column = $matches[0] ?? null;

                if (! $column) {
                    continue;
                }

                $cells[$column] = $this->cellValue($cell, $sharedStrings);
            }

            $surname = trim((string) ($cells['B'] ?? ''));
            $studentNames = trim((string) ($cells['C'] ?? ''));

            if ($surname === '' && $studentNames === '') {
                continue;
            }

            $headerText = strtolower($surname . ' ' . $studentNames);
            if (str_contains($headerText, 'surname') || str_contains($headerText, 'student')) {
                continue;
            }

            $closingBalance = $this->moneyToDecimal($cells['L'] ?? null);

            // Ignore non-student summary/total rows with no usable balance.
            if ($studentNames === '' || $surname === '') {
                continue;
            }

            $rows[] = [
                'excel_row_number' => $excelRowNumber,
                'form' => trim((string) ($cells['A'] ?? '')) ?: null,
                'surname' => $surname,
                'student_names' => $studentNames,
                'opening_balance' => $this->moneyToDecimal($cells['J'] ?? null),
                'payment' => $this->moneyToDecimal($cells['K'] ?? null),
                'closing_balance' => $closingBalance,
            ];
        }

        return $rows;
    }

    public function matchRows(array $rows): array
    {
        $students = Student::with(['user', 'currentClass'])->get();

        return array_map(function (array $row) use ($students) {
            $matches = $this->matchStudent($row, $students);
            $count = $matches->count();

            if ($count === 1) {
                $student = $matches->first();
                $row['matched_student_id'] = $student->id;
                $row['match_status'] = 'matched';
                $row['match_notes'] = 'Matched to ' . ($student->user->name ?? 'student ID ' . $student->id);
                $row['possible_student_ids'] = null;
            } elseif ($count > 1) {
                $row['matched_student_id'] = null;
                $row['match_status'] = 'ambiguous';
                $row['match_notes'] = 'More than one possible student match. Please review manually.';
                $row['possible_student_ids'] = $matches->pluck('id')->values()->all();
            } else {
                $row['matched_student_id'] = null;
                $row['match_status'] = 'unmatched';
                $row['match_notes'] = 'No matching student found using surname and student names.';
                $row['possible_student_ids'] = null;
            }

            return $row;
        }, $rows);
    }

    private function matchStudent(array $row, Collection $students): Collection
    {
        $surname = $this->normalizeName($row['surname'] ?? '');
        $studentNames = $this->normalizeName($row['student_names'] ?? '');
        $expectedGivenSurname = trim($studentNames . ' ' . $surname);
        $expectedSurnameGiven = trim($surname . ' ' . $studentNames);

        $exact = $students->filter(function ($student) use ($expectedGivenSurname, $expectedSurnameGiven) {
            $name = $this->normalizeName($student->user->name ?? '');
            return $name !== '' && ($name === $expectedGivenSurname || $name === $expectedSurnameGiven);
        })->values();

        if ($exact->count() > 0) {
            return $exact;
        }

        $tokens = array_values(array_filter(explode(' ', trim($studentNames . ' ' . $surname))));

        return $students->filter(function ($student) use ($tokens) {
            $name = $this->normalizeName($student->user->name ?? '');
            if ($name === '') {
                return false;
            }

            foreach ($tokens as $token) {
                if ($token !== '' && ! str_contains($name, $token)) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $shared = simplexml_load_string($xml);
        if (! $shared || ! isset($shared->si)) {
            return [];
        }

        $strings = [];
        foreach ($shared->si as $si) {
            $text = '';
            if (isset($si->t)) {
                $text = (string) $si->t;
            } elseif (isset($si->r)) {
                foreach ($si->r as $run) {
                    $text .= (string) ($run->t ?? '');
                }
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private function cellValue($cell, array $sharedStrings): string
    {
        $type = (string) ($cell['t'] ?? '');

        if ($type === 's') {
            $index = (int) ($cell->v ?? 0);
            return (string) ($sharedStrings[$index] ?? '');
        }

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        return trim((string) ($cell->v ?? ''));
    }

    private function moneyToDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $negative = str_starts_with($raw, '(') && str_ends_with($raw, ')');
        $clean = preg_replace('/[^0-9.\-]/', '', $raw);

        if ($clean === '' || $clean === '-' || $clean === '.') {
            return null;
        }

        $amount = (float) $clean;
        return $negative ? -abs($amount) : $amount;
    }

    private function normalizeName(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }
}
