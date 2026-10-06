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

    /** Personal and contact details (admin staff can edit these). */
    public const CONTACT_FIELDS = ['name', 'email', 'phone', 'gender', 'date_of_birth', 'address'];

    /** Enrolment details (only the registrar can edit these). */
    public const ENROLMENT_FIELDS = ['student_no', 'programme_id', 'intake_year', 'semester', 'status'];

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

    /** The student's own login account. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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
     * Adds charges_sum_amount and payments_sum_amount to each student,
     * so balance() needs no extra queries.
     */
    public function scopeWithBalance(Builder $query): void
    {
        $query->withSum('charges', 'amount')->withSum('payments', 'amount');
    }

    /**
     * Cumulative GPA across all marked results (null if none yet).
     * Load "results.subject" first to avoid extra queries.
     */
    public function cgpa(): ?float
    {
        return Grading::gpa($this->results);
    }

    /**
     * Amount still owed: total charges minus total payments.
     * A negative number means the student has paid in advance (credit).
     */
    public function balance(): float
    {
        // Use the totals from withBalance() when loaded, otherwise ask the database.
        $charged = array_key_exists('charges_sum_amount', $this->attributes) ? $this->charges_sum_amount : $this->charges()->sum('amount');
        $paid = array_key_exists('payments_sum_amount', $this->attributes) ? $this->payments_sum_amount : $this->payments()->sum('amount');

        return round((float) $charged - (float) $paid, 2);
    }
}
