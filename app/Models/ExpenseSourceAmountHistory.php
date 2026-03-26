<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseSourceAmountHistory extends Model
{
    protected $fillable = [
        'expense_source_id',
        'amount',
        'effective_from',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'effective_from' => 'date',
        ];
    }

    public function expenseSource(): BelongsTo
    {
        return $this->belongsTo(ExpenseSource::class);
    }
}
