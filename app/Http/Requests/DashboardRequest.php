<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Keep backward compatibility: invalid month input is ignored, not rejected.
        return [];
    }

    public function selectedMonth(): ?int
    {
        $month = $this->integer('month');

        return ($month >= 1 && $month <= 12) ? $month : null;
    }
}
