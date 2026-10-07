<?php

namespace App\Support;

class AcademicYearLabel
{
    public static function singleYear(?string $label): ?string
    {
        if ($label === null) {
            return null;
        }

        // Older records used school-year ranges; the first year is the calendar label.
        return preg_replace('/(?<!\d)([1-9]\d{3})\s*[\/\-\x{2013}\x{2014}]\s*[1-9]\d{3}(?!\d)/u', '$1', $label);
    }
}
