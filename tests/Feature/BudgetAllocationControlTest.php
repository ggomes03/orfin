<?php

use App\Models\BudgetAllocation;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('authenticated users can access budget allocation control page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('budget.control.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('budget/control')
        ->where('totalPercentage', 0)
        ->where('remainingPercentage', 100)
        ->has('items', 6),
    );
});

test('budget allocations are saved for current user when total is at most 100', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put(route('budget.control.update'), [
        BudgetAllocation::CATEGORY_FINANCIAL_FREEDOM => 20,
        BudgetAllocation::CATEGORY_FIXED_COSTS => 35,
        BudgetAllocation::CATEGORY_COMFORT => 15,
        BudgetAllocation::CATEGORY_GOALS => 10,
        BudgetAllocation::CATEGORY_PLEASURES => 10,
        BudgetAllocation::CATEGORY_KNOWLEDGE => 10,
    ]);

    $response->assertRedirect(route('budget.control.index'));

    $this->assertDatabaseHas('budget_allocations', [
        'user_id' => $user->id,
        'category' => BudgetAllocation::CATEGORY_FINANCIAL_FREEDOM,
        'percentage' => 20,
    ]);

    $this->assertDatabaseCount('budget_allocations', 6);
});

test('budget allocation update is rejected when total is greater than 100', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('budget.control.index'))
        ->put(route('budget.control.update'), [
            BudgetAllocation::CATEGORY_FINANCIAL_FREEDOM => 30,
            BudgetAllocation::CATEGORY_FIXED_COSTS => 30,
            BudgetAllocation::CATEGORY_COMFORT => 20,
            BudgetAllocation::CATEGORY_GOALS => 10,
            BudgetAllocation::CATEGORY_PLEASURES => 10,
            BudgetAllocation::CATEGORY_KNOWLEDGE => 10,
        ]);

    $response->assertRedirect(route('budget.control.index'));
    $response->assertSessionHasErrors('total');

    $this->assertDatabaseCount('budget_allocations', 0);
});
