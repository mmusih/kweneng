<?php

namespace App\Services\Timetable;

use App\Models\Tt\Constraint;
use App\Models\Tt\Lesson;
use App\Models\Tt\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Build the whole-timetable audit used by the verification page and publish gate. */
class VerificationService
{
    public function __construct(private readonly CardPlacementService $placement) {}

    /** @return array<string, mixed> */
    public function verify(Setting $setting): array
    {
        $lessons = $setting->lessons()->with([
            'subject', 'teachers.user', 'classes', 'groups', 'rooms', 'cards.room',
            'setting', 'weeksDef', 'termsDef',
        ])->get();

        $unplaced = $this->unplaced($lessons);
        $conflicts = $this->conflicts($setting, $lessons);
        $workloads = $this->workloads($setting, $lessons);
        $rooms = $this->roomIssues($lessons, $conflicts);
        $gaps = $this->classGaps($setting, $lessons);
        $constraints = $setting->constraints()->active()->orderByDesc('weight')->get()
            ->map(fn (Constraint $constraint) => [
                'kind' => $constraint->kind,
                'weight' => (int) $constraint->weight,
                'hard' => $constraint->isHard(),
                'message' => 'This configured constraint has no evaluator yet and was not included in the score.',
            ])->values()->all();

        $violationsByWeight = collect($conflicts)->groupBy(fn () => Constraint::WEIGHT_HARD)
            ->map->count()->all();

        $hardFailures = count($unplaced)
            + collect($conflicts)->whereIn('kind', ['teacher', 'students', 'room', 'structure'])->count()
            + collect($workloads)->sum(fn (array $row) => count($row['overloaded_days']))
            + collect($rooms)->where('kind', 'capacity')->count();

        return [
            'clean' => $hardFailures === 0,
            'summary' => [
                'hard_failures' => $hardFailures,
                'unplaced_lessons' => count($unplaced),
                'conflicts' => count($conflicts),
                'workload_issues' => collect($workloads)->filter(fn ($row) => $row['unplaced_periods'] > 0 || $row['overloaded_days'] !== [])->count(),
                'room_issues' => count($rooms),
                'class_gaps' => count($gaps),
            ],
            'unplaced' => $unplaced,
            'workloads' => $workloads,
            'room_issues' => $rooms,
            'class_gaps' => $gaps,
            'conflicts' => $conflicts,
            'constraints' => $constraints,
            'violations_by_weight' => $violationsByWeight,
        ];
    }

    /** @param Collection<int, Lesson> $lessons */
    private function unplaced(Collection $lessons): array
    {
        return $lessons->map(function (Lesson $lesson) {
            $count = $this->placement->unplacedCount($lesson);

            return $count > 0 ? [
                'lesson_id' => (int) $lesson->id,
                'subject' => $lesson->subject?->name ?? 'Unknown subject',
                'classes' => $lesson->classes->pluck('name')->implode(', '),
                'teachers' => $lesson->teachers->pluck('user.name')->filter()->implode(', '),
                'cards' => $count,
                'periods' => $count * max(1, (int) $lesson->periods_per_card),
            ] : null;
        })->filter()->values()->all();
    }

    /** @param Collection<int, Lesson> $lessons */
    private function conflicts(Setting $setting, Collection $lessons): array
    {
        $pool = ConflictChecker::poolFor((int) $setting->id);
        $checker = (new ConflictChecker)->withPool($pool);
        $found = [];

        foreach ($lessons as $lesson) {
            foreach ($this->placement->unitsFor($lesson) as $unit) {
                foreach ($unit->first()->daysMask()->positions() as $day) {
                    foreach ($checker->check($lesson, $day, $unit->startPeriod(), $unit->roomId(), $unit->cardIds()) as $conflict) {
                        $ids = array_filter(array_merge($unit->cardIds(), [$conflict->cardId]));
                        sort($ids);
                        $key = $conflict->kind.'|'.implode('-', $ids).'|'.$day.'|'.$conflict->periodNumber;
                        $found[$key] = $conflict->toArray() + [
                            'day' => $day,
                            'subject' => $lesson->subject?->name ?? 'Unknown subject',
                        ];
                    }
                }
            }
        }

        return array_values($found);
    }

