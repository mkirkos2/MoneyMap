<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait TransactionValidationRules
{
    /**
     * Get the validation rules used to validate transactions.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function transactionRules(): array
    {
        return [
            'category_id' => $this->transactionCategoryRules(),
            'type' => $this->transactionTypeRules(),
            'amount' => $this->transactionAmountRules(),
            'transaction_date' => $this->transactionDateRules(),
            'description' => $this->transactionDescriptionRules(),
            'notes' => $this->transactionNotesRules(),
        ];
    }

    /**
     * Get the validation rules used to validate transaction categories.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function transactionCategoryRules(): array
    {
        return [
            'sometimes',
            'nullable',
            'integer',
            Rule::exists('categories', 'id')
                ->where('user_id', $this->user()->id)
                ->where('type', $this->input('type')),
        ];
    }

    /**
     * Get the validation rules used to validate transaction types.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function transactionTypeRules(): array
    {
        return ['required', 'string', 'in:income,expense'];
    }

    /**
     * Get the validation rules used to validate transaction amounts.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function transactionAmountRules(): array
    {
        return ['required', 'numeric', 'gt:0'];
    }

    /**
     * Get the validation rules used to validate transaction dates.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function transactionDateRules(): array
    {
        return ['required', 'date'];
    }

    /**
     * Get the validation rules used to validate transaction descriptions.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function transactionDescriptionRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate transaction notes.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function transactionNotesRules(): array
    {
        return ['sometimes', 'nullable', 'string'];
    }
}
