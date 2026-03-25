<?php

use App\Models\ExpenseEntry;
use App\Models\ExpenseSource;
use App\Models\IncomeEntry;
use App\Models\IncomeSource;
use App\Models\BudgetAllocation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('annualIncomeAmount', 0)
        ->where('annualExpenseAmount', 0)
        ->where('annualBalanceAmount', 0)
        ->where('annualBudgetDetail.referenceIncomeAmount', 0)
        ->where('annualBudgetDetail.unallocatedPercentage', 100)
        ->where('selectedMonth', null)
        ->where('selectedMonthDetail', null)
        ->where('exerciseYear', now()->year),
    );
});

test('dashboard returns sum of income entries for current exercise year', function () {
    $user = User::factory()->create();

    IncomeEntry::query()->create([
        'user_id' => $user->id,
        'income_source_id' => null,
        'entry_type' => IncomeEntry::TYPE_SIMPLE,
        'description' => 'Entrada 1',
        'amount' => 1000,
        'entry_date' => now()->startOfYear()->addMonth()->toDateString(),
    ]);

    IncomeEntry::query()->create([
        'user_id' => $user->id,
        'income_source_id' => null,
        'entry_type' => IncomeEntry::TYPE_SIMPLE,
        'description' => 'Entrada 2',
        'amount' => 500,
        'entry_date' => now()->startOfYear()->addMonths(2)->toDateString(),
    ]);

    IncomeEntry::query()->create([
        'user_id' => $user->id,
        'income_source_id' => null,
        'entry_type' => IncomeEntry::TYPE_SIMPLE,
        'description' => 'Entrada ano anterior',
        'amount' => 2000,
        'entry_date' => now()->subYear()->toDateString(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('annualIncomeAmount', 1500)
        ->where('annualExpenseAmount', 0)
        ->where('annualBalanceAmount', 1500)
        ->where('exerciseYear', now()->year),
    );
});

test('dashboard projects annual salary income and ignores manual salary entries', function () {
    $user = User::factory()->create();

    $salarySource = IncomeSource::query()->create([
        'user_id' => $user->id,
        'type' => IncomeSource::TYPE_SALARY,
        'description' => 'Salario principal',
        'monthly_amount' => '3000.00',
    ]);

    $serviceSource = IncomeSource::query()->create([
        'user_id' => $user->id,
        'type' => IncomeSource::TYPE_SERVICE_PROVISION,
        'description' => 'Contrato mensal',
        'monthly_amount' => '700.00',
    ]);

    IncomeEntry::query()->create([
        'user_id' => $user->id,
        'income_source_id' => null,
        'entry_type' => IncomeEntry::TYPE_SIMPLE,
        'description' => 'Entrada avulsa',
        'amount' => 500,
        'entry_date' => now()->startOfYear()->addMonth()->toDateString(),
    ]);

    IncomeEntry::query()->create([
        'user_id' => $user->id,
        'income_source_id' => $serviceSource->id,
        'entry_type' => IncomeEntry::TYPE_SOURCE,
        'description' => 'Servico lancado no mes',
        'amount' => 700,
        'entry_date' => now()->startOfYear()->addMonths(2)->toDateString(),
    ]);

    // Legacy entries linked to salary should not inflate the annual total.
    IncomeEntry::query()->create([
        'user_id' => $user->id,
        'income_source_id' => $salarySource->id,
        'entry_type' => IncomeEntry::TYPE_SOURCE,
        'description' => 'Salario lancado manualmente',
        'amount' => 3000,
        'entry_date' => now()->startOfYear()->addMonths(3)->toDateString(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('annualIncomeAmount', 37200)
        ->where('annualExpenseAmount', 0)
        ->where('annualBalanceAmount', 37200)
        ->where('exerciseYear', now()->year),
    );
});

test('dashboard projects annual fixed expenses and keeps credit card monthly manual', function () {
    $user = User::factory()->create();

    $fixedExpenseSource = ExpenseSource::query()->create([
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_FIXED,
        'description' => 'Aluguel',
        'monthly_amount' => '1200.00',
    ]);

    $creditCardSource = ExpenseSource::query()->create([
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_CREDIT_CARD,
        'description' => 'Cartao principal',
        'monthly_amount' => null,
    ]);

    ExpenseEntry::query()->create([
        'user_id' => $user->id,
        'expense_source_id' => $creditCardSource->id,
        'entry_type' => ExpenseEntry::TYPE_SOURCE,
        'description' => 'Fatura fevereiro',
        'amount' => 400,
        'entry_date' => now()->startOfYear()->addMonth()->toDateString(),
    ]);

    // Legacy entries linked to fixed expenses should not inflate annual totals.
    ExpenseEntry::query()->create([
        'user_id' => $user->id,
        'expense_source_id' => $fixedExpenseSource->id,
        'entry_type' => ExpenseEntry::TYPE_SOURCE,
        'description' => 'Aluguel lancado manualmente',
        'amount' => 1200,
        'entry_date' => now()->startOfYear()->addMonths(2)->toDateString(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('annualIncomeAmount', 0)
        ->where('annualExpenseAmount', 14800)
        ->where('annualBalanceAmount', -14800)
        ->where('exerciseYear', now()->year),
    );
});

test('dashboard returns monthly detail when month query is provided', function () {
    $user = User::factory()->create();

    BudgetAllocation::query()->create([
        'user_id' => $user->id,
        'category' => BudgetAllocation::CATEGORY_FINANCIAL_FREEDOM,
        'percentage' => 40,
    ]);

    BudgetAllocation::query()->create([
        'user_id' => $user->id,
        'category' => BudgetAllocation::CATEGORY_FIXED_COSTS,
        'percentage' => 30,
    ]);

    IncomeSource::query()->create([
        'user_id' => $user->id,
        'type' => IncomeSource::TYPE_SALARY,
        'description' => 'Salario principal',
        'monthly_amount' => '3000.00',
    ]);

    IncomeEntry::query()->create([
        'user_id' => $user->id,
        'income_source_id' => null,
        'entry_type' => IncomeEntry::TYPE_SIMPLE,
        'description' => 'Entrada detalhada',
        'amount' => 800,
        'entry_date' => now()->startOfYear()->addMonth()->toDateString(),
    ]);

    ExpenseEntry::query()->create([
        'user_id' => $user->id,
        'expense_source_id' => null,
        'entry_type' => ExpenseEntry::TYPE_SIMPLE,
        'description' => 'Saida detalhada',
        'amount' => 300,
        'entry_date' => now()->startOfYear()->addMonth()->toDateString(),
    ]);

    ExpenseSource::query()->create([
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_FIXED,
        'description' => 'Internet',
        'monthly_amount' => '120.00',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard', ['month' => 2]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('selectedMonth', 2)
        ->where('selectedMonthDetail.month', 2)
        ->where('selectedMonthDetail.referenceIncomeAmount', 3800)
        ->where('selectedMonthDetail.items.0.label', 'Liberdade financeira')
        ->where('selectedMonthDetail.items.0.percentage', 40)
        ->where('selectedMonthDetail.items.0.amount', 1520)
        ->where('selectedMonthDetail.items.1.label', 'Custos fixos')
        ->where('selectedMonthDetail.items.1.percentage', 30)
        ->where('selectedMonthDetail.items.1.amount', 1140)
        ->where('selectedMonthDetail.unallocatedPercentage', 30)
        ->where('selectedMonthDetail.unallocatedAmount', 1140),
    );
});

test('dashboard ignores invalid month query and returns no monthly detail', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard', ['month' => 99]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('selectedMonth', null)
        ->where('selectedMonthDetail', null),
    );
});