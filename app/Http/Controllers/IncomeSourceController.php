<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeSourceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeSourceController extends Controller
{
    public function store(StoreIncomeSourceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated): void {
            $incomeSource = $request->user()->incomeSources()
                ->where('type', $validated['type'])
                ->where('description', $validated['description'])
                ->first();

            if ($incomeSource === null) {
                $incomeSource = $request->user()->incomeSources()->create([
                    'type' => $validated['type'],
                    'description' => $validated['description'],
                    'monthly_amount' => $validated['monthly_amount'],
                    'monthly_amount_started_at' => $validated['effective_from'],
                ]);
            } else {
                $incomeSource->update([
                    'monthly_amount' => $validated['monthly_amount'],
                    'monthly_amount_started_at' => $validated['effective_from'],
                ]);
            }

            $incomeSource->amountHistories()->create([
                'amount' => $validated['monthly_amount'],
                'effective_from' => $validated['effective_from'],
            ]);
        });

        return to_route($this->resolveRedirectRoute($request))->with('status', 'Fonte de renda salva com sucesso.');
    }

    private function resolveRedirectRoute(Request $request): string
    {
        return $request->string('redirect_to')->toString() === 'movement'
            ? 'movement.index'
            : 'income.entries.index';
    }
}
