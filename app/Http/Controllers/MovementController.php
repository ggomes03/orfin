<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovementExpenseRequest;
use App\Http\Requests\StoreIncomeEntryRequest;
use App\Models\ExpenseEntry;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSource;
use App\Models\IncomeEntry;
use App\Models\IncomeSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MovementController extends Controller
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

        $expenseSources = $request->user()->expenseSources()
            ->where('type', '!=', ExpenseSource::TYPE_FIXED)
            ->latest()
            ->get(['id', 'type', 'description', 'monthly_amount']);

        $fixedExpenseSources = $request->user()->expenseSources()
            ->where('type', ExpenseSource::TYPE_FIXED)
            ->with([
                'expenseCategory:id,name',
                'amountHistories' => fn ($query) => $query
                    ->latest('effective_from')
                    ->latest('id'),
            ])
            ->latest()
            ->get(['id', 'type', 'description', 'category_id', 'monthly_amount', 'monthly_amount_started_at']);

        $incomeEntries = $request->user()->incomeEntries()
            ->with('incomeSource:id,type,description')
            ->whereYear('entry_date', $exerciseYear)
            ->latest('entry_date')
            ->latest('id')
            ->get(['id', 'income_source_id', 'entry_type', 'description', 'amount', 'entry_date']);

        $expenseEntries = $request->user()->expenseEntries()
            ->with(['expenseSource:id,type,description', 'expenseCategory:id,name'])
            ->whereYear('entry_date', $exerciseYear)
            ->latest('entry_date')
            ->latest('id')
            ->get(['id', 'expense_source_id', 'entry_type', 'description', 'category_id', 'amount', 'entry_date']);

        $expenseCategoryOptions = ExpenseCategory::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ExpenseCategory $category) => [
                'value' => (string) $category->id,
                'label' => $category->name,
            ])
            ->values();

        return Inertia::render('movement/index', [
            'exerciseYear' => $exerciseYear,
            'incomeSources' => $incomeSources,
            'expenseSources' => $expenseSources,
            'fixedExpenseSources' => $fixedExpenseSources,
            'incomeEntries' => $incomeEntries,
            'expenseEntries' => $expenseEntries,
            'expenseCategoryOptions' => $expenseCategoryOptions,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function storeIncome(StoreIncomeEntryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['entry_mode'] === IncomeEntry::TYPE_SOURCE) {
            $incomeSource = $request->user()->incomeSources()->findOrFail($validated['income_source_id']);
            $entryAmount = $incomeSource->resolveAmountForDate($validated['entry_date']);

            $request->user()->incomeEntries()->create([
                'income_source_id' => $incomeSource->id,
                'entry_type' => IncomeEntry::TYPE_SOURCE,
                'description' => $incomeSource->description,
                'amount' => $entryAmount,
                'entry_date' => $validated['entry_date'],
            ]);

            return to_route('movement.index')->with('status', 'Entrada mensal da fonte cadastrada com sucesso.');
        }

        $request->user()->incomeEntries()->create([
            'entry_type' => IncomeEntry::TYPE_SIMPLE,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'entry_date' => $validated['entry_date'],
        ]);

        return to_route('movement.index')->with('status', 'Entrada simples cadastrada com sucesso.');
    }

    public function storeExpense(StoreMovementExpenseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['entry_mode'] === ExpenseSource::TYPE_FIXED) {
            DB::transaction(function () use ($request, $validated): void {
                $expenseSource = $request->user()->expenseSources()->create([
                    'type' => ExpenseSource::TYPE_FIXED,
                    'description' => $validated['description'],
                    'category_id' => $validated['category_id'],
                    'monthly_amount' => $validated['amount'],
                    'monthly_amount_started_at' => $validated['effective_from'],
                ]);

                $expenseSource->amountHistories()->create([
                    'amount' => $validated['amount'],
                    'effective_from' => $validated['effective_from'],
                ]);
            });

            return to_route('movement.index')->with('status', 'Saida fixa cadastrada com sucesso.');
        }

        if ($validated['entry_mode'] === ExpenseEntry::TYPE_SOURCE) {
            $expenseSource = $request->user()->expenseSources()->findOrFail($validated['expense_source_id']);

            $request->user()->expenseEntries()->create([
                'expense_source_id' => $expenseSource->id,
                'entry_type' => ExpenseEntry::TYPE_SOURCE,
                'description' => $validated['description'] ?: $expenseSource->description,
                'category_id' => $validated['category_id'],
                'amount' => $validated['amount'],
                'entry_date' => $validated['entry_date'],
            ]);

            return to_route('movement.index')->with('status', 'Saida mensal por cartao cadastrada com sucesso.');
        }

        $request->user()->expenseEntries()->create([
            'entry_type' => ExpenseEntry::TYPE_SIMPLE,
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'entry_date' => $validated['entry_date'],
        ]);

        return to_route('movement.index')->with('status', 'Saida simples cadastrada com sucesso.');
    }
}
