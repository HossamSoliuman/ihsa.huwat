<?php

namespace App\Http\Requests;

use App\Models\Governorate;
use App\Models\Port;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ViewHarborRequest extends FormRequest
{
    public function authorize(): bool
    {
        $port = $this->route('port');

        return $port instanceof Port ? $this->user()->can('view', $port) : $this->user()->can('viewAny', Port::class);
    }

    public function rules(): array
    {
        return [
            'tab' => ['nullable', Rule::in(['overview', 'boats', 'workers', 'licenses', 'violations'])],
            'region_id' => ['nullable', 'required_with:governorate_id,port_id', 'integer', 'exists:regions,id'],
            'governorate_id' => ['nullable', 'required_with:port_id', 'integer', 'exists:governorates,id'],
            'port_id' => ['nullable', 'integer', 'exists:ports,id'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled(['region_id', 'governorate_id']) && ! Governorate::query()
                    ->whereKey($this->integer('governorate_id'))
                    ->where('region_id', $this->integer('region_id'))
                    ->exists()) {
                    $validator->errors()->add('governorate_id', 'المدينة المحددة لا تتبع المحافظة المختارة.');
                }

                if (! $this->filled('port_id')) {
                    return;
                }

                $port = Port::query()->find($this->integer('port_id'));

                if ($this->filled('governorate_id') && $port?->governorate_id !== $this->integer('governorate_id')) {
                    $validator->errors()->add('port_id', 'المرفأ المحدد لا يتبع المدينة المختارة.');
                } elseif ($port !== null && $this->user()->cannot('view', $port)) {
                    $validator->errors()->add('port_id', 'المرفأ المحدد خارج نطاق صلاحيتك.');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'region_id' => 'المحافظة',
            'governorate_id' => 'المدينة',
            'port_id' => 'المرفأ',
        ];
    }
}
