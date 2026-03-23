<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseEntryRequest;
use App\Models\ExpenseEntry;
use App\Models\ExpenseSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $fixedExpenseSources = $request->user()->expenseSources()
            ->where('type', ExpenseSource::TYPE_FIXED)
            ->latest()
            ->get(['id', 'type', 'description', 'monthly_amount']);

        $expenseSources = $request->user()->expenseSources()
            ->where('type', '!=', ExpenseSource::TYPE_FIXED)
            ->latest()
            ->get(['id', 'type', 'description', 'monthly_amount']);

        $expenseEntries = $request->user()->expenseEntries()
            ->with('expenseSource:id,type,description')
            ->latest('entry_date')
            ->latest('id')
            ->get(['id', 'expense_source_id', 'entry_type', 'description', 'amount', 'entry_date']);

        $sourceTypeOptions = collect(ExpenseSource::typeLabels())
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values();

        return Inertia::render('expense/entries', [
            'fixedExpenseSources' => $fixedExpenseSources,
            'expenseSources' => $expenseSources,
            'expenseEntries' => $expenseEntries,
            'sourceTypeOptions' => $sourceTypeOptions,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(StoreExpenseEntryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['entry_mode'] === ExpenseEntry::TYPE_SOURCE) {
            $expenseSource = $request->user()->expenseSources()->findOrFail($validated['expense_source_id']);

            $request->user()->expenseEntries()->create([
                'expense_source_id' => $expenseSource->id,
                'entry_type' => ExpenseEntry::TYPE_SOURCE,
                'description' => $validated['description'] ?: $expenseSource->description,
                'amount' => $validated['amount'],
                'entry_date' => $validated['entry_date'],
            ]);

            return to_route('expense.entries.index')->with('status', 'Saida mensal por cartao cadastrada com sucesso.');
        }

        $request->user()->expenseEntries()->create([
            'entry_type' => ExpenseEntry::TYPE_SIMPLE,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'entry_date' => $validated['entry_date'],
        ]);

        return to_route('expense.entries.index')->with('status', 'Saida simples cadastrada com sucesso.');
    }
}
