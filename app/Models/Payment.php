<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Money received from a student. Each payment gets its own receipt number.
 */
#[Fillable(['receipt_no', 'amount', 'method', 'reference', 'paid_at', 'notes', 'received_by'])]
class Payment extends Model
{
    public const METHODS = ['Cash', 'Online transfer', 'Card', 'Cheque'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Next receipt number for this year, e.g. RCP-2026-00013.
     * Call it inside a database transaction.
     */
    public static function nextReceiptNo(): string
    {
        $prefix = 'RCP-'.now()->year.'-';

        $last = DB::table('payments')
            ->where('receipt_no', 'like', $prefix.'%')
            ->lockForUpdate()
            ->max('receipt_no');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
