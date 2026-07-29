<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ViewEmploymentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role->code === 'employee_portal';
    }

    public function rules(): array
    {
        return [];
    }
}
