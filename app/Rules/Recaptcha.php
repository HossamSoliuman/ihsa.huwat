<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * يتحقق من رمز reCAPTCHA v3 لدى Google: نجاح الرمز، ومطابقة الإجراء،
 * ودرجة لا تقل عن services.recaptcha.min_score.
 */
class Recaptcha implements ValidationRule
{
    public function __construct(private readonly string $action) {}

    public static function enabled(): bool
    {
        return filled(config('services.recaptcha.site_key')) && filled(config('services.recaptcha.secret_key'));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $message = 'تعذّر التحقق من أنك لست روبوتًا، أعد المحاولة.';

        if (! is_string($value) || $value === '') {
            $fail($message);

            return;
        }

        try {
            $result = Http::asForm()->timeout(5)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha.secret_key'),
                'response' => $value,
                'remoteip' => request()->ip(),
            ])->json();
        } catch (Throwable) {
            $fail($message);

            return;
        }

        if (! ($result['success'] ?? false)
            || ($result['action'] ?? null) !== $this->action
            || (float) ($result['score'] ?? 0) < (float) config('services.recaptcha.min_score')) {
            $fail($message);
        }
    }
}
