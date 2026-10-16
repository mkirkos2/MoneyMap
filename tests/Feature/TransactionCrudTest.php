<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();

    // Create categories for testing
    $this->incomeCategory = Category::factory()->for($this->user)->create(['type' => 'income']);
    $this->expenseCategory = Category::factory()->for($this->user)->create(['type' => 'expense']);

    $this->otherUserCategory = Category::factory()->for($this->otherUser)->create(['type' => 'income']);
});

test('guest cannot access transactions', function () {
    $response = $this->get(route('transactions.index'));
    $response->assertRedirect(route('login'));
});

test('guest cannot create or modify transactions', function () {
    $transaction = Transaction::factory()->for($this->user)->create();

    $this->get(route('transactions.create'))->assertRedirect(route('login'));
    $this->post(route('transactions.store'), [
        'type' => 'income',
        'amount' => '10.00',
        'transaction_date' => '2026-10-15',
    ])->assertRedirect(route('login'));
    $this->get(route('transactions.edit', $transaction))->assertRedirect(route('login'));
    $this->put(route('transactions.update', $transaction), [
        'type' => 'income',
        'amount' => '10.00',
        'transaction_date' => '2026-10-15',
    ])->assertRedirect(route('login'));
    $this->delete(route('transactions.destroy', $transaction))->assertRedirect(route('login'));

    $this->assertDatabaseMissing('transactions', ['amount' => 10.00]);
    expect($transaction->fresh()->description)->toBe($transaction->description);
});

test('user can view transactions index', function () {
    $this->actingAs($this->user)->get(route('transactions.index'))
        ->assertInertia(fn ($page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 0)
            ->where('transactions.total', 0)
            ->has('categories')
            ->has('filters'));
});

