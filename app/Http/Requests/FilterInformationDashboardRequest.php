<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterInformationDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'range' => ['nullable', Rule::in(['7', '30', '90', 'year', 'all'])],
            'port_id' => ['nullable', 'integer', 'exists:ports,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
        ];
    }
}
