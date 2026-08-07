<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Str;

/**
 * Arabic keyboards produce Arabic-Indic digits, so an identity number typed as ٠١٢٣ has
 * to reach validation as 0123 or every numeric rule rejects a correct entry. Registration
 * and licence numbers arrive with stray spacing and mixed case, and a phone with
 * separators nobody stores. Each field is normalised before the rules see it.
 */
trait NormalizesEnteredValues
{
    protected function normalizeDigits(mixed $value): string
    {
        return strtr((string) $value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    protected function normalizePhone(mixed $value): string
    {
        return (string) preg_replace('/[\s()\-]+/', '', $this->normalizeDigits($value));
    }

    protected function normalizeCode(mixed $value): string
    {
        return Str::of((string) $value)
            ->trim()
            ->replaceMatches('/\s+/u', '')
            ->upper()
            ->toString();
    }

    /**
     * A registration, tax or licence number is searched and compared, so its digits are
     * latinised as well as its spacing and case settled — ١٠١٠ and 1010 must not become two
     * different records.
     */
    protected function normalizeReference(mixed $value): string
    {
        return $this->normalizeCode($this->normalizeDigits($value));
    }

    /** Free text keeps whatever the applicant typed; only stray spacing is settled. */
    protected function normalizeText(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }
}
