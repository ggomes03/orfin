<?php

use App\Models\ExpenseEntry;
use App\Models\ExpenseSource;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected from expense entries page', function () {
    $response = $this->get(route('expense.entries.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view expense entries page', function () {
    $user = User::factory()->create();

    $fixedSource = ExpenseSource::query()->create([
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

    $response = $this->actingAs($user)->get(route('expense.entries.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('expense/entries')
        ->has('fixedExpenseSources', 1)
        ->where('fixedExpenseSources.0.id', $fixedSource->id)
        ->has('expenseSources', 1)
        ->where('expenseSources.0.id', $creditCardSource->id),
    );
});

test('authenticated user can create a fixed expense source', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('expense.sources.store'), [
        'type' => ExpenseSource::TYPE_FIXED,
        'description' => 'Aluguel',
        'monthly_amount' => '1200.00',
    ]);

    $response->assertRedirect(route('expense.entries.index'));

    $this->assertDatabaseHas('expense_sources', [
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_FIXED,
        'description' => 'Aluguel',
        'monthly_amount' => '1200.00',
    ]);
});

test('authenticated user can create a credit card expense source without fixed amount', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('expense.sources.store'), [
        'type' => ExpenseSource::TYPE_CREDIT_CARD,
        'description' => 'Cartao principal',
        'monthly_amount' => null,
    ]);

    $response->assertRedirect(route('expense.entries.index'));

    $this->assertDatabaseHas('expense_sources', [
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_CREDIT_CARD,
        'description' => 'Cartao principal',
        'monthly_amount' => null,
    ]);
});

test('authenticated user can create a simple expense entry', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('expense.entries.store'), [
        'entry_mode' => ExpenseEntry::TYPE_SIMPLE,
        'description' => 'Supermercado',
        'amount' => '350.00',
        'entry_date' => '2026-03-22',
    ]);

    $response->assertRedirect(route('expense.entries.index'));

    $this->assertDatabaseHas('expense_entries', [
        'user_id' => $user->id,
        'expense_source_id' => null,
        'entry_type' => ExpenseEntry::TYPE_SIMPLE,
        'description' => 'Supermercado',
        'amount' => 350,
        'entry_date' => '2026-03-22 00:00:00',
    ]);
});

test('authenticated user can create credit card based expense entry with manual amount', function () {
    $user = User::factory()->create();

    $source = ExpenseSource::query()->create([
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_CREDIT_CARD,
        'description' => 'Cartao principal',
        'monthly_amount' => null,
    ]);

    $response = $this->actingAs($user)->post(route('expense.entries.store'), [
        'entry_mode' => ExpenseEntry::TYPE_SOURCE,
        'expense_source_id' => $source->id,
        'description' => 'Fatura de marco',
        'amount' => '980.00',
        'entry_date' => '2026-03-22',
    ]);

    $response->assertRedirect(route('expense.entries.index'));

    $this->assertDatabaseHas('expense_entries', [
        'user_id' => $user->id,
        'expense_source_id' => $source->id,
        'entry_type' => ExpenseEntry::TYPE_SOURCE,
        'description' => 'Fatura de marco',
        'amount' => 980,
        'entry_date' => '2026-03-22 00:00:00',
    ]);
});

test('user cannot create source based expense entry with source from another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $otherSource = ExpenseSource::query()->create([
        'user_id' => $otherUser->id,
        'type' => ExpenseSource::TYPE_CREDIT_CARD,
        'description' => 'Cartao externo',
        'monthly_amount' => null,
    ]);

    $response = $this->actingAs($user)->from(route('expense.entries.index'))->post(route('expense.entries.store'), [
        'entry_mode' => ExpenseEntry::TYPE_SOURCE,
        'expense_source_id' => $otherSource->id,
        'amount' => '100.00',
        'entry_date' => '2026-03-22',
    ]);

    $response->assertRedirect(route('expense.entries.index'));
    $response->assertSessionHasErrors('expense_source_id');

    $this->assertDatabaseMissing('expense_entries', [
        'user_id' => $user->id,
        'expense_source_id' => $otherSource->id,
    ]);
});

