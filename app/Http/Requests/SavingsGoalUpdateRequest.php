<?php

namespace App\Http\Requests;

use App\Models\SavingsGoal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SavingsGoalUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $goal = $this->route('savings_goal');

        return $goal instanceof SavingsGoal && ($this->user()?->can('update', $goal) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'target_amount' => ['sometimes', 'required', 'numeric', 'gt:0', 'max:9999999999.99', 'decimal:0,2'],
            'target_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
