<?php

namespace App\Models;

use App\Support\Timetable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A weekly class time: a session meets on a day, from a start to an end time, in a classroom.
 */
#[Fillable(['day', 'starts_at', 'ends_at', 'classroom_id'])]
class TimetableSlot extends Model
{
    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function dayName(): string
    {
        return Timetable::DAYS[$this->day] ?? '?';
    }

    /** e.g. "08:00 to 10:00" */
    public function timeRange(): string
    {
        return substr($this->starts_at, 0, 5).' to '.substr($this->ends_at, 0, 5);
    }

    /** e.g. "Monday 08:00 to 10:00" */
    public function whenLabel(): string
    {
        return $this->dayName().' '.$this->timeRange();
    }
}