test('user cannot create source based entry for fixed expense source', function () {
    $user = User::factory()->create();

    $fixedSource = ExpenseSource::query()->create([
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_FIXED,
        'description' => 'Aluguel',
        'monthly_amount' => '1200.00',
    ]);

    $response = $this->actingAs($user)->from(route('expense.entries.index'))->post(route('expense.entries.store'), [
        'entry_mode' => ExpenseEntry::TYPE_SOURCE,
        'expense_source_id' => $fixedSource->id,
        'amount' => '1200.00',
        'entry_date' => '2026-03-23',
    ]);

    $response->assertRedirect(route('expense.entries.index'));
    $response->assertSessionHasErrors('expense_source_id');

    $this->assertDatabaseMissing('expense_entries', [
        'user_id' => $user->id,
        'expense_source_id' => $fixedSource->id,
    ]);
});

test('authenticated user can update a fixed expense source', function () {
    $user = User::factory()->create();

    $fixedSource = ExpenseSource::query()->create([
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_FIXED,
        'description' => 'Aluguel antigo',
        'monthly_amount' => '1000.00',
    ]);

    $response = $this->actingAs($user)->patch(route('expense.sources.update', ['expenseSourceId' => $fixedSource->id]), [
        'description' => 'Aluguel atualizado',
        'monthly_amount' => '1300.00',
    ]);

    $response->assertRedirect(route('expense.entries.index'));

    $this->assertDatabaseHas('expense_sources', [
        'id' => $fixedSource->id,
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_FIXED,
        'description' => 'Aluguel atualizado',
        'monthly_amount' => '1300.00',
    ]);
});

test('authenticated user can delete a fixed expense source', function () {
    $user = User::factory()->create();

    $fixedSource = ExpenseSource::query()->create([
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_FIXED,
        'description' => 'Aluguel',
        'monthly_amount' => '1200.00',
    ]);

    $response = $this->actingAs($user)->delete(route('expense.sources.destroy', ['expenseSourceId' => $fixedSource->id]));

    $response->assertRedirect(route('expense.entries.index'));

    $this->assertDatabaseMissing('expense_sources', [
        'id' => $fixedSource->id,
    ]);
});

test('authenticated user can update a simple expense entry', function () {
    $user = User::factory()->create();

    $entry = ExpenseEntry::query()->create([
        'user_id' => $user->id,
        'expense_source_id' => null,
        'entry_type' => ExpenseEntry::TYPE_SIMPLE,
        'description' => 'Mercado antigo',
        'amount' => '120.00',
        'entry_date' => '2026-03-21',
    ]);

    $response = $this->actingAs($user)->patch(route('expense.entries.update', ['expenseEntryId' => $entry->id]), [
        'description' => 'Mercado atualizado',
        'amount' => '180.50',
        'entry_date' => '2026-03-24',
    ]);

    $response->assertRedirect(route('expense.entries.index'));

    $this->assertDatabaseHas('expense_entries', [
        'id' => $entry->id,
        'user_id' => $user->id,
        'expense_source_id' => null,
        'entry_type' => ExpenseEntry::TYPE_SIMPLE,
        'description' => 'Mercado atualizado',
        'amount' => '180.50',
        'entry_date' => '2026-03-24 00:00:00',
    ]);
});

test('user cannot update source based expense entry as simple entry endpoint', function () {
    $user = User::factory()->create();

    $source = ExpenseSource::query()->create([
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_CREDIT_CARD,
        'description' => 'Cartao principal',
        'monthly_amount' => null,
    ]);

    $sourceEntry = ExpenseEntry::query()->create([
        'user_id' => $user->id,
        'expense_source_id' => $source->id,
        'entry_type' => ExpenseEntry::TYPE_SOURCE,
        'description' => 'Fatura',
        'amount' => '500.00',
        'entry_date' => '2026-03-21',
    ]);

    $response = $this->actingAs($user)->patch(route('expense.entries.update', ['expenseEntryId' => $sourceEntry->id]), [
        'description' => 'Tentativa de alterar',
        'amount' => '300.00',
        'entry_date' => '2026-03-24',
    ]);

    $response->assertNotFound();

    $this->assertDatabaseHas('expense_entries', [
        'id' => $sourceEntry->id,
        'entry_type' => ExpenseEntry::TYPE_SOURCE,
        'description' => 'Fatura',
        'amount' => '500.00',
    ]);
});

