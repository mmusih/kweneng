<?php

namespace App\Services;

use App\Models\AwardRun;
use App\Models\StudentAward;
use Carbon\CarbonInterface;

class AwardExcelExportService
{
    public function build(AwardRun $run): string
    {
        $run->loadMissing(['category', 'academicYear', 'term', 'awards']);

        $rows = $run->awards
            ->sortBy(fn (StudentAward $award) => [$award->position ?? PHP_INT_MAX, $award->student_name_snapshot])
            ->values();

        $lastRow = 8 + max(1, $rows->count());
        $sheetRows = [
            $this->row(1, [$this->stringCell('A1', $run->title, 1)]),
            $this->row(2, [$this->stringCell('A2', 'Kweneng International Secondary School', 2)]),
            $this->row(4, [
                $this->stringCell('A4', 'Academic Year', 3),
                $this->stringCell('B4', $run->academicYear?->year_name ?? '', 4),
                $this->stringCell('D4', 'Term', 3),
                $this->stringCell('E4', $run->term?->name ?? 'Annual / Not term-specific', 4),
                $this->stringCell('G4', 'Award Date', 3),
                $this->stringCell('H4', $this->date($run->award_date), 4),
                $this->stringCell('J4', 'Status', 3),
                $this->stringCell('K4', ucfirst($run->status), 4),
            ]),
            $this->row(5, [
                $this->stringCell('A5', 'Scope', 3),
                $this->stringCell('B5', $this->scopeLabel($run), 4),
                $this->stringCell('D5', 'Calculation', 3),
                $this->stringCell('E5', $run->generation_summary['calculation_description'] ?? ucfirst($run->type), 4),
                $this->stringCell('G5', 'Cutoff', 3),
                $run->cutoff_percentage !== null
                    ? $this->numberCell('H5', (float) $run->cutoff_percentage, 8)
                    : $this->stringCell('H5', 'Not applicable', 4),
                $this->stringCell('J5', 'Recipients', 3),
                $this->numberCell('K5', $rows->count(), 7),
            ]),
            $this->row(7, [$this->stringCell('A7', 'Recipients', 2)]),
            $this->row(8, collect([
                'Position', 'Student Name', 'Admission No.', 'Class / Form', 'Level', 'Main Score',
                'Tie-breaker Scores', 'Citation', 'Certificate Reference', 'Selection Source', 'Status',
                'Subject',
            ])->map(fn (string $heading, int $index) => $this->stringCell($this->column($index + 1).'8', $heading, 5))->all()),
        ];

        foreach ($rows as $index => $award) {
            $rowNumber = $index + 9;
            $ties = collect($award->tie_breaker_scores ?? [])->map(function (array $tie) {
                $score = $tie['score'] ?? null;

                return ($tie['label'] ?? 'Tie-breaker').': '.($score !== null ? number_format((float) $score, 2).'%' : 'N/A');
            })->implode('; ');

            $sheetRows[] = $this->row($rowNumber, [
                $award->position !== null ? $this->numberCell("A{$rowNumber}", $award->position, 7) : $this->stringCell("A{$rowNumber}", '', 6),
                $this->stringCell("B{$rowNumber}", $award->student_name_snapshot, 6),
                $this->stringCell("C{$rowNumber}", $award->admission_no_snapshot ?? '', 6),
                $this->stringCell("D{$rowNumber}", $award->class_name_snapshot ?? '', 6),
                $award->level_snapshot !== null ? $this->numberCell("E{$rowNumber}", $award->level_snapshot, 7) : $this->stringCell("E{$rowNumber}", '', 6),
                $award->main_score !== null ? $this->numberCell("F{$rowNumber}", (float) $award->main_score, 8) : $this->stringCell("F{$rowNumber}", '', 6),
                $this->stringCell("G{$rowNumber}", $ties, 9),
                $this->stringCell("H{$rowNumber}", $award->citation ?? '', 9),
                $this->stringCell("I{$rowNumber}", $award->certificate_reference, 6),
                $this->stringCell("J{$rowNumber}", ucfirst($award->selection_source), 6),
                $this->stringCell("K{$rowNumber}", ucfirst($run->status), $run->status === 'published' ? 10 : 6),
                $this->stringCell("L{$rowNumber}", $award->subject_name_snapshot ?? '', 6),
            ]);
        }

        if ($rows->isEmpty()) {
            $sheetRows[] = $this->row(9, [$this->stringCell('A9', 'No recipients in this award run.', 9)]);
        }

        $files = [
            '[Content_Types].xml' => $this->contentTypes(),
            '_rels/.rels' => $this->rootRelationships(),
            'docProps/app.xml' => $this->appProperties(),
            'docProps/core.xml' => $this->coreProperties(),
            'xl/workbook.xml' => $this->workbook(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelationships(),
            'xl/styles.xml' => $this->styles(),
            'xl/worksheets/sheet1.xml' => $this->worksheet(implode('', $sheetRows), $lastRow),
        ];

        return $this->zip($files);
    }

    private function worksheet(string $rows, int $lastRow): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView showGridLines="0" workbookViewId="0"><pane ySplit="8" topLeftCell="A9" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="18"/>'
            .'<cols><col min="1" max="1" width="18" customWidth="1"/><col min="2" max="2" width="28" customWidth="1"/><col min="3" max="3" width="18" customWidth="1"/><col min="4" max="4" width="20" customWidth="1"/><col min="5" max="5" width="16" customWidth="1"/><col min="6" max="6" width="18" customWidth="1"/><col min="7" max="7" width="34" customWidth="1"/><col min="8" max="8" width="48" customWidth="1"/><col min="9" max="9" width="25" customWidth="1"/><col min="10" max="11" width="18" customWidth="1"/><col min="12" max="12" width="24" customWidth="1"/></cols>'
            .'<sheetData>'.$rows.'</sheetData>'
            .'<autoFilter ref="A8:L'.$lastRow.'"/>'
            .'<mergeCells count="9"><mergeCell ref="A1:L1"/><mergeCell ref="A2:L2"/><mergeCell ref="A7:L7"/><mergeCell ref="B4:C4"/><mergeCell ref="E4:F4"/><mergeCell ref="H4:I4"/><mergeCell ref="B5:C5"/><mergeCell ref="E5:F5"/><mergeCell ref="H5:I5"/></mergeCells>'
            .'<pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            .'<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0" paperSize="9"/>'
            .'</worksheet>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="0.00\%"/></numFmts>'
            .'<fonts count="4"><font><sz val="11"/><name val="Aptos"/></font><font><b/><sz val="18"/><color rgb="FFFFFFFF"/><name val="Aptos Display"/></font><font><b/><sz val="12"/><color rgb="FF124E66"/><name val="Aptos"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Aptos"/></font></fonts>'
            .'<fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF124E66"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD4AF37"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD1FAE5"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left/><right style="thin"><color rgb="FFE5EBEF"/></right><top/><bottom style="thin"><color rgb="FFD9E2E8"/></bottom><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="11">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="0" fontId="3" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top"/></xf>'
            .'<xf numFmtId="1" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="top"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="top"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1"/>'
            .'</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView/></bookViews><sheets><sheet name="Award Recipients" sheetId="1" r:id="rId1"/></sheets><calcPr calcId="191029"/></workbook>';
    }

