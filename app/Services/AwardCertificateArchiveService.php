<?php

namespace App\Services;

use App\Models\AwardRun;
use App\Models\StudentAward;
use Barryvdh\DomPDF\Facade\Pdf;
use Generator;
use Illuminate\Support\Str;

class AwardCertificateArchiveService
{
    /**
     * Stream a ZIP archive one entry at a time.
     *
     * The local ZIP header is yielded before each PDF is rendered. This lets the
     * web server start the response immediately and keeps only one PDF in memory.
     *
     * @return Generator<int, string>
     */
    public function stream(AwardRun $run, string $logoPath = ''): Generator
    {
        $run->loadMissing(['category', 'academicYear', 'term', 'awards']);
        $directory = '';
        $entries = 0;
        $offset = 0;
        $flags = 0x0808; // UTF-8 filenames and a data descriptor after each file.
        $now = now();
        $dosTime = ((int) $now->format('H') << 11) | ((int) $now->format('i') << 5) | intdiv((int) $now->format('s'), 2);
        $dosDate = (((int) $now->format('Y') - 1980) << 9) | ((int) $now->format('n') << 5) | (int) $now->format('j');

        foreach ($run->awards->sortBy(fn (StudentAward $award) => [$award->position ?? PHP_INT_MAX, $award->student_name_snapshot]) as $award) {
            $name = str_replace('\\', '/', Str::slug($award->student_name_snapshot).'-'.$award->certificate_reference.'.pdf');
            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034B50,
                20,
                $flags,
                0,
                $dosTime,
                $dosDate,
                0,
                0,
                0,
                strlen($name),
                0,
            ).$name;
            $entryOffset = $offset;
            $offset += strlen($localHeader);

            // Yielding the header before rendering prevents upstream request
            // timeouts while DomPDF works on the first certificate.
            yield $localHeader;

            $contents = Pdf::loadView('pdf.award-certificate', [
                'run' => $run,
                'award' => $award,
                'logoPath' => $logoPath,
            ])->setPaper('a4', 'landscape')->output();

            $crc = crc32($contents);
            $size = strlen($contents);
            $descriptor = pack('VVVV', 0x08074B50, $crc, $size, $size);

            yield $contents;
            yield $descriptor;

            $directory .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014B50,
                20,
                20,
                $flags,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $size,
                $size,
                strlen($name),
                0,
                0,
                0,
                0,
                0,
                $entryOffset,
            ).$name;

            $offset += $size + strlen($descriptor);
            $entries++;
            unset($contents);
        }

        $directoryOffset = $offset;
        yield $directory;
        yield pack('VvvvvVVv', 0x06054B50, 0, 0, $entries, $entries, strlen($directory), $directoryOffset, 0);
    }
}
