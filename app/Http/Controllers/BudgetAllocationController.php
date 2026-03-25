<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBudgetAllocationRequest;
use App\Models\BudgetAllocation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetAllocationController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $savedPercentages = $user->budgetAllocations()
            ->get(['category', 'percentage'])
            ->pluck('percentage', 'category');

        $items = collect(BudgetAllocation::categories())
            ->map(function (string $category) use ($savedPercentages): array {
                return [
                    'key' => $category,
                    'label' => BudgetAllocation::labels()[$category],
                    'percentage' => (int) ($savedPercentages[$category] ?? 0),
                ];
            })
            ->values();

        $totalPercentage = (int) $items->sum('percentage');

        return Inertia::render('budget/control', [
            'items' => $items,
            'totalPercentage' => $totalPercentage,
            'remainingPercentage' => max(0, 100 - $totalPercentage),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(UpdateBudgetAllocationRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $rows = collect(BudgetAllocation::categories())
            ->map(fn (string $category): array => [
                'user_id' => $user->id,
                'category' => $category,
                'percentage' => (int) $request->integer($category),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        BudgetAllocation::query()->upsert(
            $rows,
            ['user_id', 'category'],
            ['percentage', 'updated_at'],
        );

        return to_route('budget.control.index')->with('status', 'Controle de orcamento atualizado com sucesso.');
    }
}
