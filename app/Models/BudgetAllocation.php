<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetAllocation extends Model
{
    public const CATEGORY_FINANCIAL_FREEDOM = 'financial_freedom';

    public const CATEGORY_FIXED_COSTS = 'fixed_costs';

    public const CATEGORY_COMFORT = 'comfort';

    public const CATEGORY_GOALS = 'goals';

    public const CATEGORY_PLEASURES = 'pleasures';

    public const CATEGORY_KNOWLEDGE = 'knowledge';

    protected $fillable = [
        'user_id',
        'category',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'integer',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_FINANCIAL_FREEDOM,
            self::CATEGORY_FIXED_COSTS,
            self::CATEGORY_COMFORT,
            self::CATEGORY_GOALS,
            self::CATEGORY_PLEASURES,
            self::CATEGORY_KNOWLEDGE,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::CATEGORY_FINANCIAL_FREEDOM => 'Liberdade financeira',
            self::CATEGORY_FIXED_COSTS => 'Custos fixos',
            self::CATEGORY_COMFORT => 'Conforto',
            self::CATEGORY_GOALS => 'Metas',
            self::CATEGORY_PLEASURES => 'Prazeres',
            self::CATEGORY_KNOWLEDGE => 'Conhecimento',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
