<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $transactions = $user->transactions();

        $recentTransactions = (clone $transactions)
            ->with('category')
            ->latest('transaction_date')
            ->latest('id')
            ->limit(5)
            ->get();

        $totalIncome = (clone $transactions)->where('type', 'income')->sum('amount');
        $totalExpenses = (clone $transactions)->where('type', 'expense')->sum('amount');

        $expensesByCategory = (clone $transactions)
            ->where('transactions.type', 'expense')
            ->leftJoin('categories', function (JoinClause $join): void {
                $join->on('transactions.category_id', '=', 'categories.id')
                    ->on('transactions.user_id', '=', 'categories.user_id');
            })
            ->selectRaw('COALESCE(categories.name, ?) as category, SUM(transactions.amount) as amount, COALESCE(categories.color, ?) as color', ['Uncategorized', '#cccccc'])
            ->groupBy('transactions.category_id', 'categories.name', 'categories.color')
            ->orderByDesc('amount')
            ->get();

        $now = CarbonImmutable::now(config('app.timezone'));
        $currentMonthStart = $now->startOfMonth();
        $previousMonthStart = $currentMonthStart->subMonth();
        $nextMonthStart = $currentMonthStart->addMonth();
        $currentMonthStartDate = $currentMonthStart->toDateString();
        $previousMonthStartDate = $previousMonthStart->toDateString();
        $nextMonthStartDate = $nextMonthStart->toDateString();

        $monthlyTotals = Transaction::query()
            ->whereBelongsTo($user)
            ->where('transaction_date', '>=', $previousMonthStartDate)
            ->where('transaction_date', '<', $nextMonthStartDate)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN transaction_date >= ? AND transaction_date < ? AND type = ? THEN amount ELSE 0 END), 0) as current_income',
                [$currentMonthStartDate, $nextMonthStartDate, 'income'],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN transaction_date >= ? AND transaction_date < ? AND type = ? THEN amount ELSE 0 END), 0) as current_expenses',
                [$currentMonthStartDate, $nextMonthStartDate, 'expense'],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN transaction_date >= ? AND transaction_date < ? AND type = ? THEN amount ELSE 0 END), 0) as previous_income',
                [$previousMonthStartDate, $currentMonthStartDate, 'income'],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN transaction_date >= ? AND transaction_date < ? AND type = ? THEN amount ELSE 0 END), 0) as previous_expenses',
                [$previousMonthStartDate, $currentMonthStartDate, 'expense'],
            )
            ->first();

        $currentIncomeCents = $this->amountToCents($monthlyTotals->current_income);
        $currentExpensesCents = $this->amountToCents($monthlyTotals->current_expenses);
        $previousIncomeCents = $this->amountToCents($monthlyTotals->previous_income);
        $previousExpensesCents = $this->amountToCents($monthlyTotals->previous_expenses);
        $currentNetCents = $currentIncomeCents - $currentExpensesCents;
        $previousNetCents = $previousIncomeCents - $previousExpensesCents;

        $expenseCategories = Transaction::query()
            ->whereBelongsTo($user)
            ->where('transactions.type', 'expense')
            ->where('transactions.transaction_date', '>=', $previousMonthStartDate)
            ->where('transactions.transaction_date', '<', $nextMonthStartDate)
            ->leftJoin('categories', function (JoinClause $join): void {
                $join->on('transactions.category_id', '=', 'categories.id')
                    ->on('transactions.user_id', '=', 'categories.user_id');
            })
            ->select('transactions.category_id')
            ->selectRaw('COALESCE(categories.name, ?) as category_name', ['Uncategorized'])
            ->selectRaw('COALESCE(categories.color, ?) as category_color', ['#64748b'])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN transactions.transaction_date >= ? AND transactions.transaction_date < ? THEN transactions.amount ELSE 0 END), 0) as current_amount',
                [$currentMonthStartDate, $nextMonthStartDate],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN transactions.transaction_date >= ? AND transactions.transaction_date < ? THEN transactions.amount ELSE 0 END), 0) as previous_amount',
                [$previousMonthStartDate, $currentMonthStartDate],
            )
            ->groupBy('transactions.category_id', 'categories.name', 'categories.color')
            ->get();

        $rankedCategories = $expenseCategories->map(function (object $category) use ($currentExpensesCents): array {
            $currentAmountCents = $this->amountToCents($category->current_amount);
            $previousAmountCents = $this->amountToCents($category->previous_amount);
            $increaseCents = $currentAmountCents - $previousAmountCents;

            return [
                'category_id' => $category->category_id === null ? null : (int) $category->category_id,
                'name' => $category->category_name,
                'color' => $category->category_color,
                'current_amount' => $this->centsToAmount($currentAmountCents),
                'previous_amount' => $this->centsToAmount($previousAmountCents),
                'increase_amount' => $this->centsToAmount($increaseCents),
                'share_percentage' => $currentExpensesCents > 0
                    ? round(($currentAmountCents / $currentExpensesCents) * 100, 1)
                    : null,
                'percentage_change' => $previousAmountCents > 0
                    ? round(($increaseCents / $previousAmountCents) * 100, 1)
                    : null,
                '_current_cents' => $currentAmountCents,
                '_previous_cents' => $previousAmountCents,
                '_increase_cents' => $increaseCents,
            ];
        });

        $topExpenseCategories = $rankedCategories
            ->filter(fn (array $category): bool => $category['_current_cents'] > 0)
            ->sort(fn (array $left, array $right): int => $right['_current_cents'] <=> $left['_current_cents']
                ?: strcasecmp($left['name'], $right['name']))
            ->take(5)
            ->map(fn (array $category): array => $this->publicCategoryInsight($category))
            ->values();

        $increasedExpenseCategories = $rankedCategories
            ->filter(fn (array $category): bool => $category['_increase_cents'] > 0)
            ->sort(fn (array $left, array $right): int => $right['_increase_cents'] <=> $left['_increase_cents']
                ?: strcasecmp($left['name'], $right['name']))
            ->take(3)
            ->map(fn (array $category): array => $this->publicCategoryInsight($category))
            ->values();

        $currentSpendingByCategory = $rankedCategories->keyBy(
            fn (array $category): string => (string) ($category['category_id'] ?? 'uncategorized'),
        );

        $budgetAlerts = $user->budgets()
            ->with(['category' => fn ($query) => $query->where('categories.user_id', $user->id)])
            ->where('month', $now->format('Y-m'))
            ->get()
            ->map(function (Budget $budget) use ($currentSpendingByCategory): ?array {
                $spending = $currentSpendingByCategory->get((string) $budget->category_id);
                $spentCents = $spending['_current_cents'] ?? 0;
                $limitCents = $this->amountToCents($budget->amount);

                if ($limitCents === 0) {
                    if ($spentCents === 0) {
                        return null;
                    }

                    $status = 'exceeded';
                    $usagePercentage = null;
                } else {
                    if ($spentCents * 100 < $limitCents * 80) {
                        return null;
                    }

                    $status = $spentCents > $limitCents ? 'exceeded' : 'approaching';
                    $usagePercentage = round(($spentCents / $limitCents) * 100, 1);
                }

                return [
                    'budget_id' => $budget->id,
                    'category_id' => $budget->category_id,
                    'category_name' => $budget->category?->name ?? 'Unavailable category',
                    'category_color' => $budget->category?->color ?? '#64748b',
                    'limit_amount' => $this->centsToAmount($limitCents),
                    'spent_amount' => $this->centsToAmount($spentCents),
                    'remaining_amount' => $this->centsToAmount($limitCents - $spentCents),
                    'usage_percentage' => $usagePercentage,
                    'status' => $status,
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $alert): int => $alert['status'] === 'exceeded' ? 1 : 0)
            ->values();

        $financialInsights = [
            'periods' => [
                'current' => [
                    'key' => $now->format('Y-m'),
                    'income' => $this->centsToAmount($currentIncomeCents),
                    'expenses' => $this->centsToAmount($currentExpensesCents),
                    'net' => $this->centsToAmount($currentNetCents),
                ],
                'previous' => [
                    'key' => $previousMonthStart->format('Y-m'),
                    'income' => $this->centsToAmount($previousIncomeCents),
                    'expenses' => $this->centsToAmount($previousExpensesCents),
                    'net' => $this->centsToAmount($previousNetCents),
                ],
            ],
            'changes' => [
                'income' => $this->amountChange($currentIncomeCents, $previousIncomeCents),
                'expenses' => $this->amountChange($currentExpensesCents, $previousExpensesCents),
                'net' => ['amount' => $this->centsToAmount($currentNetCents - $previousNetCents)],
            ],
            'top_expense_categories' => $topExpenseCategories,
            'increased_expense_categories' => $increasedExpenseCategories,
            'budget_alerts' => $budgetAlerts,
        ];

        return Inertia::render('Dashboard', [
            'totalIncome' => (float) $totalIncome,
            'totalExpenses' => (float) $totalExpenses,
            'balance' => (float) ($totalIncome - $totalExpenses),
            'recentTransactions' => $recentTransactions,
            'expensesByCategory' => $expensesByCategory,
            'financialInsights' => $financialInsights,
        ]);
    }

    /**
     * Convert a DECIMAL amount with two fractional digits to integer cents.
     */
    private function amountToCents(string|int|float $amount): int
    {
        $value = (string) $amount;
        $isNegative = str_starts_with($value, '-');
        $value = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        $cents = ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');

        return $isNegative ? -$cents : $cents;
    }

    /**
     * Convert integer cents to a decimal amount string without floating-point rounding.
     */
    private function centsToAmount(int $cents): string
    {
        $absoluteCents = abs($cents);

        return ($cents < 0 ? '-' : '').intdiv($absoluteCents, 100).'.'.str_pad((string) ($absoluteCents % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Build a signed amount and safe percentage change.
     *
     * @return array{amount: string, percentage: float|null}
     */
    private function amountChange(int $currentCents, int $previousCents): array
    {
        $difference = $currentCents - $previousCents;

        return [
            'amount' => $this->centsToAmount($difference),
            'percentage' => $previousCents > 0 ? round(($difference / $previousCents) * 100, 1) : null,
        ];
    }

    /**
     * Remove internal ranking values from a category insight.
     *
     * @param  array<string, mixed>  $category
     * @return array{category_id: int|null, name: string, color: string, current_amount: string, previous_amount: string, increase_amount: string, share_percentage: float|null, percentage_change: float|null}
     */
    private function publicCategoryInsight(array $category): array
    {
        return [
            'category_id' => $category['category_id'],
            'name' => $category['name'],
            'color' => $category['color'],
            'current_amount' => $category['current_amount'],
            'previous_amount' => $category['previous_amount'],
            'increase_amount' => $category['increase_amount'],
            'share_percentage' => $category['share_percentage'],
            'percentage_change' => $category['percentage_change'],
        ];
    }
}
