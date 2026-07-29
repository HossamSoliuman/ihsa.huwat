<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionEmploymentJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transition', $this->route('job'));
    }

    public function rules(): array
    {
        return ['transition' => ['required', Rule::in(['publish', 'close', 'archive', 'restore'])]];
    }
}
