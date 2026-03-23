<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseSource extends Model
{
    public const TYPE_FIXED = 'fixed';

    public const TYPE_CREDIT_CARD = 'credit_card';

    protected $fillable = [
        'user_id',
        'type',
        'description',
        'monthly_amount',
    ];

    protected function casts(): array
    {
        return [
            'monthly_amount' => 'decimal:2',
        ];
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_FIXED => 'Fixa',
            self::TYPE_CREDIT_CARD => 'Cartao de credito',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ExpenseEntry::class);
    }
}
