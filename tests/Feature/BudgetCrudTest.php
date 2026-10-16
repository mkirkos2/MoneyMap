<?php

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->expenseCategory = Category::factory()->for($this->user)->create(['type' => 'expense']);
    $this->otherExpenseCategory = Category::factory()->for($this->otherUser)->create(['type' => 'expense']);
});

test('guest cannot access budgets', function () {
    $this->get(route('budgets.index'))->assertRedirect(route('login'));
    $this->get(route('budgets.create'))->assertRedirect(route('login'));
    $this->post(route('budgets.store'), [])->assertRedirect(route('login'));
});

test('unverified users cannot access budgets', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('budgets.index'))
        ->assertRedirect(route('verification.notice'));
});

test('user can view an empty budgets page and only their expense categories', function () {
    Category::factory()->for($this->user)->create(['type' => 'income']);
    Category::factory()->for($this->otherUser)->create(['type' => 'expense']);

    $this->actingAs($this->user)->get(route('budgets.index'))
        ->assertInertia(fn ($page) => $page
            ->component('budgets/Index')
            ->has('budgets.data', 0)
            ->where('budgets.total', 0)
            ->has('categories', 1)
            ->where('categories.0.id', $this->expenseCategory->id)
            ->where('filters.month', null));
});

test('user can create a budget for an expense category', function () {
    $this->actingAs($this->user)->get(route('budgets.create'))
        ->assertInertia(fn ($page) => $page
            ->component('budgets/Create')
            ->where('month', now()->format('Y-m'))
            ->has('categories', 1));

    $response = $this->post(route('budgets.store'), [
        'category_id' => $this->expenseCategory->id,
        'month' => '2026-10',
        'amount' => '450.00',
    ]);

    $response->assertRedirect(route('budgets.index', ['month' => '2026-10']))
        ->assertSessionHas('toast');

    $this->assertDatabaseHas('budgets', [
        'user_id' => $this->user->id,
        'category_id' => $this->expenseCategory->id,
        'month' => '2026-10',
        'amount' => '450.00',
    ]);
});

test('user can update and delete their own budget', function () {
    $budget = Budget::factory()->for($this->user)->for($this->expenseCategory)->create([
        'month' => '2026-10',
        'amount' => '450.00',
    ]);

    $this->actingAs($this->user)->get(route('budgets.edit', $budget))
        ->assertInertia(fn ($page) => $page
            ->component('budgets/Edit')
            ->where('budget.id', $budget->id)
            ->where('budget.month', '2026-10')
            ->where('budget.amount', '450.00'));

    $this->put(route('budgets.update', $budget), [
        'category_id' => $this->expenseCategory->id,
        'month' => '2026-11',
        'amount' => '600.25',
    ])->assertRedirect(route('budgets.index', ['month' => '2026-11']))
        ->assertSessionHas('toast');

    $this->assertDatabaseHas('budgets', [
        'id' => $budget->id,
        'user_id' => $this->user->id,
        'month' => '2026-11',
        'amount' => '600.25',
    ]);

    $this->delete(route('budgets.destroy', $budget))
        ->assertRedirect(route('budgets.index', ['month' => '2026-11']))
        ->assertSessionHas('toast');

    $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
});

test('user cannot create duplicate budgets for the same category and month', function () {
    Budget::factory()->for($this->user)->for($this->expenseCategory)->create(['month' => '2026-10']);

    $this->actingAs($this->user)->post(route('budgets.store'), [
        'category_id' => $this->expenseCategory->id,
        'month' => '2026-10',
        'amount' => '500.00',
    ])->assertSessionHasErrors(['month']);

    $this->assertDatabaseCount('budgets', 1);
});

test('user cannot update a budget into a duplicate category and month', function () {
    $otherCategory = Category::factory()->for($this->user)->create(['type' => 'expense']);
    $budget = Budget::factory()->for($this->user)->for($this->expenseCategory)->create(['month' => '2026-10']);
    Budget::factory()->for($this->user)->for($otherCategory)->create(['month' => '2026-10']);

    $this->actingAs($this->user)->put(route('budgets.update', $budget), [
        'category_id' => $otherCategory->id,
        'month' => '2026-10',
        'amount' => '250.00',
    ])->assertSessionHasErrors(['month']);

    $this->assertDatabaseHas('budgets', [
        'id' => $budget->id,
        'category_id' => $this->expenseCategory->id,
        'month' => '2026-10',
    ]);
});

