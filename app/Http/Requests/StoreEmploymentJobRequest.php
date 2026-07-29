<?php

namespace App\Http\Requests;

use App\Models\EmploymentJob;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmploymentJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', EmploymentJob::class);
    }

    public function rules(): array
    {
        return [
            'title_ar' => ['required', 'string', 'max:190'],
            'department' => ['nullable', 'string', 'max:190'],
            'summary' => ['required', 'string'],
            'description' => ['required', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'requirements' => ['required', 'string'],
            'employment_type' => ['required', Rule::in(array_keys(config('employment.types')))],
            'vacancies' => ['required', 'integer', 'between:1,65535'],
            'port_id' => ['nullable', Rule::exists('ports', 'id')->where('is_active', true)],
            'city' => ['nullable', 'string', 'max:120'],
            'salary_min' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'salary_max' => ['nullable', 'numeric', 'between:0,99999999.99', 'gte:salary_min'],
            'application_deadline' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
