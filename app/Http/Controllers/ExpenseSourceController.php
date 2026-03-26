<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseSourceRequest;
use App\Http\Requests\UpdateFixedExpenseSourceRequest;
use App\Models\ExpenseSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseSourceController extends Controller
{
    public function store(StoreExpenseSourceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['type'] !== ExpenseSource::TYPE_FIXED) {
            $request->user()->expenseSources()->create([
                'type' => $validated['type'],
                'description' => $validated['description'],
            ]);

            return to_route('expense.entries.index')->with('status', 'Fonte de saida cadastrada com sucesso.');
        }

        DB::transaction(function () use ($request, $validated): void {
            $expenseSource = $request->user()->expenseSources()->create([
                'type' => $validated['type'],
                'description' => $validated['description'],
                'category_id' => $validated['category_id'],
                'monthly_amount' => $validated['monthly_amount'],
                'monthly_amount_started_at' => $validated['effective_from'],
            ]);

            $expenseSource->amountHistories()->create([
                'amount' => $validated['monthly_amount'],
                'effective_from' => $validated['effective_from'],
            ]);
        });

        return to_route('expense.entries.index')->with('status', 'Fonte de saida cadastrada com sucesso.');
    }

    public function update(UpdateFixedExpenseSourceRequest $request, int $expenseSourceId): RedirectResponse
    {
        $validated = $request->validated();

        $expenseSource = $request->user()->expenseSources()
            ->where('type', ExpenseSource::TYPE_FIXED)
            ->findOrFail($expenseSourceId);

        DB::transaction(function () use ($expenseSource, $validated): void {
            $expenseSource->update([
                'description' => $validated['description'],
                'category_id' => $validated['category_id'],
                'monthly_amount' => $validated['monthly_amount'],
                'monthly_amount_started_at' => $validated['effective_from'],
            ]);

            $expenseSource->amountHistories()->create([
                'amount' => $validated['monthly_amount'],
                'effective_from' => $validated['effective_from'],
            ]);
        });

        return to_route($this->resolveRedirectRoute($request))->with('status', 'Saida fixa atualizada com sucesso.');
    }

    private function resolveRedirectRoute(Request $request): string
    {
        return $request->string('redirect_to')->toString() === 'movement'
            ? 'movement.index'
            : 'expense.entries.index';
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
