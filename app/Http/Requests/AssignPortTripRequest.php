<?php

namespace App\Http\Requests;

use App\Models\Port;
use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignPortTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        $port = $this->route('port');
        $trip = $this->route('trip');

        return $port instanceof Port && $trip instanceof Trip
            && ($this->user()?->can('view', $port) ?? false)
            && ($this->user()?->can('manageAtPort', $trip) ?? false);
    }

    public function rules(): array
    {
        return ['employee_id' => ['required', 'integer', 'exists:employees,id']];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->route('trip')->port_id === $this->route('port')->id
            ?: $validator->errors()->add('trip', 'الرحلة لا تتبع الميناء المحدد.')];
    }
}
