<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeEntryRequest;
use App\Http\Requests\UpdateIncomeEntryRequest;
use App\Models\IncomeEntry;
use App\Models\IncomeSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncomeEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $currentYear = now()->year;
        $exerciseYear = (int) $request->session()->get('exercise_year', $currentYear);

        if ($exerciseYear < 1970 || $exerciseYear > $currentYear) {
            $exerciseYear = $currentYear;
        }

        $incomeSources = $request->user()->incomeSources()
            ->where('type', '!=', IncomeSource::TYPE_SALARY)
            ->latest()
            ->get(['id', 'type', 'description', 'monthly_amount']);

        $incomeEntries = $request->user()->incomeEntries()
            ->with('incomeSource:id,type,description')
            ->whereYear('entry_date', $exerciseYear)
            ->latest('entry_date')
            ->latest('id')
            ->get(['id', 'income_source_id', 'entry_type', 'description', 'amount', 'entry_date']);

        $sourceTypeOptions = collect(IncomeSource::typeLabels())
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values();

        return Inertia::render('income/entries', [
            'incomeSources' => $incomeSources,
            'incomeEntries' => $incomeEntries,
            'sourceTypeOptions' => $sourceTypeOptions,
            'exerciseYear' => $exerciseYear,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(StoreIncomeEntryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['entry_mode'] === IncomeEntry::TYPE_SOURCE) {
            $incomeSource = $request->user()->incomeSources()->findOrFail($validated['income_source_id']);

            $request->user()->incomeEntries()->create([
                'income_source_id' => $incomeSource->id,
                'entry_type' => IncomeEntry::TYPE_SOURCE,
                'description' => $incomeSource->description,
                'amount' => $incomeSource->monthly_amount,
                'entry_date' => $validated['entry_date'],
            ]);

            return to_route('income.entries.index')->with('status', 'Entrada mensal da fonte cadastrada com sucesso.');
        }

        $request->user()->incomeEntries()->create([
            'entry_type' => IncomeEntry::TYPE_SIMPLE,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'entry_date' => $validated['entry_date'],
        ]);

        return to_route('income.entries.index')->with('status', 'Entrada simples cadastrada com sucesso.');
    }

    public function update(UpdateIncomeEntryRequest $request, int $incomeEntryId): RedirectResponse
    {
        $incomeEntry = $request->user()->incomeEntries()->findOrFail($incomeEntryId);

        $incomeEntry->update($request->validated());

        return to_route('income.entries.index')->with('status', 'Entrada atualizada com sucesso.');
    }
}
