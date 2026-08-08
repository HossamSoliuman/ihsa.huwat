<?php

namespace App\Http\Requests;

use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
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
            'region_id' => ['nullable', 'integer', Rule::exists(Region::class, 'id')],
            'governorate_id' => ['nullable', 'integer', Rule::exists(Governorate::class, 'id')],
            'port_id' => ['nullable', 'integer', Rule::exists(Port::class, 'id')],
        ];
    }
}
