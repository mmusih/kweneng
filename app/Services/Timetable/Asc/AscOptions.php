<?php

namespace App\Services\Timetable\Asc;

use SimpleXMLElement;

/**
 * The aSc file's root metadata, preserved verbatim.
 *
 * Two separate jobs, both about fidelity rather than behaviour:
 *
 * 1. The root `options` string and each section's `columns` list must survive the
 *    round trip untouched (§5.2). aSc treats them as part of the file's identity, so
 *    Phase 9 re-emits exactly what came in rather than regenerating them from the
 *    schema — a regenerated list would silently reorder or drop fields aSc expects.
 * 2. A handful of those flags change how the file must be *parsed*. `decimalseparatordot`
 *    says `periodsperweek="6.0"` uses a dot, and `groupstype1` says the division/group
 *    model is in play. Reading them is cheaper and safer than inferring from the data.
 *
 * The whole object serialises to one JSON blob in `tt_settings.asc_options`.
 */
final class AscOptions
{
    /**
     * @param  array<string, string>  $attributes  root element attributes, verbatim
     * @param  list<string>  $flags  the `options` string split on commas
     * @param  array<string, string>  $sectionColumns  section name => its `columns` attribute
     */
    private function __construct(
        private readonly array $attributes,
        private readonly array $flags,
        private readonly array $sectionColumns,
    ) {}

    public static function fromRoot(SimpleXMLElement $root): self
    {
        $attributes = [];

        foreach ($root->attributes() ?? [] as $name => $value) {
            $attributes[(string) $name] = (string) $value;
        }

        $sectionColumns = [];

        foreach ($root->children() as $section) {
            $columns = $section->attributes()['columns'] ?? null;

            if ($columns !== null) {
                $sectionColumns[$section->getName()] = (string) $columns;
            }
        }

        return new self(
            $attributes,
            self::splitFlags($attributes['options'] ?? ''),
            $sectionColumns,
        );
    }

    public static function fromJson(?string $json): self
    {
        $decoded = is_string($json) && $json !== '' ? json_decode($json, true) : null;

        if (! is_array($decoded)) {
            return new self([], [], []);
        }

        $attributes = is_array($decoded['attributes'] ?? null) ? $decoded['attributes'] : [];

        return new self(
            $attributes,
            self::splitFlags($attributes['options'] ?? ''),
            is_array($decoded['sectionColumns'] ?? null) ? $decoded['sectionColumns'] : [],
        );
    }

    /**
     * @return list<string>
     */
    private static function splitFlags(string $options): array
    {
        if (trim($options) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $options)),
            static fn (string $flag): bool => $flag !== '',
        ));
    }

    public function attribute(string $name): ?string
    {
        return $this->attributes[$name] ?? null;
    }

    public function version(): ?string
    {
        return $this->attribute('ascttversion');
    }

    /**
     * A flag may be bare (`groupstype1`) or prefixed (`export:idprefix:%CHRID`), so a
     * prefix match on `name:` counts as present too.
     */
    public function has(string $flag): bool
    {
        foreach ($this->flags as $candidate) {
            if ($candidate === $flag || str_starts_with($candidate, $flag.':')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function flags(): array
    {
        return $this->flags;
    }

    public function columnsFor(string $section): ?string
    {
        return $this->sectionColumns[$section] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function sectionColumns(): array
    {
        return $this->sectionColumns;
    }

    public function toJson(): string
    {
        return (string) json_encode([
            'attributes' => $this->attributes,
            'sectionColumns' => $this->sectionColumns,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
