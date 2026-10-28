<?php

namespace App\Services;

use Illuminate\Support\Collection;

class BehaviourSummaryService
{
    public function label(Collection $records): string
    {
        if ($records->isEmpty()) return 'No incidents recorded';
        return $records->groupBy('severity')->map(fn ($items, $severity) =>
            $items->count().' '.$severity)->implode(', ').' incident(s)';
    }
}
