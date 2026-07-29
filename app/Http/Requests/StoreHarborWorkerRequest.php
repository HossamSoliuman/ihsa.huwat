<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreHarborWorkerRequest extends DeleteHarborRecordRequest
{
    public function rules(): array
    {
        return [
            'employee_name' => ['required', 'string', 'max:150'], 'identity_number' => ['nullable', 'string', 'max:100'],
            'nationality' => ['required', Rule::in(['saudi', 'non_saudi'])],
            'worker_type' => ['required', Rule::in(['supervisor', 'contractor', 'fisherman', 'foreign_worker'])],
            'mobile_number' => ['nullable', 'string', 'max:30'],
            'employment_status' => ['required', Rule::in(['active', 'suspended', 'expired'])],
            'start_date' => ['nullable', 'date_format:Y-m-d'], 'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }
}
