<?php

namespace App\Models;

use Database\Factories\ProgrammeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'level'])]
class Programme extends Model
{
    /** @use HasFactory<ProgrammeFactory> */
    use HasFactory;

    public const LEVELS = ['Certificate', 'Diploma', 'Bachelor'];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
