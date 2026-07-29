<?php

namespace App\Http\Requests;

use App\Models\EmploymentApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterEmploymentApplicationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', EmploymentApplication::class);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(EmploymentApplication::STATUSES)],
            'job_id' => ['nullable', 'integer', 'exists:employment_jobs,id'],
        ];
    }
}
