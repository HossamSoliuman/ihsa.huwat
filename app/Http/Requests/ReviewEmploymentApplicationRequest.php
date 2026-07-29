<?php

namespace App\Http\Requests;

use App\Models\EmploymentApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewEmploymentApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('application'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_diff(EmploymentApplication::STATUSES, ['account_created', 'withdrawn']))],
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
