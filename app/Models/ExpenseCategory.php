<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    public const CODE_PERSONAL_EXPENSES = 'personal_expenses';

    public const CODE_TRANSPORT = 'transport';

    public const CODE_HOUSING = 'housing';

    public const CODE_LEISURE = 'leisure';

    public const CODE_HEALTH = 'health';

    public const CODE_EDUCATION = 'education';

    public const CODE_OTHER = 'other';

    // Legacy alias to avoid breaking older references/tests.
    public const CODE_FOOD = self::CODE_PERSONAL_EXPENSES;

    protected $fillable = [
        'code',
        'name',
    ];

    /**
     * @return array<string, string>
     */
    public static function defaultDefinitions(): array
    {
        return [
            self::CODE_HOUSING => 'Habitacao',
            self::CODE_HEALTH => 'Saude',
            self::CODE_TRANSPORT => 'Transporte',
            self::CODE_PERSONAL_EXPENSES => 'Despesas Pessoais',
            self::CODE_EDUCATION => 'Educacao',
            self::CODE_LEISURE => 'Lazer',
            self::CODE_OTHER => 'Outros',
        ];
    }

    public function expenseEntries(): HasMany
    {
        return $this->hasMany(ExpenseEntry::class, 'category_id');
    }
}
