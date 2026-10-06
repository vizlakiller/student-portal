<?php

namespace App\Models;

use App\Support\Grading;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'student_no', 'name', 'email', 'phone', 'gender', 'date_of_birth',
    'address', 'programme_id', 'intake_year', 'semester', 'status',
])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    public const STATUSES = ['Active', 'Deferred', 'Graduated', 'Withdrawn'];

    public const GENDERS = ['Male', 'Female'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    /**
     * Apply the search box and filters from the students list.
     * Usage: Student::filter($request->only('search', 'programme', 'status'))
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('student_no', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['programme'] ?? null, fn (Builder $query, $id) => $query->where('programme_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status));
    }

    /**
     * Cumulative GPA across all results (null if no results yet).
     * Load "results.subject" first to avoid extra queries.
     */
    public function cgpa(): ?float
    {
        return Grading::gpa($this->results);
    }
}
