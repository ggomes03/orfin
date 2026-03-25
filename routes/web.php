<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BudgetAllocationController;
use App\Http\Controllers\ExerciseYearController;
use App\Http\Controllers\ExpenseEntryController;
use App\Http\Controllers\ExpenseSourceController;
use App\Http\Controllers\IncomeEntryController;
use App\Http\Controllers\IncomeSourceController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::put('financeiro/exercicio', [ExerciseYearController::class, 'update'])->name('exercise-year.update');
    Route::get('financeiro/controle-orcamento', [BudgetAllocationController::class, 'index'])->name('budget.control.index');
    Route::put('financeiro/controle-orcamento', [BudgetAllocationController::class, 'update'])->name('budget.control.update');

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
