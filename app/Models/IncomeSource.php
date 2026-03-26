<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class IncomeSource extends Model
{
    public const TYPE_SALARY = 'salary';

    public const TYPE_COMPLEMENT = 'complement';

    public const TYPE_SERVICE_PROVISION = 'service_provision';

    public const TYPE_SCHOLARSHIP = 'scholarship';

    protected $fillable = [
        'user_id',
        'type',
        'description',
        'monthly_amount',
        'monthly_amount_started_at',
    ];

    protected function casts(): array
    {
        return [
            'monthly_amount' => 'decimal:2',
            'monthly_amount_started_at' => 'date',
        ];
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_SALARY => 'Salario',
            self::TYPE_COMPLEMENT => 'Complemento',
            self::TYPE_SERVICE_PROVISION => 'Prestacao de servico',
            self::TYPE_SCHOLARSHIP => 'Bolsa',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(IncomeEntry::class);
    }

    public function amountHistories(): HasMany
    {
        return $this->hasMany(IncomeSourceAmountHistory::class);
    }

    public function resolveAmountForDate(string $entryDate): float
    {
        $effectiveDate = CarbonImmutable::parse($entryDate)->toDateString();

        $historyAmount = $this->amountHistories()
            ->whereDate('effective_from', '<=', $effectiveDate)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->value('amount');

        if ($historyAmount !== null) {
            return (float) $historyAmount;
        }

        if ($this->monthly_amount_started_at !== null && $effectiveDate < $this->monthly_amount_started_at->toDateString()) {
            return 0;
        }

        return (float) ($this->monthly_amount ?? 0);
    }
}
