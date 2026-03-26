<?php

use App\Models\ExpenseEntry;
use App\Models\ExpenseCategory;
use App\Models\IncomeEntry;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('user can change selected exercise year in session', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('dashboard'))
        ->put(route('exercise-year.update'), [
            'exercise_year' => now()->subYear()->year,
        ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('exercise_year', now()->subYear()->year);
});

test('income and expense listing pages respect selected exercise year', function () {
    $user = User::factory()->create();
    $selectedYear = now()->subYear()->year;

    IncomeEntry::query()->create([
        'user_id' => $user->id,
        'income_source_id' => null,
        'entry_type' => IncomeEntry::TYPE_SIMPLE,
        'description' => 'Entrada ano selecionado',
        'amount' => 100,
        'entry_date' => "$selectedYear-02-10",
    ]);

    IncomeEntry::query()->create([
        'user_id' => $user->id,
        'income_source_id' => null,
        'entry_type' => IncomeEntry::TYPE_SIMPLE,
        'description' => 'Entrada ano atual',
        'amount' => 200,
        'entry_date' => now()->toDateString(),
    ]);

    ExpenseEntry::query()->create([
        'user_id' => $user->id,
        'expense_source_id' => null,
        'entry_type' => ExpenseEntry::TYPE_SIMPLE,
        'description' => 'Saida ano selecionado',
        'category_id' => ExpenseCategory::query()->where('code', ExpenseCategory::CODE_TRANSPORT)->value('id'),
        'amount' => 50,
        'entry_date' => "$selectedYear-03-15",
    ]);

    ExpenseEntry::query()->create([
        'user_id' => $user->id,
        'expense_source_id' => null,
        'entry_type' => ExpenseEntry::TYPE_SIMPLE,
        'description' => 'Saida ano atual',
        'category_id' => ExpenseCategory::query()->where('code', ExpenseCategory::CODE_FOOD)->value('id'),
        'amount' => 75,
        'entry_date' => now()->toDateString(),
    ]);

    $incomeResponse = $this->actingAs($user)
        ->withSession(['exercise_year' => $selectedYear])
        ->get(route('income.entries.index'));

    $incomeResponse->assertOk();
    $incomeResponse->assertInertia(fn ($page) => $page
        ->component('income/entries')
        ->where('exerciseYear', $selectedYear)
        ->where('incomeEntries.0.description', 'Entrada ano selecionado')
        ->has('incomeEntries', 1),
    );

    $expenseResponse = $this->actingAs($user)
        ->withSession(['exercise_year' => $selectedYear])
        ->get(route('expense.entries.index'));

    $expenseResponse->assertOk();
    $expenseResponse->assertInertia(fn ($page) => $page
        ->component('expense/entries')
        ->where('exerciseYear', $selectedYear)
        ->where('expenseEntries.0.description', 'Saida ano selecionado')
        ->has('expenseEntries', 1),
    );
});
