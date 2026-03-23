<?php

use App\Http\Controllers\IncomeEntryController;
use App\Http\Controllers\IncomeSourceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        $exerciseYear = now()->year;
        $annualIncomeAmount = $request->user()->incomeEntries()
            ->whereYear('entry_date', $exerciseYear)
            ->sum('amount');

        return Inertia::render('dashboard', [
            'exerciseYear' => $exerciseYear,
            'annualIncomeAmount' => (float) $annualIncomeAmount,
        ]);
    })->name('dashboard');

    Route::get('financeiro/entradas', [IncomeEntryController::class, 'index'])->name('income.entries.index');
    Route::post('financeiro/entradas', [IncomeEntryController::class, 'store'])->name('income.entries.store');
    Route::post('financeiro/fontes-renda', [IncomeSourceController::class, 'store'])->name('income.sources.store');
});

require __DIR__.'/settings.php';
