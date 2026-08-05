<?php

namespace App\Http\Requests;

use App\Http\Middleware\EnsureInformationIdentity;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInformationSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return EnsureInformationIdentity::verified($this) !== null;
    }

    protected function prepareForValidation(): void
    {
        /**
         * The owner identity is never taken from the form: it is pinned to the pair
         * confirmed on the landing page, so nobody can file under someone else's id
         * and every submission stays reachable from that applicant's tracker.
         */
        $identity = EnsureInformationIdentity::verified($this) ?? ['national_id' => '', 'phone' => ''];

        $crewMembers = collect((array) $this->input('crew_members', []))
            ->map(function (mixed $crewMember): array {
                $crewMember = is_array($crewMember) ? $crewMember : [];

                return [
                    ...$crewMember,
                    'identity_number' => $this->normalizeDigits(Arr::get($crewMember, 'identity_number')),
                    'phone' => $this->normalizePhone(Arr::get($crewMember, 'phone')),
                    /** Kept null when blank so `distinct` never compares two empty licences. */
                    'fishing_license_number' => $this->normalizeCode(Arr::get($crewMember, 'fishing_license_number')) ?: null,
                ];
            })
            ->values()
            ->all();

        $fishingTools = collect((array) $this->input('fishing_tools', []))
            ->map(function (mixed $fishingTool): array {
                $fishingTool = is_array($fishingTool) ? $fishingTool : [];

                return [
                    ...$fishingTool,
                    'quantity' => $this->normalizeDigits(Arr::get($fishingTool, 'quantity')),
                    'is_primary' => filter_var(Arr::get($fishingTool, 'is_primary'), FILTER_VALIDATE_BOOL),
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'owner_national_id' => $identity['national_id'],
            'owner_phone' => $identity['phone'],
            'captain_national_id' => $this->normalizeDigits($this->input('captain_national_id')),
            'captain_phone' => $this->normalizePhone($this->input('captain_phone')),
            'registration_no' => $this->normalizeCode($this->input('registration_no')),
            'license_number' => $this->normalizeCode($this->input('license_number')),
            'hull_number' => $this->normalizeCode($this->input('hull_number')),
            'engine_number' => $this->normalizeCode($this->input('engine_number')),
            'engine_serial_number' => $this->normalizeCode($this->input('engine_serial_number')),
            'call_sign' => $this->normalizeCode($this->input('call_sign')),
            'berth_number' => $this->normalizeCode($this->input('berth_number')),
            'mooring_number' => $this->normalizeCode($this->input('mooring_number')),
            'captain_license_number' => $this->normalizeCode($this->input('captain_license_number')),
            'captain_fishing_license_number' => $this->normalizeCode($this->input('captain_fishing_license_number')),
            'crew_count' => $this->normalizeDigits($this->input('crew_count', count($crewMembers))),
            'crew_members' => $crewMembers,
            'fishing_tools' => $fishingTools,
            'consent' => $this->boolean('consent'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $documentTypes = config('information.document_types');

        $rules = [
            'website' => ['nullable', 'size:0'],
            'owner_full_name' => ['required', 'string', 'min:3', 'max:150'],
            'owner_national_id' => ['required', 'regex:/^[12][0-9]{9}$/'],
            'owner_nationality' => ['required', Rule::in(array_keys(config('information.nationalities')))],
            'owner_birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'owner_email' => ['nullable', 'email:rfc', 'max:190'],
            'owner_phone' => ['required', 'regex:/^\+?[0-9]{8,15}$/'],
            'owner_region' => ['required', 'string', 'max:150', Rule::exists(Region::class, 'name')],
            'owner_governorate' => ['required', 'string', 'max:150', Rule::exists(Governorate::class, 'name')],
            'owner_city' => ['required', 'string', 'max:150'],
            'owner_address' => ['nullable', 'string', 'max:250'],
            'license_number' => ['required', 'regex:/^[\pL\pN\-\/]{2,80}$/u'],
            'license_issue_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'license_expiry_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:license_issue_date'],
            'port_id' => ['required', 'integer', Rule::exists(Port::class, 'id')->where('is_active', true)],
            'boat_name' => ['required', 'string', 'min:2', 'max:150'],
            'boat_name_en' => ['required', 'string', 'min:2', 'max:150', 'regex:/^[A-Za-z0-9 .\'\-]+$/'],
            'registration_no' => ['required', 'regex:/^[\pL\pN\-\/]{2,50}$/u'],
            'boat_type' => ['required', Rule::in(array_keys(config('information.boat_types')))],
            'boat_classification' => ['required', Rule::in(array_keys(config('information.boat_classifications')))],
            'hull_material' => ['required', Rule::in(array_keys(config('information.hull_materials')))],
            'boat_build_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'boat_license_expiry_date' => ['required', 'date_format:Y-m-d'],
            'hull_number' => ['required', 'regex:/^[\pL\pN\-\/]{2,80}$/u'],
            'engine_number' => ['required', 'regex:/^[\pL\pN\-\/]{2,80}$/u'],
            'engine_serial_number' => ['required', 'regex:/^[\pL\pN\-\/]{2,80}$/u'],
            'call_sign' => ['nullable', 'regex:/^[A-Z0-9\-\/]{2,30}$/'],
            'berth_number' => ['nullable', 'regex:/^[\pL\pN\-\/]{1,30}$/u'],
            'mooring_number' => ['nullable', 'regex:/^[\pL\pN\-\/]{1,30}$/u'],
            'captain_full_name' => ['required', 'string', 'min:3', 'max:150'],
            'captain_national_id' => ['required', 'regex:/^[12][0-9]{9}$/'],
            'captain_phone' => ['required', 'regex:/^\+?[0-9]{8,15}$/'],
            'captain_license_number' => ['required', 'regex:/^[\pL\pN\-\/]{2,80}$/u'],
            'captain_license_expiry_date' => ['required', 'date_format:Y-m-d'],
            'captain_fishing_license_number' => ['required', 'regex:/^[\pL\pN\-\/]{2,80}$/u'],
            'captain_fishing_license_issue_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'captain_fishing_license_expiry_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:captain_fishing_license_issue_date'],
            'captain_nationality' => ['required', Rule::in(array_keys(config('information.nationalities')))],
            'captain_photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:10240'],
            'crew_count' => ['required', 'integer', 'between:1,50'],
            'crew_members' => ['required', 'array', 'min:1', 'max:50'],
            'crew_members.*' => ['array:full_name,identity_number,phone,nationality,role,fishing_license_number,fishing_license_issue_date,fishing_license_expiry_date'],
            'crew_members.*.full_name' => ['required', 'string', 'min:3', 'max:150'],
            'crew_members.*.identity_number' => ['required', 'regex:/^[12][0-9]{9}$/', 'distinct'],
            'crew_members.*.phone' => ['required', 'regex:/^\+?[0-9]{8,15}$/'],
            'crew_members.*.nationality' => ['required', Rule::in(array_keys(config('information.nationalities')))],
            'crew_members.*.role' => ['required', Rule::in(array_keys(config('information.crew_roles')))],
            'crew_members.*.fishing_license_number' => ['nullable', 'regex:/^[\pL\pN\-\/]{2,80}$/u', 'distinct'],
            'crew_members.*.fishing_license_issue_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'crew_members.*.fishing_license_expiry_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:crew_members.*.fishing_license_issue_date'],
            'crew_photos' => ['nullable', 'array', 'max:50'],
            'crew_photos.*' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:10240'],
            'fishing_method' => ['required', Rule::in(array_keys(config('information.fishing_methods')))],
            'fishing_tools' => ['required', 'array', 'min:1', 'max:50'],
            'fishing_tools.*' => ['array:type,quantity,size,material,condition,is_primary'],
            'fishing_tools.*.type' => ['required', Rule::in(array_keys(config('information.fishing_tool_types')))],
            'fishing_tools.*.quantity' => ['required', 'integer', 'between:1,10000'],
            'fishing_tools.*.size' => ['nullable', 'string', 'max:50'],
            'fishing_tools.*.material' => ['required', Rule::in(array_keys(config('information.fishing_tool_materials')))],
            'fishing_tools.*.condition' => ['required', Rule::in(array_keys(config('information.fishing_tool_conditions')))],
            'fishing_tools.*.is_primary' => ['required', 'boolean'],
            'documents' => ['required', 'array:'.implode(',', array_keys($documentTypes))],
            'consent' => ['accepted'],
        ];

        foreach ($documentTypes as $key => $documentType) {
            $isImageOnly = (bool) ($documentType['image_only'] ?? false);

            $rules['documents.'.$key] = [
                $documentType['required'] ? 'required' : 'nullable',
                'file',
                ...($isImageOnly
                    ? ['image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png']
                    : ['mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png']),
                'max:10240',
            ];
        }

        return $rules;
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('owner_region') && ! $validator->errors()->has('owner_governorate')) {
                    $governorateBelongsToRegion = Governorate::query()
                        ->where('name', $this->input('owner_governorate'))
                        ->whereRelation('region', 'name', $this->input('owner_region'))
                        ->exists();

                    if (! $governorateBelongsToRegion) {
                        $validator->errors()->add('owner_governorate', 'المحافظة المحددة لا تتبع المنطقة المختارة.');
                    }
                }

                $crewMembers = collect((array) $this->input('crew_members', []));

                if ((int) $this->input('crew_count') !== $crewMembers->count()) {
                    $validator->errors()->add('crew_members', 'عدد البحارة يجب أن يطابق السجلات المضافة.');
                }

                if ($crewMembers->contains(fn (mixed $crewMember): bool => is_array($crewMember)
                    && Arr::get($crewMember, 'identity_number') === $this->input('captain_national_id'))) {
                    $validator->errors()->add('crew_members', 'لا يمكن تكرار هوية القبطان ضمن قائمة البحارة.');
                }

                $primaryTools = collect((array) $this->input('fishing_tools', []))
                    ->filter(fn (mixed $tool): bool => is_array($tool) && (bool) Arr::get($tool, 'is_primary'));

                if ($primaryTools->count() !== 1) {
                    $validator->errors()->add('fishing_tools', 'حدد أداة صيد أساسية واحدة فقط.');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            '*.required' => 'هذا الحقل مطلوب.',
            '*.regex' => 'تحقق من صيغة البيانات المدخلة.',
            '*.date_format' => 'أدخل التاريخ بالصيغة الصحيحة.',
            '*.before_or_equal' => 'يجب ألا يكون التاريخ بعد اليوم.',
            'license_expiry_date.after_or_equal' => 'يجب أن يكون انتهاء رخصة الصيد بعد تاريخ إصدارها.',
            'captain_fishing_license_expiry_date.after_or_equal' => 'يجب أن يكون انتهاء رخصة صيد القبطان بعد تاريخ إصدارها.',
            'crew_members.*.fishing_license_expiry_date.after_or_equal' => 'يجب أن يكون انتهاء رخصة صيد البحار بعد تاريخ إصدارها.',
            'port_id.exists' => 'الميناء المحدد غير متاح.',
            'owner_email.email' => 'أدخل بريداً إلكترونياً صحيحاً.',
            'crew_members.min' => 'أضف بحاراً واحداً على الأقل.',
            'crew_members.*.identity_number.distinct' => 'هوية كل بحار يجب أن تكون فريدة.',
            'crew_members.*.fishing_license_number.distinct' => 'رقم رخصة صيد كل بحار يجب أن يكون فريداً.',
            'fishing_tools.min' => 'أضف أداة صيد واحدة على الأقل.',
            'documents.engine_photo.image' => 'ارفع صورة صحيحة للمحرك بصيغة JPG أو PNG.',
            'documents.engine_photo.mimes' => 'صورة المحرك يجب أن تكون بصيغة JPG أو PNG.',
            'documents.engine_photo.extensions' => 'امتداد صورة المحرك يجب أن يكون JPG أو PNG.',
            'documents.boat_photo.image' => 'ارفع صورة صحيحة للقارب بصيغة JPG أو PNG.',
            'documents.boat_photo.mimes' => 'صورة القارب يجب أن تكون بصيغة JPG أو PNG.',
            'documents.boat_photo.extensions' => 'امتداد صورة القارب يجب أن يكون JPG أو PNG.',
            'documents.*.image' => 'ارفع صورة صحيحة بصيغة JPG أو PNG.',
            'documents.*.mimes' => 'ارفع ملف PDF أو صورة JPG أو PNG.',
            'documents.*.extensions' => 'امتداد الملف غير مدعوم.',
            'documents.*.max' => 'يجب ألا يتجاوز حجم الملف 10 ميجابايت.',
            'captain_photo.image' => 'يجب أن تكون صورة القبطان بصيغة صورة صحيحة.',
            'captain_photo.max' => 'يجب ألا يتجاوز حجم صورة القبطان 10 ميجابايت.',
            'crew_photos.*.image' => 'يجب أن تكون صورة البحار بصيغة صورة صحيحة.',
            'crew_photos.*.max' => 'يجب ألا يتجاوز حجم صورة البحار 10 ميجابايت.',
            'consent.accepted' => 'يجب تأكيد صحة البيانات قبل الإرسال.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'owner_full_name' => 'الاسم الرباعي للمالك',
            'owner_national_id' => 'هوية المالك',
            'owner_nationality' => 'جنسية المالك',
            'owner_birth_date' => 'تاريخ ميلاد المالك',
            'owner_email' => 'البريد الإلكتروني للمالك',
            'owner_phone' => 'جوال المالك',
            'owner_region' => 'منطقة المالك',
            'owner_governorate' => 'محافظة المالك',
            'owner_city' => 'مدينة المالك',
            'owner_address' => 'عنوان المالك',
            'license_number' => 'رقم رخصة الصيد',
            'license_issue_date' => 'تاريخ إصدار رخصة الصيد',
            'license_expiry_date' => 'تاريخ انتهاء رخصة الصيد',
            'boat_name' => 'اسم القارب بالعربية',
            'boat_name_en' => 'اسم القارب بالإنجليزية',
            'registration_no' => 'رقم القارب',
            'boat_classification' => 'تصنيف القارب',
            'hull_material' => 'مادة الهيكل',
            'boat_build_date' => 'تاريخ بناء القارب',
            'boat_license_expiry_date' => 'انتهاء رخصة القارب',
            'hull_number' => 'رقم الهيكل',
            'engine_number' => 'رقم المحرك',
            'engine_serial_number' => 'الرقم التسلسلي للمحرك',
            'berth_number' => 'رقم الرصيف',
            'mooring_number' => 'رقم الموقف',
            'captain_full_name' => 'اسم القبطان',
            'captain_national_id' => 'هوية القبطان',
            'captain_license_number' => 'رخصة القيادة البحرية',
            'captain_license_expiry_date' => 'انتهاء رخصة القيادة البحرية',
            'captain_fishing_license_number' => 'رقم رخصة صيد القبطان',
            'captain_fishing_license_issue_date' => 'تاريخ إصدار رخصة صيد القبطان',
            'captain_fishing_license_expiry_date' => 'تاريخ انتهاء رخصة صيد القبطان',
            'crew_members' => 'قائمة البحارة',
            'crew_photos' => 'صور البحارة',
            'fishing_tools' => 'أدوات الصيد',
            'documents' => 'المستندات',
        ];
    }

    private function normalizeDigits(mixed $value): string
    {
        return strtr((string) $value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    private function normalizePhone(mixed $value): string
    {
        return (string) preg_replace('/[\s()\-]+/', '', $this->normalizeDigits($value));
    }

    private function normalizeCode(mixed $value): string
    {
        return Str::of((string) $value)
            ->trim()
            ->replaceMatches('/\s+/u', '')
            ->upper()
            ->toString();
    }
}
