<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveDiscrepancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve', $this->route('discrepancy'));
    }

    public function rules(): array
    {
        return [];
    }
}
