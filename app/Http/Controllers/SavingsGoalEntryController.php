<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavingsGoalEntryStoreRequest;
use App\Models\SavingsGoal;
use App\Support\MoneyDecimal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavingsGoalEntryController extends Controller
{
    public function store(SavingsGoalEntryStoreRequest $request, SavingsGoal $savings_goal): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $savings_goal, $validated): void {
            $lockedGoal = $request->user()->savingsGoals()
                ->whereKey($savings_goal->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($validated['type'] === 'withdrawal') {
                $contributed = MoneyDecimal::toCents((string) ($lockedGoal->entries()->where('type', 'contribution')->sum('amount') ?: '0.00'));
                $withdrawn = MoneyDecimal::toCents((string) ($lockedGoal->entries()->where('type', 'withdrawal')->sum('amount') ?: '0.00'));
                $availableAmount = MoneyDecimal::subtract($contributed, $withdrawn);
                $withdrawalAmount = MoneyDecimal::toCents((string) $validated['amount']);

                if (MoneyDecimal::compare($withdrawalAmount, $availableAmount) > 0) {
                    throw ValidationException::withMessages([
                        'amount' => __('The withdrawal cannot exceed the goal\'s available amount.'),
                    ]);
                }
            }

            $lockedGoal->entries()->create($validated);
        });

        return to_route('savings-goals.show', $savings_goal)
            ->with('toast', [
                'type' => 'success',
                'message' => $validated['type'] === 'contribution'
                    ? __('Contribution added successfully.')
                    : __('Withdrawal recorded successfully.'),
            ]);
    }
}
