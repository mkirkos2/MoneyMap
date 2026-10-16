<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Category;
use App\Models\SavingsGoal;
use App\Models\SavingsGoalEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Support\MoneyDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExampleDemoSeeder extends Seeder
{
    /**
     * Seed a repeatable interview demo dataset for the explicitly selected Example account.
     */
    public function run(): void
    {
        if (! app()->environment('local') || DB::connection()->getDatabaseName() !== 'moneymap') {
            throw new RuntimeException('The Example demo seeder is restricted to the local moneymap database.');
        }

        $email = getenv('MONEYMAP_DEMO_EMAIL');

        if (! is_string($email) || trim($email) === '') {
            throw new RuntimeException('Set MONEYMAP_DEMO_EMAIL for this command before running the Example demo seeder.');
        }

        $user = User::query()
            ->where('email', trim($email))
            ->where('name', 'Example')
            ->first();

        if ($user === null) {
            throw new RuntimeException('No existing user named Example matches the provided demo email.');
        }

        $monthStart = CarbonImmutable::now(config('app.timezone'))->startOfMonth();
        $stats = [
            'categories_created' => 0,
            'transactions_created' => 0,
            'budgets_created' => 0,
            'savings_goals_created' => 0,
            'savings_goal_entries_created' => 0,
        ];

        DB::transaction(function () use ($user, $monthStart, &$stats): void {
            $categories = $this->seedCategories($user, $stats);
            $this->seedTransactions($user, $categories, $monthStart, $stats);
            $this->seedBudgets($user, $categories, $monthStart, $stats);
            $goals = $this->seedGoals($user, $monthStart, $stats);
            $this->seedGoalEntries($goals, $monthStart, $stats);
        });

        $this->command?->info('Example demo data is ready.');

        foreach ($stats as $section => $count) {
            $this->command?->line(str_replace('_', ' ', ucfirst($section)).': '.$count);
        }
    }

    /**
     * @param  array<string, int>  $stats
     * @return array<string, Category>
     */
    private function seedCategories(User $user, array &$stats): array
    {
        $definitions = [
            'rent' => ['name' => 'Rent', 'type' => 'expense', 'color' => '#64748b', 'icon' => 'HOME'],
            'groceries' => ['name' => 'Groceries', 'type' => 'expense', 'color' => '#22c55e', 'icon' => 'FOOD'],
            'dining' => ['name' => 'Dining & Coffee', 'type' => 'expense', 'color' => '#f59e0b', 'icon' => 'CAFE'],
            'transport' => ['name' => 'Transport', 'type' => 'expense', 'color' => '#3b82f6', 'icon' => 'MOVE'],
            'utilities' => ['name' => 'Utilities', 'type' => 'expense', 'color' => '#0ea5e9', 'icon' => 'BILLS'],
            'health' => ['name' => 'Health', 'type' => 'expense', 'color' => '#ef4444', 'icon' => 'CARE'],
            'subscriptions' => ['name' => 'Subscriptions', 'type' => 'expense', 'color' => '#8b5cf6', 'icon' => 'SUBS'],
            'shopping' => ['name' => 'Shopping', 'type' => 'expense', 'color' => '#ec4899', 'icon' => 'SHOP'],
            'entertainment' => ['name' => 'Entertainment', 'type' => 'expense', 'color' => '#14b8a6', 'icon' => 'FUN'],
            'personal_care' => ['name' => 'Personal Care', 'type' => 'expense', 'color' => '#f97316', 'icon' => 'CARE'],
            'salary' => ['name' => 'Salary', 'type' => 'income', 'color' => '#16a34a', 'icon' => 'PAY'],
            'freelance' => ['name' => 'Freelance', 'type' => 'income', 'color' => '#84cc16', 'icon' => 'SIDE'],
        ];
        $categories = [];

        foreach ($definitions as $key => $definition) {
            $category = Category::withTrashed()
                ->whereBelongsTo($user)
                ->where('name', $definition['name'])
                ->where('type', $definition['type'])
                ->first();

            if ($category?->trashed()) {
                throw new RuntimeException('A demo category is soft-deleted. Restore or rename it manually before seeding.');
            }

            if ($category === null) {
                $category = $user->categories()->create($definition);
                $stats['categories_created']++;
            }

            $categories[$key] = $category;
        }

        return $categories;
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, int>  $stats
     */
    private function seedTransactions(User $user, array $categories, CarbonImmutable $monthStart, array &$stats): void
    {
        $rows = [
            ['offset' => 3, 'day' => 1, 'category' => 'salary', 'type' => 'income', 'amount' => '1850.00', 'description' => 'Monthly salary', 'notes' => 'Net monthly salary.'],
            ['offset' => 3, 'day' => 2, 'category' => 'rent', 'type' => 'expense', 'amount' => '620.00', 'description' => 'Rent payment', 'notes' => 'Monthly apartment rent.'],
            ['offset' => 3, 'day' => 4, 'category' => 'groceries', 'type' => 'expense', 'amount' => '54.00', 'description' => 'Weekly groceries', 'notes' => 'Food and household essentials.'],
            ['offset' => 3, 'day' => 5, 'category' => 'dining', 'type' => 'expense', 'amount' => '16.00', 'description' => 'Coffee and breakfast', 'notes' => null],
            ['offset' => 3, 'day' => 3, 'category' => 'transport', 'type' => 'expense', 'amount' => '33.00', 'description' => 'Monthly transit pass', 'notes' => 'Athens public transport.'],
            ['offset' => 3, 'day' => 12, 'category' => 'groceries', 'type' => 'expense', 'amount' => '83.00', 'description' => 'Market and supermarket', 'notes' => null],
            ['offset' => 3, 'day' => 11, 'category' => 'dining', 'type' => 'expense', 'amount' => '39.00', 'description' => 'Dinner with a friend', 'notes' => null],
            ['offset' => 3, 'day' => 15, 'category' => 'utilities', 'type' => 'expense', 'amount' => '68.00', 'description' => 'Electricity bill', 'notes' => 'Monthly utility payment.'],
            ['offset' => 3, 'day' => 20, 'category' => 'health', 'type' => 'expense', 'amount' => '21.00', 'description' => 'Pharmacy essentials', 'notes' => null],
            ['offset' => 3, 'day' => 18, 'category' => 'shopping', 'type' => 'expense', 'amount' => '34.00', 'description' => 'Bookstore purchase', 'notes' => null],
            ['offset' => 3, 'day' => 26, 'category' => 'dining', 'type' => 'expense', 'amount' => '28.00', 'description' => 'Lunch near the office', 'notes' => null],
            ['offset' => 3, 'day' => 24, 'category' => 'groceries', 'type' => 'expense', 'amount' => '112.00', 'description' => 'Monthly pantry restock', 'notes' => null],
            ['offset' => 3, 'day' => 27, 'category' => 'entertainment', 'type' => 'expense', 'amount' => '44.00', 'description' => 'Cinema tickets', 'notes' => null],

            ['offset' => 2, 'day' => 1, 'category' => 'salary', 'type' => 'income', 'amount' => '1850.00', 'description' => 'Monthly salary', 'notes' => 'Net monthly salary.'],
            ['offset' => 2, 'day' => 2, 'category' => 'rent', 'type' => 'expense', 'amount' => '620.00', 'description' => 'Rent payment', 'notes' => 'Monthly apartment rent.'],
            ['offset' => 2, 'day' => 3, 'category' => 'groceries', 'type' => 'expense', 'amount' => '73.00', 'description' => 'Weekly groceries', 'notes' => 'Food and household essentials.'],
            ['offset' => 2, 'day' => 4, 'category' => 'dining', 'type' => 'expense', 'amount' => '32.00', 'description' => 'Coffee and breakfast', 'notes' => null],
            ['offset' => 2, 'day' => 2, 'category' => 'transport', 'type' => 'expense', 'amount' => '40.00', 'description' => 'Monthly transit pass', 'notes' => 'Athens public transport.'],
            ['offset' => 2, 'day' => 13, 'category' => 'groceries', 'type' => 'expense', 'amount' => '91.00', 'description' => 'Market and supermarket', 'notes' => null],
            ['offset' => 2, 'day' => 16, 'category' => 'dining', 'type' => 'expense', 'amount' => '47.00', 'description' => 'Dinner with a friend', 'notes' => null],
            ['offset' => 2, 'day' => 18, 'category' => 'utilities', 'type' => 'expense', 'amount' => '94.00', 'description' => 'Electricity bill', 'notes' => 'Monthly utility payment.'],
            ['offset' => 2, 'day' => 10, 'category' => 'subscriptions', 'type' => 'expense', 'amount' => '29.00', 'description' => 'Streaming and music plans', 'notes' => null],
            ['offset' => 2, 'day' => 21, 'category' => 'health', 'type' => 'expense', 'amount' => '18.00', 'description' => 'Pharmacy essentials', 'notes' => null],
            ['offset' => 2, 'day' => 23, 'category' => 'shopping', 'type' => 'expense', 'amount' => '86.00', 'description' => 'Work shirt and basics', 'notes' => null],
            ['offset' => 2, 'day' => 14, 'category' => 'entertainment', 'type' => 'expense', 'amount' => '49.00', 'description' => 'Museum and exhibition', 'notes' => null],
            ['offset' => 2, 'day' => 25, 'category' => 'groceries', 'type' => 'expense', 'amount' => '62.00', 'description' => 'Fresh produce and pantry items', 'notes' => null],
            ['offset' => 2, 'day' => 29, 'category' => 'dining', 'type' => 'expense', 'amount' => '18.00', 'description' => 'Takeaway dinner', 'notes' => null],
            ['offset' => 2, 'day' => 19, 'category' => 'freelance', 'type' => 'income', 'amount' => '180.00', 'description' => 'Freelance design work', 'notes' => 'Small one-off project.'],

            ['offset' => 1, 'day' => 1, 'category' => 'salary', 'type' => 'income', 'amount' => '1850.00', 'description' => 'Monthly salary', 'notes' => 'Net monthly salary.'],
            ['offset' => 1, 'day' => 2, 'category' => 'rent', 'type' => 'expense', 'amount' => '620.00', 'description' => 'Rent payment', 'notes' => 'Monthly apartment rent.'],
            ['offset' => 1, 'day' => 4, 'category' => 'groceries', 'type' => 'expense', 'amount' => '98.00', 'description' => 'Weekly groceries', 'notes' => 'Food and household essentials.'],
            ['offset' => 1, 'day' => 6, 'category' => 'dining', 'type' => 'expense', 'amount' => '45.00', 'description' => 'Coffee and breakfast', 'notes' => null],
            ['offset' => 1, 'day' => 3, 'category' => 'transport', 'type' => 'expense', 'amount' => '39.00', 'description' => 'Monthly transit pass', 'notes' => 'Athens public transport.'],
            ['offset' => 1, 'day' => 12, 'category' => 'groceries', 'type' => 'expense', 'amount' => '74.00', 'description' => 'Market and supermarket', 'notes' => null],
            ['offset' => 1, 'day' => 20, 'category' => 'dining', 'type' => 'expense', 'amount' => '29.00', 'description' => 'Dinner with a friend', 'notes' => null],
            ['offset' => 1, 'day' => 18, 'category' => 'utilities', 'type' => 'expense', 'amount' => '103.00', 'description' => 'Electricity bill', 'notes' => 'Monthly utility payment.'],
            ['offset' => 1, 'day' => 10, 'category' => 'subscriptions', 'type' => 'expense', 'amount' => '29.00', 'description' => 'Streaming and music plans', 'notes' => null],
            ['offset' => 1, 'day' => 21, 'category' => 'health', 'type' => 'expense', 'amount' => '37.00', 'description' => 'GP consultation and pharmacy', 'notes' => null],
            ['offset' => 1, 'day' => 23, 'category' => 'shopping', 'type' => 'expense', 'amount' => '135.00', 'description' => 'Autumn shoes', 'notes' => null],
            ['offset' => 1, 'day' => 14, 'category' => 'entertainment', 'type' => 'expense', 'amount' => '70.00', 'description' => 'Concert ticket', 'notes' => null],
            ['offset' => 1, 'day' => 26, 'category' => 'personal_care', 'type' => 'expense', 'amount' => '31.00', 'description' => 'Haircut', 'notes' => null],
            ['offset' => 1, 'day' => 26, 'category' => 'groceries', 'type' => 'expense', 'amount' => '108.00', 'description' => 'Monthly pantry restock', 'notes' => null],
            ['offset' => 1, 'day' => 16, 'category' => 'freelance', 'type' => 'income', 'amount' => '140.00', 'description' => 'Freelance copy editing', 'notes' => 'One-off editing assignment.'],

            ['offset' => 0, 'day' => 1, 'category' => 'salary', 'type' => 'income', 'amount' => '1850.00', 'description' => 'Monthly salary', 'notes' => 'Net monthly salary.'],
            ['offset' => 0, 'day' => 2, 'category' => 'rent', 'type' => 'expense', 'amount' => '620.00', 'description' => 'Rent payment', 'notes' => 'Monthly apartment rent.'],
            ['offset' => 0, 'day' => 3, 'category' => 'groceries', 'type' => 'expense', 'amount' => '132.00', 'description' => 'Weekly groceries', 'notes' => 'Food and household essentials.'],
            ['offset' => 0, 'day' => 4, 'category' => 'dining', 'type' => 'expense', 'amount' => '37.00', 'description' => 'Coffee and breakfast', 'notes' => null],
            ['offset' => 0, 'day' => 1, 'category' => 'transport', 'type' => 'expense', 'amount' => '30.00', 'description' => 'Monthly transit pass', 'notes' => 'Athens public transport.'],
            ['offset' => 0, 'day' => 8, 'category' => 'groceries', 'type' => 'expense', 'amount' => '85.00', 'description' => 'Market and supermarket', 'notes' => null],
            ['offset' => 0, 'day' => 11, 'category' => 'dining', 'type' => 'expense', 'amount' => '70.00', 'description' => 'Dinner with a friend', 'notes' => null],
            ['offset' => 0, 'day' => 5, 'category' => 'utilities', 'type' => 'expense', 'amount' => '84.00', 'description' => 'Electricity bill', 'notes' => 'Monthly utility payment.'],
            ['offset' => 0, 'day' => 8, 'category' => 'subscriptions', 'type' => 'expense', 'amount' => '36.00', 'description' => 'Streaming and music plans', 'notes' => null],
            ['offset' => 0, 'day' => 7, 'category' => 'health', 'type' => 'expense', 'amount' => '32.00', 'description' => 'Pharmacy essentials', 'notes' => null],
            ['offset' => 0, 'day' => 14, 'category' => 'shopping', 'type' => 'expense', 'amount' => '98.00', 'description' => 'Kitchen supplies', 'notes' => null],
            ['offset' => 0, 'day' => 9, 'category' => 'transport', 'type' => 'expense', 'amount' => '12.00', 'description' => 'Taxi after late meeting', 'notes' => null],
            ['offset' => 0, 'day' => 13, 'category' => 'groceries', 'type' => 'expense', 'amount' => '83.00', 'description' => 'Fresh produce and pantry items', 'notes' => null],
            ['offset' => 0, 'day' => 10, 'category' => 'freelance', 'type' => 'income', 'amount' => '220.00', 'description' => 'Freelance design work', 'notes' => 'Small one-off project.'],
        ];

        foreach ($rows as $row) {
            $transactionDate = $monthStart
                ->subMonthsNoOverflow($row['offset'])
                ->addDays($row['day'] - 1)
                ->toDateString();
            $category = $categories[$row['category']];
            $existingTransaction = Transaction::withTrashed()
                ->whereBelongsTo($user)
                ->where('category_id', $category->id)
                ->where('type', $row['type'])
                ->whereDate('transaction_date', $transactionDate)
                ->where('description', $row['description'])
                ->first();

            if ($existingTransaction !== null) {
                if ($existingTransaction->trashed()) {
                    throw new RuntimeException('A matching demo transaction was soft-deleted; no duplicate was created.');
                }

                if (MoneyDecimal::toCents((string) $existingTransaction->amount) !== MoneyDecimal::toCents($row['amount'])) {
                    throw new RuntimeException('A matching demo transaction has a different amount; review it manually before seeding.');
                }

                continue;
            }

            $user->transactions()->create([
                'category_id' => $category->id,
                'type' => $row['type'],
                'amount' => $row['amount'],
                'transaction_date' => $transactionDate,
                'description' => $row['description'],
                'notes' => $row['notes'],
            ]);
            $stats['transactions_created']++;
        }
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, int>  $stats
     */
    private function seedBudgets(User $user, array $categories, CarbonImmutable $monthStart, array &$stats): void
    {
        $budgets = [
            'rent' => '650.00',
            'groceries' => '280.00',
            'dining' => '110.00',
            'utilities' => '100.00',
            'transport' => '100.00',
            'health' => '100.00',
            'subscriptions' => '60.00',
            'shopping' => '150.00',
        ];
        $month = $monthStart->format('Y-m');

        foreach ($budgets as $categoryKey => $amount) {
            $category = $categories[$categoryKey];
            $budget = Budget::query()
                ->whereBelongsTo($user)
                ->where('category_id', $category->id)
                ->where('month', $month)
                ->first();

            if ($budget !== null) {
                if (MoneyDecimal::toCents((string) $budget->amount) !== MoneyDecimal::toCents($amount)) {
                    throw new RuntimeException('A current-month demo budget has a different amount; review it manually before seeding.');
                }

                continue;
            }

            $user->budgets()->create([
                'category_id' => $category->id,
                'month' => $month,
                'amount' => $amount,
            ]);
            $stats['budgets_created']++;
        }
    }

    /**
     * @param  array<string, int>  $stats
     * @return array<string, SavingsGoal>
     */
    private function seedGoals(User $user, CarbonImmutable $monthStart, array &$stats): array
    {
        $today = CarbonImmutable::now(config('app.timezone'));
        $definitions = [
            'emergency' => [
                'name' => 'Emergency Fund',
                'target_amount' => '3000.00',
                'target_date' => $today->addYear()->endOfMonth()->toDateString(),
                'description' => 'A reserve for unexpected costs and a stronger financial safety net.',
            ],
            'vacation' => [
                'name' => 'Vacation Fund',
                'target_amount' => '1200.00',
                'target_date' => $today->addMonthsNoOverflow(9)->endOfMonth()->toDateString(),
                'description' => 'Set aside money for a planned summer break.',
            ],
            'laptop' => [
                'name' => 'Laptop Upgrade',
                'target_amount' => '1500.00',
                'target_date' => $today->addMonthsNoOverflow(5)->endOfMonth()->toDateString(),
                'description' => 'Save toward a reliable laptop for work and freelance projects.',
            ],
            'home' => [
                'name' => 'Home Deposit',
                'target_amount' => '5000.00',
                'target_date' => null,
                'description' => 'Long-term savings toward a future home deposit.',
            ],
        ];
        $goals = [];

        foreach ($definitions as $key => $definition) {
            $goal = SavingsGoal::withTrashed()
                ->whereBelongsTo($user)
                ->where('name', $definition['name'])
                ->first();

            if ($goal?->trashed()) {
                throw new RuntimeException('A demo savings goal is soft-deleted; review it manually before seeding.');
            }

            if ($goal !== null) {
                if (MoneyDecimal::toCents((string) $goal->target_amount) !== MoneyDecimal::toCents($definition['target_amount'])) {
                    throw new RuntimeException('A demo savings goal has a different target amount; review it manually before seeding.');
                }
            } else {
                $goal = $user->savingsGoals()->create($definition);
                $stats['savings_goals_created']++;
            }

            $goals[$key] = $goal;
        }

        return $goals;
    }

    /**
     * @param  array<string, SavingsGoal>  $goals
     * @param  array<string, int>  $stats
     */
    private function seedGoalEntries(array $goals, CarbonImmutable $monthStart, array &$stats): void
    {
        $rows = [
            ['goal' => 'emergency', 'offset' => 3, 'day' => 8, 'type' => 'contribution', 'amount' => '300.00', 'note' => 'Monthly emergency fund contribution.'],
            ['goal' => 'emergency', 'offset' => 2, 'day' => 8, 'type' => 'contribution', 'amount' => '350.00', 'note' => 'Monthly emergency fund contribution.'],
            ['goal' => 'emergency', 'offset' => 2, 'day' => 22, 'type' => 'withdrawal', 'amount' => '75.00', 'note' => 'Unexpected car repair.'],
            ['goal' => 'emergency', 'offset' => 1, 'day' => 8, 'type' => 'contribution', 'amount' => '300.00', 'note' => 'Monthly emergency fund contribution.'],
            ['goal' => 'emergency', 'offset' => 0, 'day' => 8, 'type' => 'contribution', 'amount' => '200.00', 'note' => 'Monthly emergency fund contribution.'],
            ['goal' => 'vacation', 'offset' => 3, 'day' => 12, 'type' => 'contribution', 'amount' => '150.00', 'note' => 'Vacation savings contribution.'],
            ['goal' => 'vacation', 'offset' => 2, 'day' => 14, 'type' => 'contribution', 'amount' => '200.00', 'note' => 'Vacation savings contribution.'],
            ['goal' => 'vacation', 'offset' => 1, 'day' => 14, 'type' => 'contribution', 'amount' => '180.00', 'note' => 'Vacation savings contribution.'],
            ['goal' => 'vacation', 'offset' => 0, 'day' => 12, 'type' => 'contribution', 'amount' => '120.00', 'note' => 'Vacation savings contribution.'],
            ['goal' => 'laptop', 'offset' => 2, 'day' => 20, 'type' => 'contribution', 'amount' => '200.00', 'note' => 'Laptop fund contribution.'],
            ['goal' => 'laptop', 'offset' => 1, 'day' => 22, 'type' => 'contribution', 'amount' => '250.00', 'note' => 'Laptop fund contribution.'],
            ['goal' => 'laptop', 'offset' => 0, 'day' => 10, 'type' => 'contribution', 'amount' => '300.00', 'note' => 'Laptop fund contribution.'],
            ['goal' => 'home', 'offset' => 3, 'day' => 28, 'type' => 'contribution', 'amount' => '300.00', 'note' => 'Long-term home deposit savings.'],
            ['goal' => 'home', 'offset' => 1, 'day' => 27, 'type' => 'contribution', 'amount' => '400.00', 'note' => 'Long-term home deposit savings.'],
            ['goal' => 'home', 'offset' => 0, 'day' => 13, 'type' => 'contribution', 'amount' => '200.00', 'note' => 'Long-term home deposit savings.'],
        ];

        foreach ($rows as $row) {
            $goal = $goals[$row['goal']];
            $entryDate = $monthStart
                ->subMonthsNoOverflow($row['offset'])
                ->addDays($row['day'] - 1)
                ->toDateString();
            $entry = $goal->entries()
                ->where('type', $row['type'])
                ->whereDate('entry_date', $entryDate)
                ->where('note', $row['note'])
                ->first();

            if ($entry instanceof SavingsGoalEntry) {
                if (MoneyDecimal::toCents((string) $entry->amount) !== MoneyDecimal::toCents($row['amount'])) {
                    throw new RuntimeException('A demo savings movement has a different amount; review it manually before seeding.');
                }

                continue;
            }

            $lockedGoal = SavingsGoal::query()->whereKey($goal->getKey())->lockForUpdate()->firstOrFail();

            if ($row['type'] === 'withdrawal') {
                $contributed = MoneyDecimal::toCents((string) ($lockedGoal->entries()->where('type', 'contribution')->sum('amount') ?: '0.00'));
                $withdrawn = MoneyDecimal::toCents((string) ($lockedGoal->entries()->where('type', 'withdrawal')->sum('amount') ?: '0.00'));
                $available = MoneyDecimal::subtract($contributed, $withdrawn);

                if (MoneyDecimal::compare(MoneyDecimal::toCents($row['amount']), $available) > 0) {
                    throw new RuntimeException('A demo withdrawal exceeds the recorded amount in its savings goal.');
                }
            }

            $lockedGoal->entries()->create([
                'type' => $row['type'],
                'amount' => $row['amount'],
                'entry_date' => $entryDate,
                'note' => $row['note'],
            ]);
            $stats['savings_goal_entries_created']++;
        }
    }
}
