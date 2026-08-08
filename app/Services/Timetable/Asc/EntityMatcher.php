<?php

namespace App\Services\Timetable\Asc;

use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Support\Collection;

/**
 * Resolves aSc entities onto the rows the school already has.
 *
 * The rule from §Phase 2 is "match where possible, report the rest" — never create.
 * That is not caution for its own sake: `subjects`, `classes`, `teachers` and
 * `students` are the academic core, referenced by marks, elections and report cards.
 * A duplicate "Biology" created by an importer would split a subject's marks in two
 * with no error anywhere, and the damage would only surface at end of term.
 *
 * Matching is therefore exact-after-normalisation and nothing more. No Levenshtein, no
 * "closest match" — §5.6 found "English As a Second Language" (DB) against "English
 * second language" (XML), and a fuzzy matcher confident enough to join those is also
 * confident enough to join "Maths Core" to "Maths Extended", which would put students
 * in the wrong option block. Near-misses go in the report for a human to decide.
 */
final class EntityMatcher
{
    /** @var Collection<int, Subject> */
    private Collection $subjects;

    /** @var Collection<int, ClassModel> */
    private Collection $classes;

    /** @var Collection<int, Teacher> */
    private Collection $teachers;

    /** @var Collection<int, Student>|null */
    private ?Collection $students = null;

    public function __construct()
    {
        $this->subjects = Subject::query()->get();
        $this->classes = ClassModel::query()->get();
        $this->teachers = Teacher::query()->with('user')->get();
    }

    /**
     * Lower-case, strip everything that is not a letter or digit, collapse runs.
     *
     * This is what closes the gap between the XML's "FORM 1A" and the database's
     * "Form 1A" (§5.6) without opening a gap anywhere else.
     */
    public static function normalise(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    /**
     * Subject: code first, then normalised name.
     *
     * Code wins because it is the school's own stable key, while names drift with
     * spelling. aSc's `short` is tried against `subjects.code` as well — the export
     * writes "Bi" for Biology, and where a school has kept those in step it is the
     * most reliable signal in the file.
     */
    public function subject(string $name, ?string $short): ?Subject
    {
        $normalisedShort = self::normalise($short);

        if ($normalisedShort !== '') {
            $byCode = $this->subjects->first(
                fn (Subject $s) => self::normalise($s->code) === $normalisedShort,
            );

            if ($byCode !== null) {
                return $byCode;
            }
        }

        $normalisedName = self::normalise($name);

        if ($normalisedName === '') {
            return null;
        }

        return $this->subjects->first(
            fn (Subject $s) => self::normalise($s->name) === $normalisedName,
        );
    }

    /**
     * Class: normalised name only.
     *
     * aSc's `short` ("F1A") is not tried against anything, because `classes` has no
     * short-name column — matching it against `name` would never hit, and matching it
     * against `level` would be nonsense.
     */
    public function class(string $name, ?string $short = null): ?ClassModel
    {
        $normalised = self::normalise($name);

        if ($normalised === '') {
            return null;
        }

        return $this->classes->first(
            fn (ClassModel $c) => self::normalise($c->name) === $normalised,
        );
    }

    /**
     * Teacher: full name, then an unambiguous surname.
     *
     * The XML writes "K Simukonda" — initial plus surname — so a full-name match only
     * lands if the database happens to store it the same way. The surname fallback is
     * what actually resolves this school's file, and it is guarded: if two teachers
     * share a surname the match is refused rather than guessed, because assigning a
     * lesson to the wrong teacher is exactly the failure that is hardest to spot.
     */
    public function teacher(string $name, ?string $lastName = null): ?Teacher
    {
        $normalised = self::normalise($name);

        if ($normalised !== '') {
            $byFullName = $this->teachers->first(
                fn (Teacher $t) => self::normalise($t->user?->name) === $normalised,
            );

            if ($byFullName !== null) {
                return $byFullName;
            }
        }

        $surname = self::normalise($lastName);

        if ($surname === '') {
            return null;
        }

        $bySurname = $this->teachers->filter(function (Teacher $t) use ($surname) {
            $parts = explode(' ', self::normalise($t->user?->name));

            return $parts !== [] && end($parts) === $surname;
        });

        return $bySurname->count() === 1 ? $bySurname->first() : null;
    }

    /**
     * Student: full name, scoped to the class aSc puts them in.
     *
     * Scoping to the class is what makes a plain name match safe at this scale — two
     * students may share a name across a school of 167, but not within one stream. A
     * name that matches more than once inside a class is refused, not guessed.
     *
     * §5.6 warns off the XML's `number` attribute as an identifier: it repeats the
     * class short name ("1A") and is empty on 81 of 128 students.
     */
    public function student(string $name, ?int $classId): ?Student
    {
        $normalised = self::normalise($name);

        if ($normalised === '' || $classId === null) {
            return null;
        }

        $candidates = $this->studentsIndex()
            ->where('current_class_id', $classId)
            ->filter(fn (Student $s) => self::normalise($s->user?->name) === $normalised);

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    /**
     * @return Collection<int, Student>
     */
    private function studentsIndex(): Collection
    {
        return $this->students ??= Student::query()->with('user')->get();
    }
}
