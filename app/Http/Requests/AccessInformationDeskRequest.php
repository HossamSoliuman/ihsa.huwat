<?php

namespace App\Http\Requests;

use App\Actions\Information\Support\InformationScope;
use App\Http\Requests\Concerns\NormalizesEnteredValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared base for every screen of the information centre that a moderator may also reach.
 * The gate here only asks whether the account belongs to the centre at all — which parts
 * of it this account opens, and which records it may name, is the scope middleware's
 * answer, given once for every route rather than once per request class.
 */
class AccessInformationDeskRequest extends FormRequest
{
    use NormalizesEnteredValues;

    public function authorize(): bool
    {
        return $this->user()->can('access-information-desk');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    protected function informationScope(): InformationScope
    {
        return $this->user()->informationScope();
    }

    /**
     * Narrows a field to the records the account holds. The middleware guards the records a
     * route names; this guards the ones a form body names — the governorate a new market is
     * filed under, the market a دلال is attached to — which no route ever sees.
     *
     * @param  list<int>  $identifiers
     * @return list<mixed>
     */
    protected function scopedTo(array $identifiers): array
    {
        return $this->informationScope()->isUnrestricted() ? [] : [Rule::in($identifiers)];
    }
}