test('users can create budgets for the same category and month independently', function () {
    Budget::factory()->for($this->user)->for($this->expenseCategory)->create(['month' => '2026-10']);

    $this->actingAs($this->otherUser)->post(route('budgets.store'), [
        'category_id' => $this->otherExpenseCategory->id,
        'month' => '2026-10',
        'amount' => '200.00',
    ])->assertRedirect(route('budgets.index', ['month' => '2026-10']));

    $this->assertDatabaseCount('budgets', 2);
});

test('budget validation rejects income categories, other users categories, and invalid amounts', function () {
    $incomeCategory = Category::factory()->for($this->user)->create(['type' => 'income']);
    $deletedCategory = Category::factory()->for($this->user)->create(['type' => 'expense']);
    $deletedCategory->delete();

    foreach ([$incomeCategory, $this->otherExpenseCategory, $deletedCategory] as $category) {
        $this->actingAs($this->user)->post(route('budgets.store'), [
            'category_id' => $category->id,
            'month' => '2026-10',
            'amount' => '100.00',
        ])->assertSessionHasErrors(['category_id']);
    }

    $this->actingAs($this->user)->post(route('budgets.store'), [
        'category_id' => $this->expenseCategory->id,
        'month' => 'October 2026',
        'amount' => '0',
    ])->assertSessionHasErrors(['month', 'amount']);

    $this->post(route('budgets.store'), [
        'category_id' => [$this->expenseCategory->id],
        'month' => ['2026-10'],
        'amount' => '100.00',
    ])->assertSessionHasErrors(['category_id', 'month']);
});

test('user cannot view, update, or delete another users budget', function () {
    $otherBudget = Budget::factory()->for($this->otherUser)->for($this->otherExpenseCategory)->create();

    $this->actingAs($this->user)->get(route('budgets.edit', $otherBudget))->assertForbidden();
    $this->put(route('budgets.update', $otherBudget), [
        'category_id' => $this->expenseCategory->id,
        'month' => $otherBudget->month,
        'amount' => '999.00',
    ])->assertForbidden();
    $this->delete(route('budgets.destroy', $otherBudget))->assertForbidden();

    $this->assertDatabaseHas('budgets', ['id' => $otherBudget->id, 'amount' => $otherBudget->amount]);
});

test('monthly spending includes both month boundaries and excludes other users, categories, income, and soft deleted transactions', function () {
    $budget = Budget::factory()->for($this->user)->for($this->expenseCategory)->create([
        'month' => '2026-10',
        'amount' => '100.00',
    ]);

    Transaction::factory()->for($this->user)->for($this->expenseCategory)->create([
        'type' => 'expense', 'amount' => '10.00', 'transaction_date' => '2026-10-01',
    ]);
    Transaction::factory()->for($this->user)->for($this->expenseCategory)->create([
        'type' => 'expense', 'amount' => '20.00', 'transaction_date' => '2026-10-31',
    ]);
    Transaction::factory()->for($this->user)->for($this->expenseCategory)->create([
        'type' => 'expense', 'amount' => '900.00', 'transaction_date' => '2026-09-30',
    ]);
    Transaction::factory()->for($this->user)->for($this->expenseCategory)->create([
        'type' => 'expense', 'amount' => '900.00', 'transaction_date' => '2026-11-01',
    ]);
    Transaction::factory()->for($this->user)->for($this->expenseCategory)->create([
        'type' => 'income', 'amount' => '800.00', 'transaction_date' => '2026-10-15',
    ]);
    Transaction::factory()->for($this->user)->for($this->otherExpenseCategory)->create([
        'type' => 'expense', 'amount' => '700.00', 'transaction_date' => '2026-10-15',
    ]);
    Transaction::factory()->for($this->otherUser)->for($this->otherExpenseCategory)->create([
        'type' => 'expense', 'amount' => '600.00', 'transaction_date' => '2026-10-15',
    ]);
    $deletedTransaction = Transaction::factory()->for($this->user)->for($this->expenseCategory)->create([
        'type' => 'expense', 'amount' => '500.00', 'transaction_date' => '2026-10-15',
    ]);
    $deletedTransaction->delete();

    $this->actingAs($this->user)->get(route('budgets.index', ['month' => '2026-10']))
        ->assertInertia(fn ($page) => $page
            ->has('budgets.data', 1)
            ->where('budgets.data.0.id', $budget->id)
            ->where('budgets.data.0.spent_amount', 30)
            ->where('budgets.data.0.remaining_amount', 70)
            ->where('budgets.data.0.usage_percentage', 30));
});

