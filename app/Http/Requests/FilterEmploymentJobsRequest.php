<?php

namespace App\Http\Requests;

use App\Models\EmploymentJob;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterEmploymentJobsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', EmploymentJob::class);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['draft', 'open', 'closed', 'archived'])],
        ];
    }
}
