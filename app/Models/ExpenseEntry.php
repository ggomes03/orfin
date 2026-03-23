<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseEntry extends Model
{
    public const TYPE_SOURCE = 'source';

    public const TYPE_SIMPLE = 'simple';

    protected $fillable = [
        'user_id',
        'expense_source_id',
        'entry_type',
        'description',
        'amount',
        'entry_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'entry_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expenseSource(): BelongsTo
    {
        return $this->belongsTo(ExpenseSource::class);
    }
}
