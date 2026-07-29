<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('leave')) ?? false;
    }

    public function rules(): array
    {
        return ['decision' => ['required', 'in:approved,rejected']];
    }
}
