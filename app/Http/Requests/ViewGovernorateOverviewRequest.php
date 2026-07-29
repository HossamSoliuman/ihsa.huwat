<?php

namespace App\Http\Requests;

use App\Models\Governorate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ViewGovernorateOverviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role->code, ['super_admin', 'region_manager', 'gov_supervisor'], true);
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->role->code === 'gov_supervisor') {
            $this->merge(['governorate_id' => $this->user()->governorate_id]);
        }
    }

    public function rules(): array
    {
        return ['governorate_id' => ['nullable', 'integer', 'exists:governorates,id']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $id = $this->integer('governorate_id');
            if ($id < 1) {
                return;
            }
            $allowed = Governorate::query()->whereKey($id)
                ->when($this->user()->role->code === 'region_manager', fn ($query) => $query->where('region_id', $this->user()->region_id))
                ->when($this->user()->role->code === 'gov_supervisor', fn ($query) => $query->whereKey($this->user()->governorate_id))->exists();
            if (! $allowed) {
                $validator->errors()->add('governorate_id', 'المحافظة المحددة خارج نطاق صلاحيتك.');
            }
        }];
    }
}
