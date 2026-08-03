<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInformationDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role()
            ->whereIn('code', ['super_admin', 'stat_employee'])
            ->exists() === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $scalarFields = [
            'owner_full_name',
            'owner_national_id',
            'owner_nationality',
            'owner_birth_date',
            'owner_email',
            'owner_phone',
            'owner_region',
            'owner_governorate',
            'owner_city',
            'owner_address',
            'license_number',
            'license_issue_date',
            'license_expiry_date',
            'port_id',
            'boat_name',
            'boat_name_en',
            'registration_no',
            'boat_type',
            'boat_classification',
            'hull_material',
            'boat_build_date',
            'boat_license_expiry_date',
            'hull_number',
            'engine_number',
            'engine_serial_number',
            'call_sign',
            'captain_full_name',
            'captain_national_id',
            'captain_phone',
            'captain_passport_number',
            'captain_birth_date',
            'captain_license_number',
            'captain_license_expiry_date',
            'captain_nationality',
            'captain_qualification',
            'captain_experience_years',
            'fishing_method',
            'consent',
        ];

        return [
            'current_step' => ['required', 'integer', 'between:1,6'],
            'payload' => ['required', 'array:fields,crew_members,fishing_tools'],
            'payload.fields' => ['required', 'array:'.implode(',', $scalarFields)],
            'payload.fields.*' => ['nullable', 'string', 'max:500'],
            'payload.crew_members' => ['required', 'array', 'min:1', 'max:50'],
            'payload.crew_members.*' => ['array:full_name,identity_number,passport_number,phone,birth_date,nationality,role,experience_years'],
            'payload.crew_members.*.*' => ['nullable', 'string', 'max:150'],
            'payload.fishing_tools' => ['required', 'array', 'min:1', 'max:50'],
            'payload.fishing_tools.*' => ['array:type,quantity,size,material,condition,is_primary'],
            'payload.fishing_tools.*.*' => ['nullable', 'string', 'max:150'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $encodedPayload = json_encode($this->input('payload'));

                if (! is_string($encodedPayload) || strlen($encodedPayload) > 262_144) {
                    $validator->errors()->add('payload', 'حجم المسودة أكبر من الحد المسموح.');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'payload.required' => 'تعذر العثور على بيانات المسودة.',
            'payload.array' => 'بنية المسودة غير صالحة.',
            'payload.*.array' => 'بنية أحد أقسام المسودة غير صالحة.',
            'payload.*.max' => 'تجاوز أحد حقول المسودة الحد المسموح.',
        ];
    }
}
