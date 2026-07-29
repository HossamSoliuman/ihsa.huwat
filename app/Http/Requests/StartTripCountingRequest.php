<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartTripCountingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('startCounting', $this->route('trip')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
