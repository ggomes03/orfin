<?php

use App\Http\Controllers\ExpenseEntryController;
use App\Http\Controllers\ExpenseSourceController;
use App\Http\Controllers\IncomeEntryController;
use App\Http\Controllers\IncomeSourceController;
use App\Models\ExpenseSource;
use App\Models\IncomeSource;
use Carbon\Carbon;
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
        $selectedMonth = (int) $request->integer('month');
        $selectedMonth = ($selectedMonth >= 1 && $selectedMonth <= 12) ? $selectedMonth : null;
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

        $selectedMonthDetail = null;

        if ($selectedMonth !== null) {
            $startDate = Carbon::create($exerciseYear, $selectedMonth, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::create($exerciseYear, $selectedMonth, 1)->endOfMonth()->toDateString();

            $monthlyIncomeItems = $request->user()->incomeEntries()
                ->with('incomeSource:id,type')
                ->whereBetween('entry_date', [$startDate, $endDate])
                ->where(function ($query) {
                    $query
                        ->whereNull('income_source_id')
                        ->orWhereDoesntHave('incomeSource', fn ($incomeSourceQuery) => $incomeSourceQuery->where('type', IncomeSource::TYPE_SALARY));
                })
                ->latest('entry_date')
                ->latest('id')
                ->get(['id', 'description', 'amount', 'entry_date', 'income_source_id'])
                ->map(function ($entry) {
                    $typeLabel = $entry->incomeSource?->type ? (IncomeSource::typeLabels()[$entry->incomeSource->type] ?? $entry->incomeSource->type) : null;

                    return [
                        'id' => $entry->id,
                        'description' => $entry->description,
                        'amount' => (float) $entry->amount,
                        'entryDate' => $entry->entry_date?->toDateString() ?? (string) $entry->entry_date,
                        'typeLabel' => $typeLabel,
                    ];
                })
                ->values();

            $salaryProjectionItems = $request->user()->incomeSources()
                ->where('type', IncomeSource::TYPE_SALARY)
                ->get(['id', 'description', 'monthly_amount'])
                ->map(function ($source) use ($startDate) {
                    return [
                        'id' => -1 * (int) $source->id,
                        'description' => $source->description,
                        'amount' => (float) $source->monthly_amount,
                        'entryDate' => $startDate,
                        'typeLabel' => 'Salario (projecao mensal)',
                    ];
                })
                ->values();

            $monthlyIncomeItems = $monthlyIncomeItems
                ->concat($salaryProjectionItems)
                ->values();

            $monthlyExpenseItems = $request->user()->expenseEntries()
                ->with('expenseSource:id,type')
                ->whereBetween('entry_date', [$startDate, $endDate])
                ->where(function ($query) {
                    $query
                        ->whereNull('expense_source_id')
                        ->orWhereDoesntHave('expenseSource', fn ($expenseSourceQuery) => $expenseSourceQuery->where('type', ExpenseSource::TYPE_FIXED));
                })
                ->latest('entry_date')
                ->latest('id')
                ->get(['id', 'description', 'amount', 'entry_date', 'expense_source_id'])
                ->map(function ($entry) {
                    $typeLabel = $entry->expenseSource?->type ? (ExpenseSource::typeLabels()[$entry->expenseSource->type] ?? $entry->expenseSource->type) : null;

                    return [
                        'id' => $entry->id,
                        'description' => $entry->description,
                        'amount' => (float) $entry->amount,
                        'entryDate' => $entry->entry_date?->toDateString() ?? (string) $entry->entry_date,
                        'typeLabel' => $typeLabel,
                    ];
                })
                ->values();

            $fixedExpenseProjectionItems = $request->user()->expenseSources()
                ->where('type', ExpenseSource::TYPE_FIXED)
                ->get(['id', 'description', 'monthly_amount'])
                ->map(function ($source) use ($startDate) {
                    return [
                        'id' => -1 * (int) $source->id,
                        'description' => $source->description,
                        'amount' => (float) $source->monthly_amount,
                        'entryDate' => $startDate,
                        'typeLabel' => 'Conta fixa (projecao mensal)',
                    ];
                })
                ->values();

            $monthlyExpenseItems = $monthlyExpenseItems
                ->concat($fixedExpenseProjectionItems)
                ->values();

            $selectedMonthIncomeTotal = (float) $monthlyIncomeItems->sum('amount');
            $selectedMonthExpenseTotal = (float) $monthlyExpenseItems->sum('amount');

            $selectedMonthDetail = [
                'month' => $selectedMonth,
                'incomeItems' => $monthlyIncomeItems,
                'expenseItems' => $monthlyExpenseItems,
                'incomeTotalAmount' => $selectedMonthIncomeTotal,
                'expenseTotalAmount' => $selectedMonthExpenseTotal,
                'balanceAmount' => $selectedMonthIncomeTotal - $selectedMonthExpenseTotal,
            ];
        }

        return Inertia::render('dashboard', [
            'exerciseYear' => $exerciseYear,
            'annualIncomeAmount' => (float) $annualIncomeAmount,
            'annualExpenseAmount' => (float) $annualExpenseAmount,
            'annualBalanceAmount' => (float) $annualBalanceAmount,
            'monthlyBalanceRows' => $monthlyBalanceRows,
            'selectedMonth' => $selectedMonth,
            'selectedMonthDetail' => $selectedMonthDetail,
        ]);
    })->name('dashboard');

    Route::get('financeiro/entradas', [IncomeEntryController::class, 'index'])->name('income.entries.index');
    Route::post('financeiro/entradas', [IncomeEntryController::class, 'store'])->name('income.entries.store');
    Route::patch('financeiro/entradas/{incomeEntryId}', [IncomeEntryController::class, 'update'])->name('income.entries.update');
    Route::post('financeiro/fontes-renda', [IncomeSourceController::class, 'store'])->name('income.sources.store');

    Route::get('financeiro/saidas', [ExpenseEntryController::class, 'index'])->name('expense.entries.index');
    Route::post('financeiro/saidas', [ExpenseEntryController::class, 'store'])->name('expense.entries.store');
    Route::patch('financeiro/saidas-avulsas/{expenseEntryId}', [ExpenseEntryController::class, 'update'])->name('expense.entries.update');
    Route::delete('financeiro/saidas-avulsas/{expenseEntryId}', [ExpenseEntryController::class, 'destroy'])->name('expense.entries.destroy');
    Route::patch('financeiro/saidas-fonte/{expenseEntryId}', [ExpenseEntryController::class, 'updateSource'])->name('expense.entries.source.update');
    Route::delete('financeiro/saidas-fonte/{expenseEntryId}', [ExpenseEntryController::class, 'destroySource'])->name('expense.entries.source.destroy');
    Route::post('financeiro/fontes-saida', [ExpenseSourceController::class, 'store'])->name('expense.sources.store');
    Route::patch('financeiro/fontes-saida-fixas/{expenseSourceId}', [ExpenseSourceController::class, 'update'])->name('expense.sources.update');
    Route::delete('financeiro/fontes-saida-fixas/{expenseSourceId}', [ExpenseSourceController::class, 'destroy'])->name('expense.sources.destroy');
});

require __DIR__.'/settings.php';
