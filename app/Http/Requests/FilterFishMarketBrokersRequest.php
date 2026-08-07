<?php

namespace App\Http\Requests;

use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use Illuminate\Validation\Rule;

class FilterFishMarketBrokersRequest extends ManageFishMarketRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:150'],
            'fish_market_id' => ['nullable', 'integer', Rule::exists(FishMarket::class, 'id')],
            'entity_type' => ['nullable', Rule::in(FishMarketBroker::ENTITY_TYPES)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'q' => 'البحث',
            'fish_market_id' => 'السوق',
            'entity_type' => 'نوع الدلال',
            'status' => 'الحالة',
        ];
    }
}
