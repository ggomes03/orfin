<?php

use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\ExpenseSource;
use App\Models\IncomeEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected from movement page', function () {
    $response = $this->get(route('movement.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view movement page', function () {
    $user = User::factory()->create();

    ExpenseSource::query()->create([
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_FIXED,
        'description' => 'Internet',
        'category_id' => ExpenseCategory::query()->where('code', ExpenseCategory::CODE_HOUSING)->value('id'),
        'monthly_amount' => '120.00',
        'monthly_amount_started_at' => '2026-03-01',
    ]);

    $response = $this->actingAs($user)->get(route('movement.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('movement/index')
        ->where('exerciseYear', now()->year)
        ->has('incomeEntries')
        ->has('expenseEntries')
        ->has('fixedExpenseSources', 1)
        ->has('expenseCategoryOptions'),
    );
});

test('authenticated user can create simple income from movement modal endpoint', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('movement.income.store'), [
        'entry_mode' => IncomeEntry::TYPE_SIMPLE,
        'description' => 'Freelance',
        'amount' => '900.00',
        'entry_date' => '2026-03-20',
    ]);

    $response->assertRedirect(route('movement.index'));

    $this->assertDatabaseHas('income_entries', [
        'user_id' => $user->id,
        'entry_type' => IncomeEntry::TYPE_SIMPLE,
        'description' => 'Freelance',
        'amount' => 900,
        'entry_date' => '2026-03-20 00:00:00',
    ]);
});

test('authenticated user can create simple expense from movement modal endpoint', function () {
    $user = User::factory()->create();
    $foodCategoryId = ExpenseCategory::query()->where('code', ExpenseCategory::CODE_FOOD)->value('id');

    $response = $this->actingAs($user)->post(route('movement.expense.store'), [
        'entry_mode' => ExpenseEntry::TYPE_SIMPLE,
        'description' => 'Mercado',
        'category_id' => $foodCategoryId,
        'amount' => '250.00',
        'entry_date' => '2026-03-20',
    ]);

    $response->assertRedirect(route('movement.index'));

    $this->assertDatabaseHas('expense_entries', [
        'user_id' => $user->id,
        'entry_type' => ExpenseEntry::TYPE_SIMPLE,
        'description' => 'Mercado',
        'category_id' => $foodCategoryId,
        'amount' => 250,
        'entry_date' => '2026-03-20 00:00:00',
    ]);
});

test('authenticated user can create fixed expense from movement modal endpoint', function () {
    $user = User::factory()->create();
    $housingCategoryId = ExpenseCategory::query()->where('code', ExpenseCategory::CODE_HOUSING)->value('id');

    $response = $this->actingAs($user)->post(route('movement.expense.store'), [
        'entry_mode' => ExpenseSource::TYPE_FIXED,
        'description' => 'Internet',
        'category_id' => $housingCategoryId,
        'amount' => '180.00',
        'effective_from' => '2026-04-01',
    ]);

    $response->assertRedirect(route('movement.index'));

    $this->assertDatabaseHas('expense_sources', [
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_FIXED,
        'description' => 'Internet',
        'category_id' => $housingCategoryId,
        'monthly_amount' => 180,
        'monthly_amount_started_at' => '2026-04-01 00:00:00',
    ]);
});

