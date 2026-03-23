<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseSourceRequest;
use App\Http\Requests\UpdateFixedExpenseSourceRequest;
use App\Models\ExpenseSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseSourceController extends Controller
{
    public function store(StoreExpenseSourceRequest $request): RedirectResponse
    {
        $request->user()->expenseSources()->create($request->validated());

        return to_route('expense.entries.index')->with('status', 'Fonte de saida cadastrada com sucesso.');
    }

    public function update(UpdateFixedExpenseSourceRequest $request, int $expenseSourceId): RedirectResponse
    {
        $expenseSource = $request->user()->expenseSources()
            ->where('type', ExpenseSource::TYPE_FIXED)
            ->findOrFail($expenseSourceId);

        $expenseSource->update($request->validated());

        return to_route('expense.entries.index')->with('status', 'Saida fixa atualizada com sucesso.');
    }

    public function destroy(Request $request, int $expenseSourceId): RedirectResponse
    {
        $expenseSource = $request->user()->expenseSources()
            ->where('type', ExpenseSource::TYPE_FIXED)
            ->findOrFail($expenseSourceId);

        $expenseSource->delete();

        return to_route('expense.entries.index')->with('status', 'Saida fixa removida com sucesso.');
    }
}