test('user can view only their own transactions', function () {
    $ownTransaction = Transaction::factory()->for($this->user)->create();
    $otherTransaction = Transaction::factory()->for($this->otherUser)->create();

    $response = $this->actingAs($this->user)->get(route('transactions.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('transactions/Index')
        ->has('transactions.data', 1)
        ->where('transactions.data.0.id', $ownTransaction->id)
        ->where('transactions.total', 1)
        ->missing('transactions.data.1'));
});

test('user can create transaction', function () {
    $response = $this->actingAs($this->user)->get(route('transactions.create'));
    $response->assertOk();

    $transactionData = [
        'category_id' => $this->incomeCategory->id,
        'type' => 'income',
        'amount' => '100.50',
        'transaction_date' => '2026-10-15',
        'description' => 'Test Transaction',
        'notes' => 'Test Notes',
    ];

    $response = $this->actingAs($this->user)->post(route('transactions.store'), $transactionData);
    $response->assertRedirect(route('transactions.index'));
    $response->assertSessionHas('toast');

    $this->assertDatabaseHas('transactions', array_merge($transactionData, [
        'user_id' => $this->user->id,
        'amount' => 100.5,
        'transaction_date' => '2026-10-15 00:00:00',
    ]));

    $this->actingAs($this->user)->get(route('transactions.index'))
        ->assertInertia(fn ($page) => $page
            ->where('transactions.data.0.description', 'Test Transaction')
            ->where('transactions.total', 1));
});

test('user cannot create transaction with invalid category', function () {
    $transactionData = [
        'category_id' => $this->otherUserCategory->id, // Another user's category
        'type' => 'income',
        'amount' => '100.50',
        'transaction_date' => '2026-10-15',
        'description' => 'Test Transaction',
    ];

    $response = $this->actingAs($this->user)->post(route('transactions.store'), $transactionData);
    $response->assertSessionHasErrors(['category_id']);
});

test('user cannot create transaction with mismatched type', function () {
    $transactionData = [
        'category_id' => $this->incomeCategory->id, // Income category
        'type' => 'expense', // But expense type
        'amount' => '100.50',
        'transaction_date' => '2026-10-15',
        'description' => 'Test Transaction',
    ];

    $response = $this->actingAs($this->user)->post(route('transactions.store'), $transactionData);
    $response->assertSessionHasErrors(['category_id']);
});

test('user cannot create transaction with invalid data', function () {
    $response = $this->actingAs($this->user)->post(route('transactions.store'), []);
    $response->assertSessionHasErrors(['type', 'amount', 'transaction_date']);

    $response = $this->actingAs($this->user)->post(route('transactions.store'), [
        'type' => 'invalid-type',
        'amount' => '-50',
        'transaction_date' => 'invalid-date',
    ]);
    $response->assertSessionHasErrors(['type', 'amount', 'transaction_date']);
});

test('user can edit their own transaction', function () {
    $transaction = Transaction::factory()->for($this->user)->create();

    $response = $this->actingAs($this->user)->get(route('transactions.edit', $transaction));
    $response->assertOk();

    $updatedData = [
        'category_id' => $this->expenseCategory->id,
        'type' => 'expense',
        'amount' => '200.75',
        'transaction_date' => '2026-10-20',
        'description' => 'Updated Transaction',
        'notes' => 'Updated Notes',
    ];

    $response = $this->actingAs($this->user)->put(route('transactions.update', $transaction), $updatedData);
    $response->assertRedirect(route('transactions.index'));
    $response->assertSessionHas('toast');

    $this->assertDatabaseHas('transactions', array_merge($updatedData, [
        'id' => $transaction->id,
        'amount' => 200.75,
        'transaction_date' => '2026-10-20 00:00:00',
    ]));
});

test('user cannot edit another users transaction', function () {
    $transaction = Transaction::factory()->for($this->otherUser)->create();

    $response = $this->actingAs($this->user)->get(route('transactions.edit', $transaction));
    $response->assertForbidden();

    $response = $this->actingAs($this->user)->put(route('transactions.update', $transaction), [
        'type' => $transaction->type,
        'transaction_date' => $transaction->transaction_date->toDateString(),
        'description' => 'Hacked Transaction',
        'amount' => '999.99',
    ]);
    $response->assertForbidden();

    $this->assertDatabaseMissing('transactions', [
        'id' => $transaction->id,
        'description' => 'Hacked Transaction',
    ]);
});

test('user cannot edit transaction with another users category', function () {
    $transaction = Transaction::factory()->for($this->user)->create();

    $response = $this->actingAs($this->user)->put(route('transactions.update', $transaction), [
        'category_id' => $this->otherUserCategory->id, // Another user's category
        'type' => 'income',
        'amount' => '100.50',
        'transaction_date' => '2026-10-15',
        'description' => 'Test Transaction',
    ]);
    $response->assertSessionHasErrors(['category_id']);
});

test('user cannot edit transaction with mismatched type', function () {
    $transaction = Transaction::factory()->for($this->user)->create();

    $response = $this->actingAs($this->user)->put(route('transactions.update', $transaction), [
        'category_id' => $this->incomeCategory->id, // Income category
        'type' => 'expense', // But expense type
        'amount' => '100.50',
        'transaction_date' => '2026-10-15',
        'description' => 'Test Transaction',
    ]);
    $response->assertSessionHasErrors(['category_id']);
});

test('user can delete their own transaction', function () {
    $transaction = Transaction::factory()->for($this->user)->create();

    $response = $this->actingAs($this->user)->delete(route('transactions.destroy', $transaction));
    $response->assertRedirect(route('transactions.index'));
    $response->assertSessionHas('toast');

    $this->assertSoftDeleted($transaction);
});

test('user cannot delete another users transaction', function () {
    $transaction = Transaction::factory()->for($this->otherUser)->create();

    $response = $this->actingAs($this->user)->delete(route('transactions.destroy', $transaction));
    $response->assertForbidden();

    $this->assertNotSoftDeleted($transaction);
});

test('transactions are filtered by type', function () {
    $incomeTransaction = Transaction::factory()->for($this->user)->create(['type' => 'income']);
    $expenseTransaction = Transaction::factory()->for($this->user)->create(['type' => 'expense']);

    // Filter by income
    $response = $this->actingAs($this->user)->get(route('transactions.index', ['type' => 'income']));
    $response->assertInertia(fn ($page) => $page
        ->where('transactions.total', 1)
        ->where('transactions.data.0.id', $incomeTransaction->id));

    // Filter by expense
    $response = $this->actingAs($this->user)->get(route('transactions.index', ['type' => 'expense']));
    $response->assertInertia(fn ($page) => $page
        ->where('transactions.total', 1)
        ->where('transactions.data.0.id', $expenseTransaction->id));
});

test('transactions are filtered by category', function () {
    $category1 = Category::factory()->for($this->user)->create();
    $category2 = Category::factory()->for($this->user)->create();

    $transaction1 = Transaction::factory()->for($this->user)->for($category1)->create();
    $transaction2 = Transaction::factory()->for($this->user)->for($category2)->create();

    // Filter by category1
    $response = $this->actingAs($this->user)->get(route('transactions.index', ['category_id' => $category1->id]));
    $response->assertInertia(fn ($page) => $page
        ->where('transactions.total', 1)
        ->where('transactions.data.0.id', $transaction1->id));
});

test('transactions are filtered by date range', function () {
    $earlyTransaction = Transaction::factory()->for($this->user)->create(['transaction_date' => '2026-01-01']);
    $middleTransaction = Transaction::factory()->for($this->user)->create(['transaction_date' => '2026-06-01']);
    $lateTransaction = Transaction::factory()->for($this->user)->create(['transaction_date' => '2026-12-01']);

    // Filter by date range
    $response = $this->actingAs($this->user)->get(route('transactions.index', [
        'start_date' => '2026-05-01',
        'end_date' => '2026-07-01',
    ]));
    $response->assertInertia(fn ($page) => $page
        ->where('transactions.total', 1)
        ->where('transactions.data.0.id', $middleTransaction->id));
});

test('transactions are paginated', function () {
    // Create more transactions than the pagination limit
    Transaction::factory()->count(20)->for($this->user)->create();

    $response = $this->actingAs($this->user)->get(route('transactions.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('transactions/Index')
        ->has('transactions.data', 15)
        ->where('transactions.total', 20));
});
