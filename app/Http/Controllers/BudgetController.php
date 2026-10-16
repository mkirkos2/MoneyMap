<?php

namespace App\Http\Controllers;

use App\Http\Requests\BudgetStoreRequest;
use App\Http\Requests\BudgetUpdateRequest;
use App\Models\Budget;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Budget::class);

        $validatedFilters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $user = $request->user();
        $month = $validatedFilters['month'] ?? null;

        $budgets = $user->budgets()
            ->with('category')
            ->when($month !== null, fn ($query) => $query->where('month', $month))
            ->orderByDesc('month')
            ->orderBy('category_id')
            ->paginate(12)
            ->withQueryString();

        $budgetPage = $budgets->getCollection();
        $spendingByCategoryAndMonth = collect();

        if ($budgetPage->isNotEmpty()) {
            $firstMonth = CarbonImmutable::createFromFormat('!Y-m', $budgetPage->min('month'));
            $lastMonth = CarbonImmutable::createFromFormat('!Y-m', $budgetPage->max('month'));

            $spendingByCategoryAndMonth = Transaction::query()
                ->whereBelongsTo($user)
                ->where('type', 'expense')
                ->where('transaction_date', '>=', $firstMonth->startOfMonth()->toDateString())
                ->where('transaction_date', '<', $lastMonth->addMonth()->startOfMonth()->toDateString())
                ->select('category_id')
                ->selectRaw('SUBSTR(transaction_date, 1, 7) as transaction_month')
                ->selectRaw('SUM(amount) as spent_amount')
                ->groupBy('category_id')
                ->groupByRaw('SUBSTR(transaction_date, 1, 7)')
                ->get()
                ->keyBy(fn (Transaction $transaction): string => $transaction->category_id.'-'.$transaction->transaction_month);
        }

        $budgetPage->transform(function (Budget $budget) use ($spendingByCategoryAndMonth): Budget {
            $amount = (float) $budget->amount;
            $spendingKey = $budget->category_id.'-'.$budget->month;
            $spentAmount = round((float) ($spendingByCategoryAndMonth->get($spendingKey)?->spent_amount ?? 0), 2);

            $budget->setAttribute('spent_amount', $spentAmount);
            $budget->setAttribute('remaining_amount', round($amount - $spentAmount, 2));
            $budget->setAttribute('usage_percentage', round(($spentAmount / $amount) * 100, 1));

            return $budget;
        });

        return Inertia::render('budgets/Index', [
            'budgets' => $budgets,
            'categories' => $user->categories()->where('type', 'expense')->orderBy('name')->get(),
            'filters' => ['month' => $month],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Budget::class);

        return Inertia::render('budgets/Create', [
            'categories' => $request->user()->categories()->where('type', 'expense')->orderBy('name')->get(),
            'month' => now()->format('Y-m'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BudgetStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Budget::class);

        $budget = $request->user()->budgets()->create($request->validated());

        return to_route('budgets.index', ['month' => $budget->month])
            ->with('toast', ['type' => 'success', 'message' => __('Budget created successfully.')]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Budget $budget): Response
    {
        $this->authorize('update', $budget);

        $categories = $request->user()->categories()
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();
        $currentCategory = $budget->category;

        if ($currentCategory !== null && ! $categories->contains('id', $currentCategory->id)) {
            $categories->push($currentCategory);
        }

        return Inertia::render('budgets/Edit', [
            'budget' => $budget,
            'categories' => $categories->sortBy('name')->values(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BudgetUpdateRequest $request, Budget $budget): RedirectResponse
    {
        $this->authorize('update', $budget);

        $budget->update($request->validated());

        return to_route('budgets.index', ['month' => $budget->month])
            ->with('toast', ['type' => 'success', 'message' => __('Budget updated successfully.')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Budget $budget): RedirectResponse
    {
        $this->authorize('delete', $budget);

        $month = $budget->month;
        $budget->delete();

        return to_route('budgets.index', ['month' => $month])
            ->with('toast', ['type' => 'success', 'message' => __('Budget deleted successfully.')]);
    }
}
