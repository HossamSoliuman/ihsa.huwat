<?php

namespace App\Http\Requests;

use App\Models\EmploymentApplication;
use App\Models\EmploymentJob;
use App\Models\Port;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmploymentApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $job = $this->route('job');

        return $job instanceof EmploymentJob
            && EmploymentJob::query()->open()->whereKey($job->getKey())->exists();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'identity_number' => strtoupper((string) preg_replace('/[\s-]+/u', '', (string) $this->input('identity_number'))),
            'mobile' => preg_replace('/[\s()\-]+/', '', (string) $this->input('mobile')),
            'phone' => preg_replace('/[\s()\-]+/', '', (string) $this->input('phone')) ?: null,
            'consent' => $this->boolean('consent'),
        ]);
    }

    public function rules(): array
    {
        $jobId = $this->route('job')->getKey();
        $identityType = $this->input('identity_type');
        $identityRule = match ($identityType) {
            'national_id' => 'regex:/^1[0-9]{9}$/',
            'residency' => 'regex:/^2[0-9]{9}$/',
            default => 'regex:/^[A-Z0-9]{5,30}$/',
        };

        return [
            'website' => ['nullable', 'size:0'],
            'full_name' => ['required', 'string', 'min:3', 'max:150'],
            'nationality' => ['required', 'string', 'min:2', 'max:100'],
            'identity_type' => ['required', Rule::in(array_keys(config('employment.identity_types')))],
            'identity_number' => ['required', $identityRule, Rule::unique(EmploymentApplication::class)->where('job_id', $jobId)],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.today()->subYears(16)->format('Y-m-d'), 'after_or_equal:'.today()->subYears(80)->format('Y-m-d')],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'marital_status' => ['required', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'children_count' => ['required', 'integer', 'between:0,30'],
            'mobile' => ['required', 'regex:/^\+?[0-9]{8,15}$/'],
            'phone' => ['nullable', 'regex:/^\+?[0-9]{7,15}$/'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'city' => ['required', 'string', 'min:2', 'max:120'],
            'address' => ['required', 'string', 'min:5', 'max:1000'],
            'preferred_port_id' => ['nullable', 'integer', Rule::exists(Port::class, 'id')->where('is_active', true)],
            'work_type' => ['required', Rule::in(array_keys(config('employment.types')))],
            'source' => ['required', Rule::in(array_keys(config('employment.sources')))],
            'education_level' => ['required', Rule::in(array_keys(config('employment.education_levels')))],
            'specialization' => ['required', 'string', 'min:2', 'max:190'],
            'institution' => ['required', 'string', 'min:2', 'max:190'],
            'graduation_year' => ['nullable', 'integer', 'between:1950,'.(today()->year + 1)],
            'experience_years' => ['required', 'numeric', 'between:0,60'],
            'current_employer' => ['nullable', 'string', 'max:190'],
            'current_job_title' => ['nullable', 'string', 'max:190'],
            'professional_summary' => ['nullable', 'string', 'max:3000'],
            'skills' => ['required', 'string', 'min:2', 'max:3000'],
            'availability_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'cover_letter' => ['nullable', 'string', 'max:5000'],
            'consent' => ['accepted'],
            'cv_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'identity_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'certificate_files' => ['nullable', 'array', 'max:5'],
            'certificate_files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'identity_number.unique' => 'سبق تقديم طلب لهذه الوظيفة باستخدام رقم الهوية نفسه.',
            'cv_file.required' => 'السيرة الذاتية مطلوبة.',
            '*.mimes' => 'الصيغ المسموحة هي PDF وJPEG وPNG فقط.',
            '*.max' => 'تجاوزت القيمة أو الملف الحد الأقصى المسموح.',
            'consent.accepted' => 'يجب الموافقة على الإقرار قبل إرسال الطلب.',
        ];
    }
}
