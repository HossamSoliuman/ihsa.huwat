<?php

namespace App\Http\Requests\Counter;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * أصل طلبات بوابة العدّاد — الويب والـAPI يتحققان بالقواعد نفسها. الدخول
 * محكوم بالوسيط (panel:counter / api.role:counter) فالتفويض هنا دائمًا صحيح،
 * والرحلة نفسها تُقيَّد بميناء العدّاد في المتحكّم (ResolvesCounterRecords).
 */
abstract class CounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function counter(): User
    {
        return $this->user();
    }
}
