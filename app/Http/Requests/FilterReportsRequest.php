<?php

namespace App\Http\Requests;

use App\Models\Port;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FilterReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role->code, ['super_admin', 'region_manager', 'gov_supervisor'], true);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'report_type' => $this->input('report_type', 'trips'),
            'date_from' => $this->input('date_from', today()->startOfMonth()->toDateString()),
            'date_to' => $this->input('date_to', today()->toDateString()),
        ]);
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', Rule::in(array_keys(config('reports.types')))],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'governorate_id' => ['nullable', 'integer', 'exists:governorates,id'],
            'port_id' => ['nullable', 'integer', 'exists:ports,id'],
            'boat_id' => ['nullable', 'integer', 'exists:boats,id'],
            'captain_id' => ['nullable', 'integer', 'exists:captains,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'species_id' => ['nullable', 'integer', 'exists:fish_species,id'],
            'status' => ['nullable', Rule::in(array_keys(config('reports.trip_statuses')))],
            'diff_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'diff_max' => ['nullable', 'numeric', 'gte:diff_min', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $portId = $this->integer('port_id');
            if ($portId > 0 && ! Port::query()->visibleTo($this->user())->whereKey($portId)->exists()) {
                $validator->errors()->add('port_id', 'الميناء المحدد خارج نطاق صلاحيتك.');
            }
            $regionId = $this->integer('region_id');
            if ($regionId > 0 && $this->user()->role->code === 'region_manager' && $regionId !== (int) $this->user()->region_id) {
                $validator->errors()->add('region_id', 'المنطقة المحددة خارج نطاق صلاحيتك.');
            }
            $governorateId = $this->integer('governorate_id');
            if ($governorateId > 0 && $this->user()->role->code === 'gov_supervisor' && $governorateId !== (int) $this->user()->governorate_id) {
                $validator->errors()->add('governorate_id', 'المحافظة المحددة خارج نطاق صلاحيتك.');
            }
        }];
    }
}
