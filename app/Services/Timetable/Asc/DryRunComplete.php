<?php

namespace App\Services\Timetable\Asc;

use RuntimeException;

/**
 * Unwinds a dry run once it has been measured.
 *
 * A dry run performs the whole import for real and then rolls the transaction back,
 * because the only honest way to report what an import *would* do is to do it: matching
 * depends on rows written earlier in the same run, and a lesson's fate depends on
 * whether its subject resolved. Throwing is how that rollback is triggered, so this is
 * control flow rather than an error — XmlImporter::import() is the only place that
 * catches it, and it never escapes.
 */
final class DryRunComplete extends RuntimeException
{
}
