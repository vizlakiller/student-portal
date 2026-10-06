<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'building', 'type', 'capacity'])]
class Classroom extends Model
{
    use HasFactory;

    public const TYPES = ['Lecture room', 'Tutorial room', 'Computer lab', 'Science lab', 'Workshop', 'Hall'];

    public function slots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class);
    }

    public function label(): string
    {
        return "{$this->code} {$this->name}";
    }
}
