<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * An academic term, e.g. "September 2026". Sessions and the timetable belong to a term.
 */
#[Fillable(['name', 'starts_on', 'ends_on', 'is_current'])]
class Term extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    /** The term marked as current by the super admin (null if none yet). */
    public static function current(): ?self
    {
        return self::where('is_current', true)->first();
    }

    /** Make this the only current term. */
    public function makeCurrent(): void
    {
        DB::transaction(function () {
            self::where('id', '!=', $this->id)->update(['is_current' => false]);
            $this->update(['is_current' => true]);
        });
    }
}