test('authenticated user can delete a simple expense entry', function () {
    $user = User::factory()->create();

    $entry = ExpenseEntry::query()->create([
        'user_id' => $user->id,
        'expense_source_id' => null,
        'entry_type' => ExpenseEntry::TYPE_SIMPLE,
        'description' => 'Saida avulsa',
        'amount' => '220.00',
        'entry_date' => '2026-03-22',
    ]);

    $response = $this->actingAs($user)->delete(route('expense.entries.destroy', ['expenseEntryId' => $entry->id]));

    $response->assertRedirect(route('expense.entries.index'));

    $this->assertDatabaseMissing('expense_entries', [
        'id' => $entry->id,
    ]);
});

test('user cannot delete source based expense entry via simple entry endpoint', function () {
    $user = User::factory()->create();

    $source = ExpenseSource::query()->create([
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_CREDIT_CARD,
        'description' => 'Cartao principal',
        'monthly_amount' => null,
    ]);

    $sourceEntry = ExpenseEntry::query()->create([
        'user_id' => $user->id,
        'expense_source_id' => $source->id,
        'entry_type' => ExpenseEntry::TYPE_SOURCE,
        'description' => 'Fatura',
        'amount' => '500.00',
        'entry_date' => '2026-03-21',
    ]);

    $response = $this->actingAs($user)->delete(route('expense.entries.destroy', ['expenseEntryId' => $sourceEntry->id]));

    $response->assertNotFound();

    $this->assertDatabaseHas('expense_entries', [
        'id' => $sourceEntry->id,
        'entry_type' => ExpenseEntry::TYPE_SOURCE,
    ]);
});

test('authenticated user can update source based expense entry', function () {
    $user = User::factory()->create();

    $source = ExpenseSource::query()->create([
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_CREDIT_CARD,
        'description' => 'Cartao principal',
        'monthly_amount' => null,
    ]);

    $sourceEntry = ExpenseEntry::query()->create([
        'user_id' => $user->id,
        'expense_source_id' => $source->id,
        'entry_type' => ExpenseEntry::TYPE_SOURCE,
        'description' => 'Fatura antiga',
        'amount' => '500.00',
        'entry_date' => '2026-03-21',
    ]);

    $response = $this->actingAs($user)->patch(route('expense.entries.source.update', ['expenseEntryId' => $sourceEntry->id]), [
        'description' => 'Fatura atualizada',
        'amount' => '650.00',
        'entry_date' => '2026-03-24',
    ]);

    $response->assertRedirect(route('expense.entries.index'));

    $this->assertDatabaseHas('expense_entries', [
        'id' => $sourceEntry->id,
        'entry_type' => ExpenseEntry::TYPE_SOURCE,
        'description' => 'Fatura atualizada',
        'amount' => '650.00',
        'entry_date' => '2026-03-24 00:00:00',
    ]);
});

test('authenticated user can delete source based expense entry', function () {
    $user = User::factory()->create();

    $source = ExpenseSource::query()->create([
        'user_id' => $user->id,
        'type' => ExpenseSource::TYPE_CREDIT_CARD,
        'description' => 'Cartao principal',
        'monthly_amount' => null,
    ]);

    $sourceEntry = ExpenseEntry::query()->create([
        'user_id' => $user->id,
        'expense_source_id' => $source->id,
        'entry_type' => ExpenseEntry::TYPE_SOURCE,
        'description' => 'Fatura',
        'amount' => '500.00',
        'entry_date' => '2026-03-21',
    ]);

    $response = $this->actingAs($user)->delete(route('expense.entries.source.destroy', ['expenseEntryId' => $sourceEntry->id]));

    $response->assertRedirect(route('expense.entries.index'));

    $this->assertDatabaseMissing('expense_entries', [
        'id' => $sourceEntry->id,
    ]);
});