test('authenticated user can edit simple expense and stay on movement page', function () {
    $user = User::factory()->create();
    $foodCategoryId = ExpenseCategory::query()->where('code', ExpenseCategory::CODE_FOOD)->value('id');
    $healthCategoryId = ExpenseCategory::query()->where('code', ExpenseCategory::CODE_HEALTH)->value('id');

    $expenseEntry = ExpenseEntry::query()->create([
        'user_id' => $user->id,
        'entry_type' => ExpenseEntry::TYPE_SIMPLE,
        'description' => 'Farmacia',
        'category_id' => $foodCategoryId,
        'amount' => '90.00',
        'entry_date' => '2026-03-10',
    ]);

    $response = $this->actingAs($user)->patch(route('expense.entries.update', $expenseEntry->id), [
        'description' => 'Farmacia mensal',
        'category_id' => $healthCategoryId,
        'amount' => '120.00',
        'entry_date' => '2026-03-15',
        'redirect_to' => 'movement',
    ]);

    $response->assertRedirect(route('movement.index'));

    $this->assertDatabaseHas('expense_entries', [
        'id' => $expenseEntry->id,
        'user_id' => $user->id,
        'entry_type' => ExpenseEntry::TYPE_SIMPLE,
        'description' => 'Farmacia mensal',
        'category_id' => $healthCategoryId,
        'amount' => 120,
        'entry_date' => '2026-03-15 00:00:00',
    ]);
});

test('authenticated user can edit source expense and stay on movement page', function () {
    $user = User::factory()->create();
    $transportCategoryId = ExpenseCategory::query()->where('code', ExpenseCategory::CODE_TRANSPORT)->value('id');
    $housingCategoryId = ExpenseCategory::query()->where('code', ExpenseCategory::CODE_HOUSING)->value('id');

    $expenseSource = ExpenseSource::query()->create([
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_CREDIT_CARD,
        'description' => 'Cartao principal',
        'monthly_amount' => '500.00',
    ]);

    $expenseEntry = ExpenseEntry::query()->create([
        'user_id' => $user->id,
        'expense_source_id' => $expenseSource->id,
        'entry_type' => ExpenseEntry::TYPE_SOURCE,
        'description' => 'Fatura',
        'category_id' => $transportCategoryId,
        'amount' => '350.00',
        'entry_date' => '2026-03-05',
    ]);

    $response = $this->actingAs($user)->patch(route('expense.entries.source.update', $expenseEntry->id), [
        'description' => 'Fatura ajustada',
        'category_id' => $housingCategoryId,
        'amount' => '380.00',
        'entry_date' => '2026-03-06',
        'redirect_to' => 'movement',
    ]);

    $response->assertRedirect(route('movement.index'));

    $this->assertDatabaseHas('expense_entries', [
        'id' => $expenseEntry->id,
        'user_id' => $user->id,
        'expense_source_id' => $expenseSource->id,
        'entry_type' => ExpenseEntry::TYPE_SOURCE,
        'description' => 'Fatura ajustada',
        'category_id' => $housingCategoryId,
        'amount' => 380,
        'entry_date' => '2026-03-06 00:00:00',
    ]);
});

test('authenticated user can edit fixed expense source and stay on movement page', function () {
    $user = User::factory()->create();
    $housingCategoryId = ExpenseCategory::query()->where('code', ExpenseCategory::CODE_HOUSING)->value('id');
    $leisureCategoryId = ExpenseCategory::query()->where('code', ExpenseCategory::CODE_LEISURE)->value('id');

    $fixedSource = ExpenseSource::query()->create([
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_FIXED,
        'description' => 'Academia',
        'category_id' => $housingCategoryId,
        'monthly_amount' => '140.00',
        'monthly_amount_started_at' => '2026-03-01',
    ]);

    $response = $this->actingAs($user)->patch(route('expense.sources.update', $fixedSource->id), [
        'description' => 'Academia premium',
        'category_id' => $leisureCategoryId,
        'monthly_amount' => '160.00',
        'effective_from' => '2026-04-01',
        'redirect_to' => 'movement',
    ]);

    $response->assertRedirect(route('movement.index'));

    $this->assertDatabaseHas('expense_sources', [
        'id' => $fixedSource->id,
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_FIXED,
        'description' => 'Academia premium',
        'category_id' => $leisureCategoryId,
        'monthly_amount' => 160,
        'monthly_amount_started_at' => '2026-04-01 00:00:00',
    ]);

    $this->assertDatabaseHas('expense_source_amount_histories', [
        'expense_source_id' => $fixedSource->id,
        'amount' => 160,
        'effective_from' => '2026-04-01 00:00:00',
    ]);
});