    /** @param Collection<int, Lesson> $lessons */
    private function workloads(Setting $setting, Collection $lessons): array
    {
        $maxima = DB::table('tt_teacher_meta')->pluck('max_lessons_per_day', 'teacher_id');
        $teachers = $lessons->flatMap->teachers->unique('id')->sortBy('user.name');

        return $teachers->map(function ($teacher) use ($lessons, $maxima) {
            $mine = $lessons->filter(fn (Lesson $lesson) => $lesson->teachers->contains('id', $teacher->id));
            $loads = [];

            foreach ($mine as $lesson) {
                foreach ($lesson->cards as $card) {
                    foreach ($card->daysMask()->positions() as $day) {
                        foreach ($card->weeksMask()->positions() as $week) {
                            foreach ($card->termsMask()->positions() as $term) {
                                $key = implode('|', [$day, $week, $term]);
                                $loads[$key] = ($loads[$key] ?? 0) + 1;
                            }
                        }
                    }
                }
            }

            $maximum = $maxima[$teacher->id] ?? null;
            $overloaded = $maximum === null ? [] : collect($loads)
                ->filter(fn (int $load) => $load > (int) $maximum)
                ->map(function (int $load, string $key) use ($maximum) {
                    [$day, $week, $term] = array_map('intval', explode('|', $key));

                    return ['day' => $day, 'week' => $week, 'term' => $term, 'periods' => $load, 'maximum' => (int) $maximum];
                })
                ->values()->all();

            return [
                'teacher_id' => (int) $teacher->id,
                'teacher' => $teacher->user?->name ?? 'Unknown teacher',
                'demand_periods' => (float) $mine->sum(fn (Lesson $lesson) => (float) $lesson->periods_per_week),
                'placed_periods' => $mine->sum(fn (Lesson $lesson) => $lesson->cards->count()),
                'unplaced_periods' => $mine->sum(fn (Lesson $lesson) => $this->placement->unplacedCount($lesson) * max(1, (int) $lesson->periods_per_card)),
                'max_per_day' => $maximum === null ? null : (int) $maximum,
                'overloaded_days' => $overloaded,
            ];
        })->values()->all();
    }

    /** @param Collection<int, Lesson> $lessons */
    private function roomIssues(Collection $lessons, array $conflicts): array
    {
        $issues = collect($conflicts)->where('kind', 'room')->map(fn ($row) => [
            'hard' => true,
            'kind' => 'clash',
            'message' => $row['message'],
        ]);

        foreach ($lessons as $lesson) {
            foreach ($lesson->cards as $card) {
                if ($card->room && $lesson->capacity !== null && $card->room->capacity !== null
                    && (int) $lesson->capacity > (int) $card->room->capacity) {
                    $issues->push([
                        'hard' => true,
                        'kind' => 'capacity',
                        'message' => "{$card->room->name} holds {$card->room->capacity}, but {$lesson->subject?->name} needs {$lesson->capacity} places.",
                    ]);
                } elseif ($card->tt_room_id === null) {
                    $issues->push([
                        'hard' => false,
                        'kind' => 'missing',
                        'message' => ($lesson->subject?->name ?? 'A lesson').' has a placement without a room.',
                    ]);
                }
            }
        }

        return $issues->unique('message')->values()->all();
    }

    /** @param Collection<int, Lesson> $lessons */
    private function classGaps(Setting $setting, Collection $lessons): array
    {
        $issues = [];
        $periods = $setting->periods()->pluck('period_number')->map(fn ($n) => (int) $n)->all();

        foreach ($lessons->flatMap->classes->unique('id') as $class) {
            $mine = $lessons->filter(fn (Lesson $lesson) => $lesson->classes->contains('id', $class->id));
            foreach (range(1, max(1, (int) $setting->cycle_length)) as $day) {
                $occupied = $mine->flatMap(fn (Lesson $lesson) => $lesson->cards
                    ->filter(fn ($card) => $card->daysMask()->has($day))
                    ->pluck('period_number'))->map(fn ($n) => (int) $n)->unique()->sort()->values();
                if ($occupied->count() < 2) {
                    continue;
                }
                $gaps = collect($periods)->filter(fn (int $period) => $period > $occupied->first()
                    && $period < $occupied->last() && ! $occupied->contains($period))->values()->all();
                if ($gaps !== []) {
                    $issues[] = ['class_id' => (int) $class->id, 'class' => $class->name, 'day' => $day, 'periods' => $gaps];
                }
            }
        }

        return $issues;
    }
}
