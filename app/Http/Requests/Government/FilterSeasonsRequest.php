<?php

namespace App\Http\Requests\Government;

use App\Models\Season;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterSeasonsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user('government')?->role?->code, config('government.allowed_roles'), true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', Rule::in([
                Season::STATUS_UPCOMING,
                Season::STATUS_ACTIVE,
                Season::STATUS_CLOSED,
            ])],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'search' => ['nullable', 'string', 'max:120'],
        ];
    }
}
