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
 * A student registered for a subject (in a session). Marks stay empty (null)
 * until the session's lecturer enters them.
 */
#[Fillable(['subject_id', 'class_session_id', 'semester', 'marks'])]
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

    /** The session (group) the student was registered in, if any. */
    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    /**
     * Lecturers who may enter marks and approve changes for this result:
     * the lecturers of its session, or (for older results without a session)
     * the lecturers of the subject.
     */
    public function responsibleLecturers(): \Illuminate\Support\Collection
    {
        return $this->class_session_id
            ? $this->classSession->lecturers
            : $this->subject->lecturers;
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ResultChangeRequest::class);
    }

    /** The head of department's change request still waiting for the lecturer, if any. */
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
