<?php

namespace App\Models;

use App\Support\Grading;
use Database\Factories\ResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['subject_id', 'semester', 'marks'])]
class Result extends Model
{
    /** @use HasFactory<ResultFactory> */
    use HasFactory;

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * $result->grade, e.g. "A-". Always worked out from the marks,
     * so changing config/grading.php updates every result.
     */
    protected function grade(): Attribute
    {
        return Attribute::get(fn () => Grading::forMarks($this->marks)[0]);
    }

    /**
     * $result->grade_point, e.g. 3.67.
     */
    protected function gradePoint(): Attribute
    {
        return Attribute::get(fn () => Grading::forMarks($this->marks)[1]);
    }
}
