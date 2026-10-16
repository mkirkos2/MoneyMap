<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavingsGoalStoreRequest;
use App\Http\Requests\SavingsGoalUpdateRequest;
use App\Models\SavingsGoal;
use App\Support\MoneyDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SavingsGoalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SavingsGoal::class);

        $goals = $request->user()->savingsGoals()
            ->withSum([
                'entries as contributions_total' => fn ($query) => $query->where('type', 'contribution'),
                'entries as withdrawals_total' => fn ($query) => $query->where('type', 'withdrawal'),
            ], 'amount')
            ->orderBy('target_date')
            ->orderBy('name')
            ->get()
            ->map(fn (SavingsGoal $goal): array => $this->presentGoal($goal));

        return Inertia::render('savings-goals/Index', [
            'goals' => $goals,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', SavingsGoal::class);

        return Inertia::render('savings-goals/Create', [
            'today' => now(config('app.timezone'))->toDateString(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SavingsGoalStoreRequest $request): RedirectResponse
    {
        $goal = $request->user()->savingsGoals()->create($request->validated());

        return to_route('savings-goals.show', $goal)
            ->with('toast', ['type' => 'success', 'message' => __('Savings goal created successfully.')]);
    }

    /**
     * Display the specified resource.
     */
    public function show(SavingsGoal $savings_goal): Response
    {
        $this->authorize('view', $savings_goal);

        $savings_goal->loadSum([
            'entries as contributions_total' => fn ($query) => $query->where('type', 'contribution'),
            'entries as withdrawals_total' => fn ($query) => $query->where('type', 'withdrawal'),
        ], 'amount');

        $entries = $savings_goal->entries()->latest('entry_date')->latest('id')->paginate(20)->withQueryString()
            ->through(fn ($entry): array => [
                'id' => $entry->id,
                'savings_goal_id' => $entry->savings_goal_id,
                'type' => $entry->type,
                'amount' => (string) $entry->amount,
                'entry_date' => $entry->entry_date->toDateString(),
                'note' => $entry->note,
                'created_at' => $entry->created_at?->toISOString(),
                'updated_at' => $entry->updated_at?->toISOString(),
            ]);

        return Inertia::render('savings-goals/Show', [
            'goal' => $this->presentGoal($savings_goal),
            'entries' => $entries,
            'today' => now(config('app.timezone'))->toDateString(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SavingsGoal $savings_goal): Response
    {
        $this->authorize('update', $savings_goal);

        return Inertia::render('savings-goals/Edit', [
            'goal' => [
                'id' => $savings_goal->id,
                'name' => $savings_goal->name,
                'target_amount' => (string) $savings_goal->target_amount,
                'target_date' => $savings_goal->target_date?->toDateString(),
                'description' => $savings_goal->description,
            ],
            'today' => now(config('app.timezone'))->toDateString(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SavingsGoalUpdateRequest $request, SavingsGoal $savings_goal): RedirectResponse
    {
        $savings_goal->fill($request->validated())->save();

        return to_route('savings-goals.show', $savings_goal)
            ->with('toast', ['type' => 'success', 'message' => __('Savings goal updated successfully.')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SavingsGoal $savings_goal): RedirectResponse
    {
        $this->authorize('delete', $savings_goal);

        DB::transaction(fn () => $savings_goal->delete());

        return to_route('savings-goals.index')
            ->with('toast', ['type' => 'success', 'message' => __('Savings goal archived successfully. Its history was retained.')]);
    }

    /** @return array<string, mixed> */
    private function presentGoal(SavingsGoal $goal): array
    {
        $contributedCents = MoneyDecimal::toCents((string) ($goal->contributions_total ?? '0.00'));
        $withdrawnCents = MoneyDecimal::toCents((string) ($goal->withdrawals_total ?? '0.00'));
        $currentCents = MoneyDecimal::subtract($contributedCents, $withdrawnCents);
        $targetCents = MoneyDecimal::toCents((string) $goal->target_amount);
        $isComplete = MoneyDecimal::compare($currentCents, $targetCents) >= 0;
        $remainingCents = $isComplete ? '0' : MoneyDecimal::subtract($targetCents, $currentCents);
        $targetDate = $goal->target_date?->toDateString();
        $today = now(config('app.timezone'))->toDateString();
        $isOverdue = ! $isComplete && $targetDate !== null && $targetDate < $today;
        $progress = MoneyDecimal::percentage($currentCents, $targetCents);
        $monthsRemaining = null;

        if ($targetDate !== null && ! $isComplete && ! $isOverdue) {
            $todayMonth = CarbonImmutable::parse($today, config('app.timezone'))->startOfMonth();
            $targetMonth = CarbonImmutable::parse($targetDate, config('app.timezone'))->startOfMonth();
            $monthsRemaining = (($targetMonth->year - $todayMonth->year) * 12) + $targetMonth->month - $todayMonth->month + 1;
        }

        $monthlyRequired = match (true) {
            $isComplete => '0.00',
            $isOverdue || $monthsRemaining === null => null,
            default => MoneyDecimal::toAmount(MoneyDecimal::divideCentsRoundedUp($remainingCents, $monthsRemaining)),
        };

        return [
            'id' => $goal->id,
            'name' => $goal->name,
            'target_amount' => (string) $goal->target_amount,
            'target_date' => $targetDate,
            'description' => $goal->description,
            'current_amount' => MoneyDecimal::toAmount($currentCents),
            'remaining_amount' => MoneyDecimal::toAmount($remainingCents),
            'progress_percentage' => $progress,
            'is_complete' => $isComplete,
            'is_overdue' => $isOverdue,
            'months_remaining' => $monthsRemaining,
            'monthly_required_amount' => $monthlyRequired,
            'created_at' => $goal->created_at?->toISOString(),
            'updated_at' => $goal->updated_at?->toISOString(),
        ];
    }
}
