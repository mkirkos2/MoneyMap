<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait CategoryValidationRules
{
    /**
     * Get the validation rules used to validate categories.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function categoryRules(): array
    {
        return [
            'name' => $this->categoryNameRules(),
            'type' => $this->categoryTypeRules(),
            'color' => $this->categoryColorRules(),
            'icon' => $this->categoryIconRules(),
        ];
    }

    /**
     * Get the validation rules used to validate category names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function categoryNameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate category types.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function categoryTypeRules(): array
    {
        return ['required', 'string', 'in:income,expense'];
    }

    /**
     * Get the validation rules used to validate category colors.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function categoryColorRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];
    }

    /**
     * Get the validation rules used to validate category icons.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function categoryIconRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:255'];
    }
}