    private function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function appProperties(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Kweneng School ERP</Application></Properties>';
    }

    private function coreProperties(): string
    {
        $now = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Award Recipients Export</dc:title><dc:creator>Kweneng School ERP</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:modified></cp:coreProperties>';
    }

    private function row(int $number, array $cells): string
    {
        return '<row r="'.$number.'">'.implode('', $cells).'</row>';
    }

    private function stringCell(string $reference, mixed $value, int $style = 0): string
    {
        $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$escaped.'</t></is></c>';
    }

    private function numberCell(string $reference, int|float $value, int $style = 0): string
    {
        return '<c r="'.$reference.'" s="'.$style.'" t="n"><v>'.(0 + $value).'</v></c>';
    }

    private function date(CarbonInterface|string|null $date): string
    {
        if (! $date) {
            return '';
        }

        return $date instanceof CarbonInterface ? $date->format('Y-m-d') : (string) $date;
    }

    private function scopeLabel(AwardRun $run): string
    {
        return match ($run->scope_type) {
            'all_levels' => 'Top students from every form / level',
            'level' => 'Form / Level '.$run->level,
            'class' => $run->classModel?->name ?? 'Selected class',
            'selected' => 'Selected classes',
            default => 'Whole school',
        };
    }

    private function column(int $number): string
    {
        $column = '';
        while ($number > 0) {
            $number--;
            $column = chr(65 + ($number % 26)).$column;
            $number = intdiv($number, 26);
        }

        return $column;
    }

    /** @param array<string, string> $files */
    private function zip(array $files): string
    {
        $archive = '';
        $directory = '';
        $entries = 0;
        $dosTime = ((int) now()->format('H') << 11) | ((int) now()->format('i') << 5) | intdiv((int) now()->format('s'), 2);
        $dosDate = (((int) now()->format('Y') - 1980) << 9) | ((int) now()->format('n') << 5) | (int) now()->format('j');

        foreach ($files as $name => $contents) {
            $name = str_replace('\\', '/', $name);
            $crc = crc32($contents);
            $size = strlen($contents);
            $offset = strlen($archive);
            $flags = 0x0800;

            $archive .= pack('VvvvvvVVVvv', 0x04034B50, 20, $flags, 0, $dosTime, $dosDate, $crc, $size, $size, strlen($name), 0).$name.$contents;
            $directory .= pack('VvvvvvvVVVvvvvvVV', 0x02014B50, 20, 20, $flags, 0, $dosTime, $dosDate, $crc, $size, $size, strlen($name), 0, 0, 0, 0, 0, $offset).$name;
            $entries++;
        }

        $directoryOffset = strlen($archive);
        $archive .= $directory;
        $archive .= pack('VvvvvVVv', 0x06054B50, 0, 0, $entries, $entries, strlen($directory), $directoryOffset, 0);

        return $archive;
    }
}
