<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Turns marks into grades and works out GPA / CGPA,
 * using the scale in config/grading.php.
 */
class Grading
{
    /**
     * Grade and grade point for the given marks, e.g. 78 => ['A-', 3.67].
     *
     * @return array{0: string, 1: float}
     */
    public static function forMarks(int|float $marks): array
    {
        foreach (config('grading.scale') as $minimum => [$grade, $point]) {
            if ($marks >= $minimum) {
                return [$grade, (float) $point];
            }
        }

        return ['F', 0.0];
    }

    /**
     * All grade letters, from best to worst.
     *
     * @return array<int, string>
     */
    public static function grades(): array
    {
        return array_column(array_values(config('grading.scale')), 0);
    }

    /**
     * Credit-weighted average grade point:
     * sum(grade point × credit hours) ÷ sum(credit hours).
     *
     * Only results that have marks count. Each result must have its subject
     * loaded. Returns null when there are no marked results yet.
     *
     * @param  Collection<int, \App\Models\Result>  $results
     */
    public static function gpa(Collection $results): ?float
    {
        $results = $results->filter(fn ($result) => $result->marks !== null);

        $credits = $results->sum(fn ($result) => $result->subject->credit_hours);

        if ($credits === 0) {
            return null;
        }

        $points = $results->sum(fn ($result) => $result->grade_point * $result->subject->credit_hours);

        return round($points / $credits, 2);
    }
}
