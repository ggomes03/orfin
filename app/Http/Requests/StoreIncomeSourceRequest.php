<?php

namespace App\Http\Requests;

use App\Models\IncomeSource;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncomeSourceRequest extends FormRequest
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
        return [
            'type' => ['required', 'string', Rule::in(array_keys(IncomeSource::typeLabels()))],
            'description' => ['required', 'string', 'max:255'],
            'monthly_amount' => ['required', 'numeric', 'gt:0'],
            'effective_from' => ['required', 'date'],
        ];
    }
}
