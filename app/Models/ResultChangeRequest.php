<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A head of department's request to change a mark. It only takes effect
 * when the teacher of that subject approves it.
 */
#[Fillable(['old_marks', 'new_marks', 'reason', 'requested_by', 'status', 'decided_by', 'decided_at', 'decision_note'])]
class ResultChangeRequest extends Model
{
    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * Requests a user should see: teachers see requests for their own subjects,
     * a head of department sees the requests they made, the super admin sees all.
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasRole('teacher')) {
            $query->whereHas('result.subject', fn (Builder $subject) => $subject->where('teacher_id', $user->id));
        } elseif (! $user->isSuperAdmin()) {
            $query->where('requested_by', $user->id);
        }
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
