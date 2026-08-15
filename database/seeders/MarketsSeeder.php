<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\MarketAuction;
use App\Models\Species;
use Illuminate\Database\Seeder;

class MarketsSeeder extends Seeder
{
    public function run(): void
    {
        $markets = [
            ['name' => 'سوق سمك القطيف المركزي', 'code' => 'MQT', 'region' => 'المنطقة الشرقية', 'governorate' => 'القطيف', 'port' => 'ميناء القطيف', 'market_type' => 'مزاد', 'fish_shops_count' => 45, 'auction_stalls_count' => 12],
            ['name' => 'سوق الدمام للأسماك', 'code' => 'MDM', 'region' => 'المنطقة الشرقية', 'governorate' => 'الدمام', 'port' => 'ميناء الدمام', 'market_type' => 'مركّب', 'fish_shops_count' => 60, 'auction_stalls_count' => 15],
            ['name' => 'سوق جدة المركزي للأسماك', 'code' => 'MJD', 'region' => 'مكة المكرمة', 'governorate' => 'جدة', 'port' => 'ميناء جدة للصيد', 'market_type' => 'مزاد', 'fish_shops_count' => 80, 'auction_stalls_count' => 20],
            ['name' => 'سوق جيزان للأسماك', 'code' => 'MGZ', 'region' => 'جازان', 'governorate' => 'جيزان', 'port' => 'ميناء جيزان', 'market_type' => 'جملة', 'fish_shops_count' => 38, 'auction_stalls_count' => 10],
        ];

        foreach ($markets as $item) {
            Market::updateOrCreate(['name' => $item['name']], $item);
        }

        $auctions = [
            ['market' => 'سوق سمك القطيف المركزي', 'species' => 'الهامور', 'auction_date' => now()->subDays(1)->toDateString(), 'quantity_offered_kg' => 1800, 'quantity_sold_kg' => 1650, 'avg_price_per_kg' => 68, 'buyer_type' => 'تجزئة'],
            ['market' => 'سوق سمك القطيف المركزي', 'species' => 'الروبيان', 'auction_date' => now()->subDays(1)->toDateString(), 'quantity_offered_kg' => 2400, 'quantity_sold_kg' => 2400, 'avg_price_per_kg' => 45, 'buyer_type' => 'جملة'],
            ['market' => 'سوق الدمام للأسماك', 'species' => 'الكنعد', 'auction_date' => now()->subDays(2)->toDateString(), 'quantity_offered_kg' => 3200, 'quantity_sold_kg' => 2950, 'avg_price_per_kg' => 38, 'buyer_type' => 'جملة'],
            ['market' => 'سوق جدة المركزي للأسماك', 'species' => 'الناجل', 'auction_date' => now()->subDays(1)->toDateString(), 'quantity_offered_kg' => 1100, 'quantity_sold_kg' => 1020, 'avg_price_per_kg' => 82, 'buyer_type' => 'مطاعم'],
            ['market' => 'سوق جدة المركزي للأسماك', 'species' => 'الشعري', 'auction_date' => now()->toDateString(), 'quantity_offered_kg' => 1500, 'quantity_sold_kg' => 1380, 'avg_price_per_kg' => 34, 'buyer_type' => 'تجزئة'],
            ['market' => 'سوق جيزان للأسماك', 'species' => 'الحريد', 'auction_date' => now()->toDateString(), 'quantity_offered_kg' => 900, 'quantity_sold_kg' => 860, 'avg_price_per_kg' => 28, 'buyer_type' => 'تجزئة'],
        ];

        foreach ($auctions as $item) {
            $market = Market::where('name', $item['market'])->first();
            $species = Species::where('name_ar', $item['species'])->first();
            MarketAuction::updateOrCreate(
                ['market_id' => $market->id, 'species_id' => $species->id, 'auction_date' => $item['auction_date']],
                collect($item)->except(['market', 'species'])->all()
            );
        }
    }
}