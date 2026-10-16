<?php

namespace App\Concerns;

use App\Models\Budget;
use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

trait BudgetValidationRules
{
    /**
     * Get the validation rules for a monthly budget.
     *
     * @return array<string, array<int, Closure|string|ValidationRule>>
     */
    protected function budgetRules(?Budget $budget = null): array
    {
        $user = $this->user();
        $categoryId = $this->input('category_id');
        $uniqueMonthRules = [];

        if (is_string($categoryId) || is_int($categoryId)) {
            $uniqueMonthRules[] = Rule::unique('budgets', 'month')
                ->where(fn (Builder $query): Builder => $query
                    ->where('user_id', $user?->id)
                    ->where('category_id', $categoryId))
                ->ignore($budget?->getKey());
        }

        return [
            'category_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail) use ($budget, $user): void {
                    if ($user === null) {
                        $fail('Select an available expense category.');

                        return;
                    }

                    if (! is_string($value) && ! is_int($value)) {
                        $fail('Select an available expense category.');

                        return;
                    }

                    $category = Category::withTrashed()
                        ->whereBelongsTo($user)
                        ->find($value);

                    $isAvailableExpenseCategory = $category !== null
                        && $category->type === 'expense'
                        && ! $category->trashed();
                    $isCurrentBudgetCategory = $budget !== null
                        && $category !== null
                        && $budget->category_id === $category->id;

                    if (! $isAvailableExpenseCategory && ! $isCurrentBudgetCategory) {
                        $fail('Select an available expense category.');
                    }
                },
            ],
            'month' => [
                'required',
                'date_format:Y-m',
                ...$uniqueMonthRules,
            ],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
        ];
    }
}
