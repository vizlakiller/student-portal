<?php

namespace App\Models;

use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'credit_hours', 'department_id'])]
class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** Lecturers who teach this subject (a subject can have several). */
    public function lecturers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subject_lecturer')->orderBy('name');
    }

    /** Sessions (groups) of this subject, in every term. */
    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }
}
