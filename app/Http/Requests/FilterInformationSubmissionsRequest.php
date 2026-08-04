<?php

namespace App\Http\Requests;

use App\Models\InformationSubmission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterInformationSubmissionsRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(InformationSubmission::STATUSES)],
            'port_id' => ['nullable', 'integer', 'exists:ports,id'],
        ];
    }
}
