<?php

namespace Tests\Feature;

use App\Models\Result;
use App\Models\Subject;
use App\Support\Grading;
use Tests\TestCase;

class GradingTest extends TestCase
{
    public function test_marks_are_converted_to_grades_at_the_boundaries(): void
    {
        $this->assertSame(['A', 4.0], Grading::forMarks(100));
        $this->assertSame(['A', 4.0], Grading::forMarks(80));
        $this->assertSame(['A-', 3.67], Grading::forMarks(79));
        $this->assertSame(['C', 2.0], Grading::forMarks(50));
        $this->assertSame(['C-', 1.67], Grading::forMarks(49));
        $this->assertSame(['D', 1.0], Grading::forMarks(35));
        $this->assertSame(['F', 0.0], Grading::forMarks(34));
        $this->assertSame(['F', 0.0], Grading::forMarks(0));
    }

    public function test_gpa_is_weighted_by_credit_hours(): void
    {
        $results = collect([
            $this->makeResult(85, 4),   // A  4.00 x 4 = 16.00
            $this->makeResult(52, 2),   // C  2.00 x 2 =  4.00
        ]);

        // 20.00 / 6 credits = 3.33
        $this->assertSame(3.33, Grading::gpa($results));
    }

    public function test_subjects_without_marks_are_left_out_of_the_gpa(): void
    {
        $results = collect([
            $this->makeResult(85, 4),     // A 4.00
            $this->makeResult(null, 3),   // registered, not marked yet
        ]);

        $this->assertSame(4.0, Grading::gpa($results));
        $this->assertNull(Grading::gpa(collect([$this->makeResult(null, 3)])));
    }

    public function test_gpa_is_null_without_results(): void
    {
        $this->assertNull(Grading::gpa(collect()));
    }

    private function makeResult(?int $marks, int $credits): Result
    {
        $result = new Result(['marks' => $marks]);
        $result->setRelation('subject', new Subject(['credit_hours' => $credits]));

        return $result;
    }
}
