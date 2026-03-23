<?php

use App\Models\IncomeEntry;
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
        ->where('exerciseYear', now()->year),
    );
});