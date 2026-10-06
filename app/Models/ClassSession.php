<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One group of a subject in a term (e.g. CSC1043 Group B), with its own
 * lecturer(s), students and weekly class times.
 */
#[Fillable(['term_id', 'subject_id', 'name', 'capacity'])]
class ClassSession extends Model
{
    use HasFactory;

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function lecturers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_session_lecturer')->orderBy('name');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class)->orderBy('day')->orderBy('starts_at');
    }

    /** The students registered in this session (their result rows). */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function hasLecturer(User $user): bool
    {
        return $this->lecturers->contains('id', $user->id);
    }

    /** e.g. "CSC1043 Group B". Needs the subject loaded. */
    public function label(): string
    {
        return "{$this->subject->code} {$this->name}";
    }

    public function isFull(?int $registered = null): bool
    {
        return $this->capacity !== null && ($registered ?? $this->results()->count()) >= $this->capacity;
    }
}
