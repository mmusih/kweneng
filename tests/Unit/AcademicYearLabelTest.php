<?php

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\Tt\Setting;
use App\Support\AcademicYearLabel;
use PHPUnit\Framework\TestCase;

class AcademicYearLabelTest extends TestCase
{
    public function test_legacy_years_are_single_years_in_attributes_and_serialized_payloads(): void
    {
        $year = new AcademicYear(['year_name' => '2026/2027']);
        $setting = new Setting([
            'name' => 'Master Timetable 2026/2027',
            'term_label' => '2026/2027 · Term 1',
        ]);

        $this->assertSame('2026', $year->year_name);
        $this->assertSame('2026', $year->toArray()['year_name']);
        $this->assertSame('Master Timetable 2026', $setting->name);
        $this->assertSame('2026 · Term 1', $setting->term_label);
        $this->assertSame('2026 · Term 1', $setting->toArray()['term_label']);
        $this->assertSame('2026/2027', $year->getAttributes()['year_name']);
    }

    public function test_range_separators_are_supported_and_other_labels_are_preserved(): void
    {
        foreach (['2026/2027', '2026 / 2027', '2026-2027', '2026–2027', '2026—2027'] as $label) {
            $this->assertSame('2026', AcademicYearLabel::singleYear($label));
        }

        foreach ([null, '', '2026', 'Term 1', 'Afternoon / study'] as $label) {
            $this->assertSame($label, AcademicYearLabel::singleYear($label));
        }
    }
}
