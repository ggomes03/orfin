<?php

namespace App\Http\Requests;

use App\Models\ExpenseEntry;
use App\Models\ExpenseSource;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseEntryRequest extends FormRequest
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
            'entry_mode' => ['required', 'string', Rule::in([ExpenseEntry::TYPE_SOURCE, ExpenseEntry::TYPE_SIMPLE])],
            'expense_source_id' => [
                'nullable',
                'required_if:entry_mode,'.ExpenseEntry::TYPE_SOURCE,
                Rule::exists('expense_sources', 'id')->where(
                    fn ($query) => $query
                        ->where('user_id', $this->user()?->id)
                        ->where('type', '!=', ExpenseSource::TYPE_FIXED)
                ),
            ],
            'description' => ['nullable', 'required_if:entry_mode,'.ExpenseEntry::TYPE_SIMPLE, 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'entry_date' => ['required', 'date'],
        ];
    }
}
