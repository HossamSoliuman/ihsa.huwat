<?php

namespace App\Http\Requests;

use App\Models\Port;
use App\Models\TripDiscrepancy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ApprovePortDiscrepancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $port = $this->route('port');
        $discrepancy = $this->route('discrepancy');

        return $port instanceof Port && $discrepancy instanceof TripDiscrepancy
            && ($this->user()?->can('view', $port) ?? false)
            && ($this->user()?->can('approve', $discrepancy) ?? false);
    }

    public function rules(): array
    {
        return [];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->route('discrepancy')->trip()->where('port_id', $this->route('port')->id)->exists()
            ?: $validator->errors()->add('discrepancy', 'الفرق لا يتبع الميناء المحدد.')];
    }
}
