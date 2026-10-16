<?php

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('unverified users cannot access financial pages', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
    $this->get(route('transactions.index'))
        ->assertRedirect(route('verification.notice'));
    $this->get(route('categories.index'))
        ->assertRedirect(route('verification.notice'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shows the authenticated users recent transactions and totals', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownTransaction = Transaction::factory()->for($user)->create([
        'type' => 'income',
        'amount' => 125.50,
        'description' => 'Own dashboard transaction',
    ]);
    Transaction::factory()->for($otherUser)->create([
        'type' => 'income',
        'amount' => 900,
        'description' => 'Other user transaction',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('totalIncome', 125.5)
        ->where('totalExpenses', 0)
        ->where('balance', 125.5)
        ->has('recentTransactions', 1)
        ->where('recentTransactions.0.id', $ownTransaction->id)
        ->where('recentTransactions.0.description', 'Own dashboard transaction'));
});

test('dashboard expense breakdown contains only the authenticated users transactions', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownCategory = Category::factory()->for($user)->create(['name' => 'Own category', 'type' => 'expense']);
    $otherCategory = Category::factory()->for($otherUser)->create(['name' => 'Private other category', 'type' => 'expense']);

    Transaction::factory()->for($user)->for($ownCategory)->create([
        'type' => 'expense',
        'amount' => 25,
    ]);
    Transaction::factory()->for($otherUser)->for($otherCategory)->create([
        'type' => 'expense',
        'amount' => 800,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->where('totalExpenses', 25)
        ->has('expensesByCategory', 1)
        ->where('expensesByCategory.0.category', 'Own category')
        ->where('expensesByCategory.0.amount', '25.00'));
});

test('financial insights compare complete current and previous calendar months for the authenticated user', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00:00', 'UTC'));

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $incomeCategory = Category::factory()->for($user)->create(['type' => 'income']);
    $expenseCategory = Category::factory()->for($user)->create(['type' => 'expense']);
    $otherIncomeCategory = Category::factory()->for($otherUser)->create(['type' => 'income']);

    Transaction::factory()->for($user)->for($incomeCategory)->create([
        'type' => 'income', 'amount' => '100.10', 'transaction_date' => '2026-10-01',
    ]);
    Transaction::factory()->for($user)->for($incomeCategory)->create([
        'type' => 'income', 'amount' => '49.90', 'transaction_date' => '2026-10-31',
    ]);
    Transaction::factory()->for($user)->for($expenseCategory)->create([
        'type' => 'expense', 'amount' => '60.25', 'transaction_date' => '2026-10-01',
    ]);
    Transaction::factory()->for($user)->for($expenseCategory)->create([
        'type' => 'expense', 'amount' => '14.75', 'transaction_date' => '2026-10-31',
    ]);
    Transaction::factory()->for($user)->for($incomeCategory)->create([
        'type' => 'income', 'amount' => '40.00', 'transaction_date' => '2026-09-01',
    ]);
    Transaction::factory()->for($user)->for($expenseCategory)->create([
        'type' => 'expense', 'amount' => '25.00', 'transaction_date' => '2026-09-30',
    ]);
    Transaction::factory()->for($user)->for($incomeCategory)->create([
        'type' => 'income', 'amount' => '900.00', 'transaction_date' => '2026-08-31',
    ]);
    Transaction::factory()->for($user)->for($expenseCategory)->create([
        'type' => 'expense', 'amount' => '900.00', 'transaction_date' => '2026-11-01',
    ]);
    Transaction::factory()->for($otherUser)->for($otherIncomeCategory)->create([
        'type' => 'income', 'amount' => '800.00', 'transaction_date' => '2026-10-15',
    ]);
    $deletedTransaction = Transaction::factory()->for($user)->for($expenseCategory)->create([
        'type' => 'expense', 'amount' => '700.00', 'transaction_date' => '2026-10-15',
    ]);
    $deletedTransaction->delete();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('financialInsights.periods.current.key', '2026-10')
            ->where('financialInsights.periods.current.income', '150.00')
            ->where('financialInsights.periods.current.expenses', '75.00')
            ->where('financialInsights.periods.current.net', '75.00')
            ->where('financialInsights.periods.previous.key', '2026-09')
            ->where('financialInsights.periods.previous.income', '40.00')
            ->where('financialInsights.periods.previous.expenses', '25.00')
            ->where('financialInsights.periods.previous.net', '15.00')
            ->where('financialInsights.changes.income.amount', '110.00')
            ->where('financialInsights.changes.income.percentage', 275)
            ->where('financialInsights.changes.expenses.amount', '50.00')
            ->where('financialInsights.changes.expenses.percentage', 200));
});

