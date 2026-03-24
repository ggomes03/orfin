<?php

use App\Models\IncomeEntry;
use App\Models\IncomeSource;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected from income entries page', function () {
    $response = $this->get(route('income.entries.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view income entries page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('income.entries.index'));

    $response->assertOk();
});

test('authenticated user can create an income source', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('income.sources.store'), [
        'type' => IncomeSource::TYPE_SALARY,
        'description' => 'Salario CLT',
        'monthly_amount' => '3500.00',
    ]);

    $response->assertRedirect(route('income.entries.index'));

    $this->assertDatabaseHas('income_sources', [
        'user_id' => $user->id,
        'type' => IncomeSource::TYPE_SALARY,
        'description' => 'Salario CLT',
        'monthly_amount' => '3500.00',
    ]);
});

test('authenticated user can create a simple income entry', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('income.entries.store'), [
        'entry_mode' => IncomeEntry::TYPE_SIMPLE,
        'description' => 'Freela de fim de semana',
        'amount' => '750.00',
        'entry_date' => '2026-03-22',
    ]);

    $response->assertRedirect(route('income.entries.index'));

    $this->assertDatabaseHas('income_entries', [
        'user_id' => $user->id,
        'income_source_id' => null,
        'entry_type' => IncomeEntry::TYPE_SIMPLE,
        'description' => 'Freela de fim de semana',
        'amount' => 750,
        'entry_date' => '2026-03-22 00:00:00',
    ]);
});

test('authenticated user can create source based income entry with fixed amount', function () {
    $user = User::factory()->create();

    $source = IncomeSource::query()->create([
        'user_id' => $user->id,
        'type' => IncomeSource::TYPE_SERVICE_PROVISION,
        'description' => 'Prestacao mensal para cliente Y',
        'monthly_amount' => '1800.00',
    ]);

    $response = $this->actingAs($user)->post(route('income.entries.store'), [
        'entry_mode' => IncomeEntry::TYPE_SOURCE,
        'income_source_id' => $source->id,
        'entry_date' => '2026-03-22',
    ]);

    $response->assertRedirect(route('income.entries.index'));

    $this->assertDatabaseHas('income_entries', [
        'user_id' => $user->id,
        'income_source_id' => $source->id,
        'entry_type' => IncomeEntry::TYPE_SOURCE,
        'description' => 'Prestacao mensal para cliente Y',
        'amount' => 1800,
        'entry_date' => '2026-03-22 00:00:00',
    ]);
});

test('user cannot create source based entry with source from another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $otherSource = IncomeSource::query()->create([
        'user_id' => $otherUser->id,
        'type' => IncomeSource::TYPE_COMPLEMENT,
        'description' => 'Complemento externo',
        'monthly_amount' => '999.00',
    ]);

    $response = $this->actingAs($user)->from(route('income.entries.index'))->post(route('income.entries.store'), [
        'entry_mode' => IncomeEntry::TYPE_SOURCE,
        'income_source_id' => $otherSource->id,
        'entry_date' => '2026-03-22',
    ]);

    $response->assertRedirect(route('income.entries.index'));
    $response->assertSessionHasErrors('income_source_id');

    $this->assertDatabaseMissing('income_entries', [
        'user_id' => $user->id,
        'income_source_id' => $otherSource->id,
    ]);
});

test('user cannot create source based entry for salary source', function () {
    $user = User::factory()->create();

    $salarySource = IncomeSource::query()->create([
        'user_id' => $user->id,
        'type' => IncomeSource::TYPE_SALARY,
        'description' => 'Salario CLT',
        'monthly_amount' => '4500.00',
    ]);

    $response = $this->actingAs($user)->from(route('income.entries.index'))->post(route('income.entries.store'), [
        'entry_mode' => IncomeEntry::TYPE_SOURCE,
        'income_source_id' => $salarySource->id,
        'entry_date' => '2026-03-23',
    ]);

    $response->assertRedirect(route('income.entries.index'));
    $response->assertSessionHasErrors('income_source_id');

    $this->assertDatabaseMissing('income_entries', [
        'user_id' => $user->id,
        'income_source_id' => $salarySource->id,
    ]);
});

test('authenticated user can update a simple income entry', function () {
    $user = User::factory()->create();

    $entry = IncomeEntry::query()->create([
        'user_id' => $user->id,
        'income_source_id' => null,
        'entry_type' => IncomeEntry::TYPE_SIMPLE,
        'description' => 'Entrada antiga',
        'amount' => '200.00',
        'entry_date' => '2026-03-21',
    ]);

    $response = $this->actingAs($user)->patch(route('income.entries.update', ['incomeEntryId' => $entry->id]), [
        'description' => 'Entrada atualizada',
        'amount' => '380.50',
        'entry_date' => '2026-03-24',
    ]);

    $response->assertRedirect(route('income.entries.index'));

    $this->assertDatabaseHas('income_entries', [
        'id' => $entry->id,
        'description' => 'Entrada atualizada',
        'amount' => '380.50',
        'entry_date' => '2026-03-24 00:00:00',
    ]);
});

test('authenticated user can update a source based income entry', function () {
    $user = User::factory()->create();

    $source = IncomeSource::query()->create([
        'user_id' => $user->id,
        'type' => IncomeSource::TYPE_SERVICE_PROVISION,
        'description' => 'Contrato mensal',
        'monthly_amount' => '1800.00',
    ]);

    $entry = IncomeEntry::query()->create([
        'user_id' => $user->id,
        'income_source_id' => $source->id,
        'entry_type' => IncomeEntry::TYPE_SOURCE,
        'description' => 'Contrato antigo',
        'amount' => '1800.00',
        'entry_date' => '2026-03-21',
    ]);

    $response = $this->actingAs($user)->patch(route('income.entries.update', ['incomeEntryId' => $entry->id]), [
        'description' => 'Contrato ajustado',
        'amount' => '1900.00',
        'entry_date' => '2026-03-24',
    ]);

    $response->assertRedirect(route('income.entries.index'));

    $this->assertDatabaseHas('income_entries', [
        'id' => $entry->id,
        'entry_type' => IncomeEntry::TYPE_SOURCE,
        'description' => 'Contrato ajustado',
        'amount' => '1900.00',
        'entry_date' => '2026-03-24 00:00:00',
    ]);
});

test('user cannot update income entry from another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $entry = IncomeEntry::query()->create([
        'user_id' => $otherUser->id,
        'income_source_id' => null,
        'entry_type' => IncomeEntry::TYPE_SIMPLE,
        'description' => 'Entrada externa',
        'amount' => '250.00',
        'entry_date' => '2026-03-21',
    ]);

    $response = $this->actingAs($user)->patch(route('income.entries.update', ['incomeEntryId' => $entry->id]), [
        'description' => 'Tentativa de alteracao',
        'amount' => '100.00',
        'entry_date' => '2026-03-22',
    ]);

    $response->assertNotFound();

    $this->assertDatabaseHas('income_entries', [
        'id' => $entry->id,
        'description' => 'Entrada externa',
        'amount' => '250.00',
    ]);
});