test('budgets with no spending report zero and overspending is reported accurately', function () {
    $zeroSpendBudget = Budget::factory()->for($this->user)->for($this->expenseCategory)->create([
        'month' => '2026-09', 'amount' => '100.00',
    ]);
    $overBudget = Budget::factory()->for($this->user)->for($this->expenseCategory)->create([
        'month' => '2026-10', 'amount' => '50.00',
    ]);

    Transaction::factory()->for($this->user)->for($this->expenseCategory)->create([
        'type' => 'expense', 'amount' => '65.00', 'transaction_date' => '2026-10-12',
    ]);

    $this->actingAs($this->user)->get(route('budgets.index'))
        ->assertInertia(fn ($page) => $page
            ->has('budgets.data', 2)
            ->where('budgets.data.0.id', $overBudget->id)
            ->where('budgets.data.0.spent_amount', 65)
            ->where('budgets.data.0.remaining_amount', -15)
            ->where('budgets.data.0.usage_percentage', 130)
            ->where('budgets.data.1.id', $zeroSpendBudget->id)
            ->where('budgets.data.1.spent_amount', 0)
            ->where('budgets.data.1.remaining_amount', 100)
            ->where('budgets.data.1.usage_percentage', 0));
});

test('soft deleted categories remain visible for existing budgets and can be retained when editing', function () {
    $budget = Budget::factory()->for($this->user)->for($this->expenseCategory)->create(['month' => '2026-10']);
    $this->expenseCategory->delete();

    $this->actingAs($this->user)->get(route('budgets.index', ['month' => '2026-10']))
        ->assertInertia(fn ($page) => $page
            ->where('budgets.data.0.category.name', $this->expenseCategory->name)
            ->where('budgets.data.0.category.deleted_at', $this->expenseCategory->deleted_at->toISOString()));

    $this->put(route('budgets.update', $budget), [
        'category_id' => $this->expenseCategory->id,
        'month' => '2026-10',
        'amount' => '250.00',
    ])->assertRedirect(route('budgets.index', ['month' => '2026-10']));

    $this->assertDatabaseHas('budgets', ['id' => $budget->id, 'category_id' => $this->expenseCategory->id]);
});

test('budgets are filtered by month and paginated with the filter preserved', function () {
    Budget::factory()->for($this->user)->for($this->expenseCategory)->create(['month' => '2026-10']);
    Budget::factory()->for($this->user)->for(Category::factory()->for($this->user)->create(['type' => 'expense']))->create(['month' => '2026-11']);

    $this->actingAs($this->user)->get(route('budgets.index', ['month' => '2026-10']))
        ->assertInertia(fn ($page) => $page
            ->has('budgets.data', 1)
            ->where('budgets.data.0.month', '2026-10')
            ->where('filters.month', '2026-10'));

    for ($index = 0; $index < 12; $index++) {
        $category = Category::factory()->for($this->user)->create(['type' => 'expense']);
        Budget::factory()->for($this->user)->for($category)->create(['month' => '2026-10']);
    }

    $this->get(route('budgets.index', ['month' => '2026-10']))
        ->assertInertia(fn ($page) => $page
            ->where('budgets.total', 13)
            ->where('budgets.last_page', 2)
            ->where('budgets.next_page_url', fn ($url) => str_contains($url, 'month=2026-10')));
});

test('the budgets page distinguishes an empty page from an empty budget list', function () {
    for ($index = 0; $index < 13; $index++) {
        $category = Category::factory()->for($this->user)->create(['type' => 'expense']);
        Budget::factory()->for($this->user)->for($category)->create(['month' => '2026-10']);
    }

    $this->actingAs($this->user)->get(route('budgets.index'))
        ->assertInertia(fn ($page) => $page
            ->component('budgets/Index')
            ->has('budgets.data', 12)
            ->where('budgets.total', 13));

    $this->get(route('budgets.index', ['page' => 3]))
        ->assertInertia(fn ($page) => $page
            ->component('budgets/Index')
            ->has('budgets.data', 0)
            ->where('budgets.total', 13)
            ->where('budgets.last_page', 2));
});
