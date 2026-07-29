<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManageTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->routeIs('dashboard.trips.destroy') ? 'delete' : 'update', $this->route('trip'));
    }

    public function rules(): array
    {
        return [];
    }
}
