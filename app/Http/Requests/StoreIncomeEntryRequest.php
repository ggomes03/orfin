<?php

namespace App\Http\Requests;

use App\Models\IncomeEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncomeEntryRequest extends FormRequest
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
            'entry_mode' => ['required', 'string', Rule::in([IncomeEntry::TYPE_SOURCE, IncomeEntry::TYPE_SIMPLE])],
            'income_source_id' => [
                'nullable',
                'required_if:entry_mode,'.IncomeEntry::TYPE_SOURCE,
                Rule::exists('income_sources', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'description' => ['nullable', 'required_if:entry_mode,'.IncomeEntry::TYPE_SIMPLE, 'string', 'max:255'],
            'amount' => ['nullable', 'required_if:entry_mode,'.IncomeEntry::TYPE_SIMPLE, 'numeric', 'gt:0'],
            'entry_date' => ['required', 'date'],
        ];
    }
}
