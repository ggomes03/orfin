<?php

namespace App\Application\UseCases;

use App\Models\BudgetAllocation;
use App\Models\ExpenseSource;
use App\Models\IncomeSource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetDashboardDataUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function execute(User $user, int $exerciseYear, ?int $selectedMonth): array
    {
        $monthExpression = $this->resolveMonthExpression();

        $manualIncomeEntriesQuery = $user->incomeEntries()
            ->whereYear('entry_date', $exerciseYear)
            ->where(function ($query) {
                $query
                    ->whereNull('income_source_id')
                    ->orWhereDoesntHave('incomeSource', fn ($incomeSourceQuery) => $incomeSourceQuery->where('type', IncomeSource::TYPE_SALARY));
            });

        $manualAnnualIncomeAmount = (float) (clone $manualIncomeEntriesQuery)->sum('amount');

        $manualIncomeByMonth = (clone $manualIncomeEntriesQuery)
            ->selectRaw($monthExpression.' as month_number, SUM(amount) as total_amount')
            ->groupByRaw($monthExpression)
            ->pluck('total_amount', 'month_number');

        $salarySources = $user->incomeSources()
            ->where('type', IncomeSource::TYPE_SALARY)
            ->with([
                'amountHistories' => fn ($query) => $query
                    ->whereDate('effective_from', '<=', sprintf('%d-12-31', $exerciseYear))
                    ->orderBy('effective_from')
                    ->orderBy('id'),
            ])
            ->get(['id', 'monthly_amount', 'monthly_amount_started_at']);

        $salaryProjectionByMonth = collect(range(1, 12))->mapWithKeys(
            fn (int $month) => [
                $month => $salarySources->sum(
                    fn (IncomeSource $source) => $this->resolveMonthlyProjectedAmount($source, $exerciseYear, $month)
                ),
            ]
        );

        $salaryAnnualProjectionAmount = (float) $salaryProjectionByMonth->sum();
        $annualIncomeAmount = $manualAnnualIncomeAmount + $salaryAnnualProjectionAmount;

        $manualExpenseEntriesQuery = $user->expenseEntries()
            ->whereYear('entry_date', $exerciseYear)
            ->where(function ($query) {
                $query
                    ->whereNull('expense_source_id')
                    ->orWhereDoesntHave('expenseSource', fn ($expenseSourceQuery) => $expenseSourceQuery->where('type', ExpenseSource::TYPE_FIXED));
            });

        $manualAnnualExpenseAmount = (float) (clone $manualExpenseEntriesQuery)->sum('amount');

        $manualExpenseByMonth = (clone $manualExpenseEntriesQuery)
            ->selectRaw($monthExpression.' as month_number, SUM(amount) as total_amount')
            ->groupByRaw($monthExpression)
            ->pluck('total_amount', 'month_number');

        $fixedExpenseSources = $user->expenseSources()
            ->where('type', ExpenseSource::TYPE_FIXED)
            ->with([
                'amountHistories' => fn ($query) => $query
                    ->whereDate('effective_from', '<=', sprintf('%d-12-31', $exerciseYear))
                    ->orderBy('effective_from')
                    ->orderBy('id'),
            ])
            ->get(['id', 'monthly_amount', 'monthly_amount_started_at']);

        $fixedExpenseProjectionByMonth = collect(range(1, 12))->mapWithKeys(
            fn (int $month) => [
                $month => $fixedExpenseSources->sum(
                    fn (ExpenseSource $source) => $this->resolveMonthlyProjectedAmount($source, $exerciseYear, $month)
                ),
            ]
        );

        $fixedExpenseAnnualProjectionAmount = (float) $fixedExpenseProjectionByMonth->sum();
        $annualExpenseAmount = $manualAnnualExpenseAmount + $fixedExpenseAnnualProjectionAmount;
        $annualBalanceAmount = $annualIncomeAmount - $annualExpenseAmount;

        $monthlyBalanceRows = collect(range(1, 12))->map(
            function (int $month) use ($manualIncomeByMonth, $salaryProjectionByMonth, $manualExpenseByMonth, $fixedExpenseProjectionByMonth): array {
                $monthlyIncomeAmount = (float) ($manualIncomeByMonth[$month] ?? 0) + (float) ($salaryProjectionByMonth[$month] ?? 0);
                $monthlyExpenseAmount = (float) ($manualExpenseByMonth[$month] ?? 0) + (float) ($fixedExpenseProjectionByMonth[$month] ?? 0);

                return [
                    'month' => $month,
                    'incomeAmount' => $monthlyIncomeAmount,
                    'expenseAmount' => $monthlyExpenseAmount,
                    'balanceAmount' => $monthlyIncomeAmount - $monthlyExpenseAmount,
                ];
            }
        )->values();

        $savedPercentages = $user->budgetAllocations()
            ->get(['category', 'percentage'])
            ->pluck('percentage', 'category');

        return [
            'exerciseYear' => $exerciseYear,
            'annualIncomeAmount' => (float) $annualIncomeAmount,
            'annualExpenseAmount' => (float) $annualExpenseAmount,
            'annualBalanceAmount' => (float) $annualBalanceAmount,
            'monthlyBalanceRows' => $monthlyBalanceRows,
            'annualBudgetDetail' => $this->buildBudgetDetail($annualIncomeAmount, $savedPercentages),
            'selectedMonth' => $selectedMonth,
            'selectedMonthDetail' => $this->buildSelectedMonthDetail($selectedMonth, $monthlyBalanceRows, $savedPercentages),
        ];
    }

    private function resolveMonthlyProjectedAmount(IncomeSource|ExpenseSource $source, int $exerciseYear, int $month): float
    {
        $referenceDate = CarbonImmutable::create($exerciseYear, $month, 1)->toDateString();

        $effectiveHistory = $source->amountHistories
            ->filter(fn ($history) => $history->effective_from !== null && $history->effective_from->toDateString() <= $referenceDate)
            ->reduce(function ($latest, $history) {
                if ($latest === null) {
                    return $history;
                }

                $historyDate = $history->effective_from->toDateString();
                $latestDate = $latest->effective_from->toDateString();

                if ($historyDate > $latestDate) {
                    return $history;
                }

                if ($historyDate === $latestDate && $history->id > $latest->id) {
                    return $history;
                }

                return $latest;
            });

        if ($effectiveHistory !== null) {
            return (float) $effectiveHistory->amount;
        }

        if ($source->monthly_amount_started_at !== null && $source->monthly_amount_started_at->toDateString() > $referenceDate) {
            return 0;
        }

        return (float) ($source->monthly_amount ?? 0);
    }

    private function resolveMonthExpression(): string
    {
        $databaseDriver = DB::connection()->getDriverName();

        return match ($databaseDriver) {
            'pgsql' => 'EXTRACT(MONTH FROM entry_date)',
            'sqlite' => "CAST(STRFTIME('%m', entry_date) AS INTEGER)",
            default => 'MONTH(entry_date)',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSelectedMonthDetail(?int $selectedMonth, Collection $monthlyBalanceRows, Collection $savedPercentages): ?array
    {
        if ($selectedMonth === null) {
            return null;
        }

        $selectedMonthRow = $monthlyBalanceRows->firstWhere('month', $selectedMonth);

        if ($selectedMonthRow === null) {
            return null;
        }

        $monthlyIncomeAmount = (float) ($selectedMonthRow['incomeAmount'] ?? 0);
        $budgetDetail = $this->buildBudgetDetail($monthlyIncomeAmount, $savedPercentages);

        return [
            'month' => $selectedMonth,
            ...$budgetDetail,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBudgetDetail(float $referenceIncomeAmount, Collection $savedPercentages): array
    {
        $items = collect(BudgetAllocation::categories())
            ->map(function (string $category) use ($savedPercentages, $referenceIncomeAmount): array {
                $percentage = (int) ($savedPercentages[$category] ?? 0);

                return [
                    'key' => $category,
                    'label' => BudgetAllocation::labels()[$category],
                    'percentage' => $percentage,
                    'amount' => (float) round(($referenceIncomeAmount * $percentage) / 100, 2),
                ];
            })
            ->values();

        $allocatedPercentage = (int) $items->sum('percentage');
        $allocatedAmount = (float) $items->sum('amount');
        $unallocatedPercentage = max(0, 100 - $allocatedPercentage);
        $unallocatedAmount = (float) max(0, round($referenceIncomeAmount - $allocatedAmount, 2));

        return [
            'referenceIncomeAmount' => $referenceIncomeAmount,
            'items' => $items,
            'unallocatedPercentage' => $unallocatedPercentage,
            'unallocatedAmount' => $unallocatedAmount,
        ];
    }
}
