<?php

namespace App\Http\Requests;

use App\Models\BudgetAllocation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBudgetAllocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (BudgetAllocation::categories() as $category) {
            $rules[$category] = ['required', 'integer', 'min:0', 'max:100'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $total = collect(BudgetAllocation::categories())
                ->sum(fn (string $category): int => (int) $this->input($category, 0));

            if ($total > 100) {
                $validator->errors()->add('total', 'A soma das porcentagens nao pode passar de 100%.');
            }
        });
    }
}
