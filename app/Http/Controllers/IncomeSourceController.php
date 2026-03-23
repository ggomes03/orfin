<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeSourceRequest;
use Illuminate\Http\RedirectResponse;

class IncomeSourceController extends Controller
{
    public function store(StoreIncomeSourceRequest $request): RedirectResponse
    {
        $request->user()->incomeSources()->create($request->validated());

        return to_route('income.entries.index')->with('status', 'Fonte de renda cadastrada com sucesso.');
    }
}