test('financial insights rank monthly expense categories and handle uncategorized and soft deleted categories', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00:00', 'UTC'));

    $user = User::factory()->create();
    $categories = collect([
        'Rent' => [300, 200],
        'Groceries' => [250, 100],
        'Transport' => [100, 50],
        'New category' => [60, 0],
        'Other' => [40, 90],
        'Small category' => [10, 0],
        'Archived category' => [75, 60],
    ])->map(fn (array $amounts, string $name): Category => Category::factory()->for($user)->create([
        'name' => $name,
        'type' => 'expense',
    ]));

    foreach ($categories as $name => $category) {
        [$current, $previous] = match ($name) {
            'Rent' => [300, 200],
            'Groceries' => [250, 100],
            'Transport' => [100, 50],
            'New category' => [60, 0],
            'Other' => [40, 90],
            'Small category' => [10, 0],
            default => [75, 60],
        };

        if ($current > 0) {
            Transaction::factory()->for($user)->for($category)->create([
                'type' => 'expense', 'amount' => $current, 'transaction_date' => '2026-10-12',
            ]);
        }

        if ($previous > 0) {
            Transaction::factory()->for($user)->for($category)->create([
                'type' => 'expense', 'amount' => $previous, 'transaction_date' => '2026-09-12',
            ]);
        }
    }

    $uncategorized = Transaction::factory()->for($user)->create([
        'category_id' => null,
        'type' => 'expense',
        'amount' => '20.00',
        'transaction_date' => '2026-10-20',
    ]);
    $categories['Archived category']->delete();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('financialInsights.periods.current.expenses', '855.00')
            ->has('financialInsights.top_expense_categories', 5)
            ->where('financialInsights.top_expense_categories.0.name', 'Rent')
            ->where('financialInsights.top_expense_categories.0.share_percentage', 35.1)
            ->where('financialInsights.top_expense_categories.3.name', 'Archived category')
            ->has('financialInsights.increased_expense_categories', 3)
            ->where('financialInsights.increased_expense_categories.0.name', 'Groceries')
            ->where('financialInsights.increased_expense_categories.0.increase_amount', '150.00')
            ->where('financialInsights.increased_expense_categories.2.name', 'New category')
            ->where('financialInsights.increased_expense_categories.2.percentage_change', null));
});

test('financial insights never expose another users category metadata', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00:00', 'UTC'));

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherCategory = Category::factory()->for($otherUser)->create([
        'name' => 'Private category name',
        'type' => 'expense',
    ]);

    Transaction::factory()->for($user)->for($otherCategory)->create([
        'type' => 'expense',
        'amount' => '45.00',
        'transaction_date' => '2026-10-12',
    ]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('financialInsights.periods.current.expenses', '45.00')
            ->where('financialInsights.top_expense_categories.0.name', 'Uncategorized')
            ->where('financialInsights.top_expense_categories.0.color', '#64748b')
            ->where('expensesByCategory.0.category', 'Uncategorized')
            ->where('expensesByCategory.0.amount', '45.00'));
});

test('financial insights include only approaching and exceeded current month budgets and guard zero limits', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00:00', 'UTC'));

    $user = User::factory()->create();
    $scenarios = [
        '79 percent' => ['limit' => '100.00', 'spent' => '79.00', 'status' => null],
        '80 percent' => ['limit' => '100.00', 'spent' => '80.00', 'status' => 'approaching'],
        '100 percent' => ['limit' => '100.00', 'spent' => '100.00', 'status' => 'approaching'],
        '101 percent' => ['limit' => '100.00', 'spent' => '101.00', 'status' => 'exceeded'],
        'zero limit' => ['limit' => '0.00', 'spent' => '5.00', 'status' => 'exceeded'],
        'archived category' => ['limit' => '100.00', 'spent' => '80.00', 'status' => 'approaching'],
    ];

    foreach ($scenarios as $name => $scenario) {
        $category = Category::factory()->for($user)->create(['name' => $name, 'type' => 'expense']);
        Budget::factory()->for($user)->for($category)->create([
            'month' => '2026-10',
            'amount' => $scenario['limit'],
        ]);

        if ($scenario['spent'] !== '0.00') {
            Transaction::factory()->for($user)->for($category)->create([
                'type' => 'expense',
                'amount' => $scenario['spent'],
                'transaction_date' => '2026-10-31',
            ]);
        }

        if ($name === 'archived category') {
            $category->delete();
        }
    }

    $otherUser = User::factory()->create();
    $otherCategory = Category::factory()->for($otherUser)->create([
        'name' => 'Private user budget',
        'type' => 'expense',
    ]);
    Budget::factory()->for($otherUser)->for($otherCategory)->create([
        'month' => '2026-10',
        'amount' => '100.00',
    ]);
    Transaction::factory()->for($otherUser)->for($otherCategory)->create([
        'type' => 'expense',
        'amount' => '150.00',
        'transaction_date' => '2026-10-31',
    ]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('financialInsights.budget_alerts', 5)
            ->where('financialInsights.budget_alerts.0.category_name', '101 percent')
            ->where('financialInsights.budget_alerts.0.status', 'exceeded')
            ->where('financialInsights.budget_alerts.2.category_name', '80 percent')
            ->where('financialInsights.budget_alerts.2.status', 'approaching')
            ->where('financialInsights.budget_alerts.4.category_name', 'archived category')
            ->where('financialInsights.budget_alerts.4.status', 'approaching')
            ->where('financialInsights.budget_alerts.1.usage_percentage', null)
            ->where('financialInsights.budget_alerts.1.remaining_amount', '-5.00'));
});

test('financial insights use zero values and no alerts for users without transactions or budgets', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00:00', 'UTC'));
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('financialInsights.periods.current.income', '0.00')
            ->where('financialInsights.periods.current.expenses', '0.00')
            ->where('financialInsights.periods.current.net', '0.00')
            ->where('financialInsights.changes.income.percentage', null)
            ->where('financialInsights.changes.expenses.percentage', null)
            ->has('financialInsights.top_expense_categories', 0)
            ->has('financialInsights.increased_expense_categories', 0)
            ->has('financialInsights.budget_alerts', 0));
});
