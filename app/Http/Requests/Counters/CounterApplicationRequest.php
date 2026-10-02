<?php

namespace App\Http\Requests\Counters;

use App\Models\CounterApplication;
use App\Models\User;
use App\Rules\Recaptcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * طلب التوظيف كما يُقدَّم من صفحة /counter/apply ومن التطبيق. الجولة تحدّد
 * الشركة والميناء؛ وقبولها للطلبات يُفحص في CounterApplicationService تحت
 * القفل. reCAPTCHA للويب وحده — التطبيق يوثّق بالرمز.
 */
class CounterApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // الجوال يُطبَّع قبل التحقق حتى يُطابق الصيغة المخزّنة في الحسابات.
        $this->merge(['phone' => User::normalizePhone($this->input('phone'))]);
    }

    public function rules(): array
    {
        return [
            'hiring_round_id' => ['required', 'integer', Rule::exists('hiring_rounds', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required', 'string', 'regex:/^05\d{8}$/',
                Rule::unique('users', 'phone'),
                Rule::unique('counter_applications', 'phone')->where('status', CounterApplication::PENDING)->whereNotNull('phone_verified_at'),
            ],
            'national_id' => ['required', 'string', 'regex:/^[12]\d{9}$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:-18 years'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'g-recaptcha-response' => ! $this->is('api/*') && Recaptcha::enabled() ? ['required', new Recaptcha('counter_apply')] : [],
        ];
    }

    public function messages(): array
    {
        return [
            'hiring_round_id.required' => 'اختر الميناء الذي تتقدّم للعمل فيه.',
            'phone.regex' => 'رقم الجوال يجب أن يكون بصيغة 05XXXXXXXX.',
            'phone.unique' => 'هذا الجوال له حساب في حوات أو طلب توظيف قيد المراجعة.',
            'national_id.regex' => 'رقم الهوية أو الإقامة عشرة أرقام يبدأ بـ 1 أو 2.',
            'birth_date.before' => 'يُشترط أن يكون عمر المتقدّم 18 سنة فأكثر.',
            'g-recaptcha-response.required' => 'تعذّر التحقق من أنك لست روبوتًا، أعد المحاولة.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'national_id' => 'رقم الهوية',
            'email' => 'البريد الإلكتروني',
            'birth_date' => 'تاريخ الميلاد',
            'qualification' => 'المؤهل',
            'experience_years' => 'سنوات الخبرة',
            'password' => 'كلمة المرور',
        ];
    }
}
