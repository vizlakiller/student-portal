<?php

namespace App\Models;

use App\Support\Grading;
use Database\Factories\ResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A student registered for a subject. Marks stay empty (null) until the
 * subject's teacher enters them.
 */
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

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ResultChangeRequest::class);
    }

    /** The head of department's change request still waiting for the teacher, if any. */
    public function pendingChange(): HasOne
    {
        return $this->hasOne(ResultChangeRequest::class)->where('status', 'pending')->latestOfMany();
    }

    public function isMarked(): bool
    {
        return $this->marks !== null;
    }

    /**
     * $result->grade, e.g. "A-" (null until marks are entered). Always worked
     * out from the marks, so changing config/grading.php updates every result.
     */
    protected function grade(): Attribute
    {
        return Attribute::get(fn () => $this->isMarked() ? Grading::forMarks($this->marks)[0] : null);
    }

    /**
     * $result->grade_point, e.g. 3.67 (null until marks are entered).
     */
    protected function gradePoint(): Attribute
    {
        return Attribute::get(fn () => $this->isMarked() ? Grading::forMarks($this->marks)[1] : null);
    }
}
