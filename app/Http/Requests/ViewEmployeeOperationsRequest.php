<?php

namespace App\Http\Requests;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;

class ViewEmployeeOperationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Trip::class) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
