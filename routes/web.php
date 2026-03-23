<?php

use App\Http\Controllers\ExpenseEntryController;
use App\Http\Controllers\ExpenseSourceController;
use App\Http\Controllers\IncomeEntryController;
use App\Http\Controllers\IncomeSourceController;
use App\Models\ExpenseSource;
use App\Models\IncomeSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        $exerciseYear = now()->year;
        $databaseDriver = DB::connection()->getDriverName();
        $monthExpression = match ($databaseDriver) {
            'pgsql' => 'EXTRACT(MONTH FROM entry_date)',
            'sqlite' => "CAST(STRFTIME('%m', entry_date) AS INTEGER)",
            default => 'MONTH(entry_date)',
        };

        $manualIncomeEntriesQuery = $request->user()->incomeEntries()
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

        $salaryMonthlyProjectionAmount = (float) $request->user()->incomeSources()
            ->where('type', IncomeSource::TYPE_SALARY)
            ->sum('monthly_amount');

        $salaryAnnualProjectionAmount = $salaryMonthlyProjectionAmount * 12;

        $annualIncomeAmount = $manualAnnualIncomeAmount + $salaryAnnualProjectionAmount;

        $manualExpenseEntriesQuery = $request->user()->expenseEntries()
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

        $fixedExpenseMonthlyProjectionAmount = (float) $request->user()->expenseSources()
            ->where('type', ExpenseSource::TYPE_FIXED)
            ->sum('monthly_amount');

        $fixedExpenseAnnualProjectionAmount = $fixedExpenseMonthlyProjectionAmount * 12;

        $annualExpenseAmount = $manualAnnualExpenseAmount + $fixedExpenseAnnualProjectionAmount;
        $annualBalanceAmount = $annualIncomeAmount - $annualExpenseAmount;

        $monthlyBalanceRows = collect(range(1, 12))->map(function (int $month) use ($manualIncomeByMonth, $salaryMonthlyProjectionAmount, $manualExpenseByMonth, $fixedExpenseMonthlyProjectionAmount) {
            $monthlyIncomeAmount = (float) ($manualIncomeByMonth[$month] ?? 0) + $salaryMonthlyProjectionAmount;
            $monthlyExpenseAmount = (float) ($manualExpenseByMonth[$month] ?? 0) + $fixedExpenseMonthlyProjectionAmount;

            return [
                'month' => $month,
                'incomeAmount' => $monthlyIncomeAmount,
                'expenseAmount' => $monthlyExpenseAmount,
                'balanceAmount' => $monthlyIncomeAmount - $monthlyExpenseAmount,
            ];
        })->values();

        return Inertia::render('dashboard', [
            'exerciseYear' => $exerciseYear,
            'annualIncomeAmount' => (float) $annualIncomeAmount,
            'annualExpenseAmount' => (float) $annualExpenseAmount,
            'annualBalanceAmount' => (float) $annualBalanceAmount,
            'monthlyBalanceRows' => $monthlyBalanceRows,
        ]);
    })->name('dashboard');

    Route::get('financeiro/entradas', [IncomeEntryController::class, 'index'])->name('income.entries.index');
    Route::post('financeiro/entradas', [IncomeEntryController::class, 'store'])->name('income.entries.store');
    Route::post('financeiro/fontes-renda', [IncomeSourceController::class, 'store'])->name('income.sources.store');

    Route::get('financeiro/saidas', [ExpenseEntryController::class, 'index'])->name('expense.entries.index');
    Route::post('financeiro/saidas', [ExpenseEntryController::class, 'store'])->name('expense.entries.store');
    Route::post('financeiro/fontes-saida', [ExpenseSourceController::class, 'store'])->name('expense.sources.store');
    Route::patch('financeiro/fontes-saida-fixas/{expenseSourceId}', [ExpenseSourceController::class, 'update'])->name('expense.sources.update');
    Route::delete('financeiro/fontes-saida-fixas/{expenseSourceId}', [ExpenseSourceController::class, 'destroy'])->name('expense.sources.destroy');
});

require __DIR__.'/settings.php';