test('user cannot update source based expense entry from another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $source = ExpenseSource::query()->create([
        'user_id' => $otherUser->id,
        'type' => ExpenseSource::TYPE_CREDIT_CARD,
        'description' => 'Cartao externo',
        'monthly_amount' => null,
    ]);

    $sourceEntry = ExpenseEntry::query()->create([
        'user_id' => $otherUser->id,
        'expense_source_id' => $source->id,
        'entry_type' => ExpenseEntry::TYPE_SOURCE,
        'description' => 'Fatura externa',
        'amount' => '500.00',
        'entry_date' => '2026-03-21',
    ]);

    $response = $this->actingAs($user)->patch(route('expense.entries.source.update', ['expenseEntryId' => $sourceEntry->id]), [
        'description' => 'Tentativa',
        'amount' => '100.00',
        'entry_date' => '2026-03-22',
    ]);

    $response->assertNotFound();

    $this->assertDatabaseHas('expense_entries', [
        'id' => $sourceEntry->id,
        'description' => 'Fatura externa',
        'amount' => '500.00',
    ]);
});

test('user cannot delete source based expense entry from another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $source = ExpenseSource::query()->create([
        'user_id' => $otherUser->id,
        'type' => ExpenseSource::TYPE_CREDIT_CARD,
        'description' => 'Cartao externo',
        'monthly_amount' => null,
    ]);

    $sourceEntry = ExpenseEntry::query()->create([
        'user_id' => $otherUser->id,
        'expense_source_id' => $source->id,
        'entry_type' => ExpenseEntry::TYPE_SOURCE,
        'description' => 'Fatura externa',
        'amount' => '500.00',
        'entry_date' => '2026-03-21',
    ]);

    $response = $this->actingAs($user)->delete(route('expense.entries.source.destroy', ['expenseEntryId' => $sourceEntry->id]));

    $response->assertNotFound();

    $this->assertDatabaseHas('expense_entries', [
        'id' => $sourceEntry->id,
    ]);
});

test('user cannot delete simple expense entry from another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $otherEntry = ExpenseEntry::query()->create([
        'user_id' => $otherUser->id,
        'expense_source_id' => null,
        'entry_type' => ExpenseEntry::TYPE_SIMPLE,
        'description' => 'Saida de outro usuario',
        'amount' => '300.00',
        'entry_date' => '2026-03-22',
    ]);

    $response = $this->actingAs($user)->delete(route('expense.entries.destroy', ['expenseEntryId' => $otherEntry->id]));

    $response->assertNotFound();

    $this->assertDatabaseHas('expense_entries', [
        'id' => $otherEntry->id,
    ]);
});

test('user cannot update fixed expense source from another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $otherFixedSource = ExpenseSource::query()->create([
        'user_id' => $otherUser->id,
        'type' => ExpenseSource::TYPE_FIXED,
        'description' => 'Aluguel externo',
        'monthly_amount' => '1400.00',
    ]);

    $response = $this->actingAs($user)->patch(route('expense.sources.update', ['expenseSourceId' => $otherFixedSource->id]), [
        'description' => 'Tentativa indevida',
        'monthly_amount' => '1500.00',
    ]);

    $response->assertNotFound();

    $this->assertDatabaseHas('expense_sources', [
        'id' => $otherFixedSource->id,
        'description' => 'Aluguel externo',
        'monthly_amount' => '1400.00',
    ]);
});

test('user cannot delete fixed expense source from another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $otherFixedSource = ExpenseSource::query()->create([
        'user_id' => $otherUser->id,
        'type' => ExpenseSource::TYPE_FIXED,
        'description' => 'Aluguel externo',
        'monthly_amount' => '1400.00',
    ]);

    $response = $this->actingAs($user)->delete(route('expense.sources.destroy', ['expenseSourceId' => $otherFixedSource->id]));

    $response->assertNotFound();

    $this->assertDatabaseHas('expense_sources', [
        'id' => $otherFixedSource->id,
    ]);
});
