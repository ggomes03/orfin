<?php

namespace App\Models;

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
}
