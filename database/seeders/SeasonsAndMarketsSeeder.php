<?php

namespace Database\Seeders;

use App\Models\FishingSeason;
use App\Models\Market;
use App\Models\MarketAuction;
use App\Models\SeasonLicense;
use Illuminate\Database\Seeder;

class SeasonsAndMarketsSeeder extends Seeder
{
    public function run(): void
    {
        $seasons = [
            ['name' => 'موسم الروبيان 2026', 'species' => 'روبيان أخضر', 'region' => 'الشرقية', 'season_type' => 'موسم صيد', 'start_date' => '2026-08-01', 'end_date' => '2026-12-31', 'gear_allowed' => 'شبك جر قاعي', 'min_size_cm' => 12, 'quota_tons' => 1800, 'status' => 'قريب الفتح'],
            ['name' => 'حظر الروبيان – الخليج', 'species' => 'روبيان أخضر', 'region' => 'الشرقية', 'season_type' => 'فترة حظر', 'start_date' => '2026-01-01', 'end_date' => '2026-07-31', 'gear_allowed' => 'لا يوجد', 'quota_tons' => 0, 'status' => 'مفتوح'],
            ['name' => 'موسم الكنعد – البحر الأحمر', 'species' => 'كنعد', 'region' => 'جازان', 'season_type' => 'موسم صيد', 'start_date' => '2026-11-01', 'end_date' => '2027-02-28', 'gear_allowed' => 'خيط طويل، سنارة يدوية', 'min_size_cm' => 65, 'quota_tons' => 2400, 'status' => 'مغلق'],
            ['name' => 'موسم الهامور', 'species' => 'هامور', 'region' => 'مكة المكرمة', 'season_type' => 'موسم صيد', 'start_date' => '2026-10-01', 'end_date' => '2027-03-31', 'gear_allowed' => 'قراقير (أشراك)', 'min_size_cm' => 45, 'quota_tons' => 900, 'status' => 'مغلق'],
        ];

        foreach ($seasons as $season) {
            FishingSeason::updateOrCreate(['name' => $season['name']], $season);
        }

        $licenses = [
            ['license_number' => 'SL-2026-0001', 'season' => 'موسم الروبيان 2026', 'boat' => 'النورس', 'captain' => 'علي الدوسري', 'port' => 'ميناء القطيف', 'region' => 'الشرقية', 'species' => 'روبيان أخضر', 'gear_type' => 'شبك جر قاعي', 'issue_date' => '2026-07-15', 'expiry_date' => '2026-12-31', 'quota_kg' => 42000, 'used_kg' => 0, 'status' => 'سارية'],
            ['license_number' => 'SL-2026-0002', 'season' => 'موسم الروبيان 2026', 'boat' => 'الفجر', 'captain' => 'سعد المطيري', 'port' => 'ميناء الجبيل', 'region' => 'الشرقية', 'species' => 'روبيان أخضر', 'gear_type' => 'شبك جر قاعي', 'issue_date' => '2026-07-18', 'expiry_date' => '2026-12-31', 'quota_kg' => 38000, 'used_kg' => 0, 'status' => 'سارية'],
            ['license_number' => 'SL-2026-0003', 'season' => 'موسم الهامور', 'boat' => 'اللؤلؤة', 'captain' => 'خالد الحربي', 'port' => 'ميناء جدة الإسلامي', 'region' => 'مكة المكرمة', 'species' => 'هامور', 'gear_type' => 'قراقير (أشراك)', 'issue_date' => '2026-09-20', 'expiry_date' => '2027-03-31', 'quota_kg' => 16000, 'used_kg' => 0, 'status' => 'معلقة'],
            ['license_number' => 'SL-2025-0188', 'season' => 'موسم الكنعد – البحر الأحمر', 'boat' => 'ريح البحر', 'captain' => 'محمد عاكش', 'port' => 'ميناء جيزان', 'region' => 'جازان', 'species' => 'كنعد', 'gear_type' => 'خيط طويل', 'issue_date' => '2025-10-30', 'expiry_date' => '2026-02-28', 'quota_kg' => 22000, 'used_kg' => 21400, 'status' => 'منتهية'],
        ];

        foreach ($licenses as $license) {
            SeasonLicense::updateOrCreate(['license_number' => $license['license_number']], $license);
        }

        $markets = [
            ['name' => 'سوق السمك المركزي – الدمام', 'code' => 'MKT-DMM', 'region' => 'الشرقية', 'governorate' => 'القطيف', 'port' => 'ميناء القطيف', 'market_type' => 'مركّب', 'fish_shops_count' => 86, 'auction_stalls_count' => 24],
            ['name' => 'سوق السمك – جدة', 'code' => 'MKT-JED', 'region' => 'مكة المكرمة', 'governorate' => 'جدة', 'port' => 'ميناء جدة الإسلامي', 'market_type' => 'مزاد', 'fish_shops_count' => 120, 'auction_stalls_count' => 32],
            ['name' => 'سوق السمك – جيزان', 'code' => 'MKT-JZN', 'region' => 'جازان', 'governorate' => 'جيزان', 'port' => 'ميناء جيزان', 'market_type' => 'مزاد', 'fish_shops_count' => 64, 'auction_stalls_count' => 18],
            ['name' => 'سوق ضباء', 'code' => 'MKT-DUB', 'region' => 'تبوك', 'governorate' => 'ضباء', 'port' => 'ميناء ضباء', 'market_type' => 'تجزئة', 'fish_shops_count' => 22, 'auction_stalls_count' => 6],
        ];

        foreach ($markets as $market) {
            Market::updateOrCreate(['name' => $market['name']], $market + ['status' => 'نشط']);
        }

        $auctions = [
            ['market' => 'سوق السمك المركزي – الدمام', 'date' => '2026-08-12', 'species' => 'هامور', 'grade' => 'ممتاز', 'offered_kg' => 1200, 'sold_kg' => 1140, 'min_price' => 42, 'max_price' => 68, 'avg_price' => 54.5, 'buyer_type' => 'جملة', 'source_port' => 'ميناء القطيف'],
            ['market' => 'سوق السمك المركزي – الدمام', 'date' => '2026-08-12', 'species' => 'شربة', 'grade' => 'أولى', 'offered_kg' => 860, 'sold_kg' => 820, 'min_price' => 18, 'max_price' => 26, 'avg_price' => 21.5, 'buyer_type' => 'تجزئة', 'source_port' => 'ميناء الجبيل'],
            ['market' => 'سوق السمك – جدة', 'date' => '2026-08-13', 'species' => 'صافي', 'grade' => 'أولى', 'offered_kg' => 1640, 'sold_kg' => 1580, 'min_price' => 22, 'max_price' => 34, 'avg_price' => 27.8, 'buyer_type' => 'مطاعم', 'source_port' => 'ميناء جدة الإسلامي'],
            ['market' => 'سوق السمك – جيزان', 'date' => '2026-08-13', 'species' => 'شعري', 'grade' => 'ممتاز', 'offered_kg' => 980, 'sold_kg' => 960, 'min_price' => 30, 'max_price' => 46, 'avg_price' => 37.2, 'buyer_type' => 'جملة', 'source_port' => 'ميناء جيزان'],
            ['market' => 'سوق ضباء', 'date' => '2026-08-14', 'species' => 'كنعد', 'grade' => 'أولى', 'offered_kg' => 420, 'sold_kg' => 400, 'min_price' => 38, 'max_price' => 52, 'avg_price' => 44.6, 'buyer_type' => 'تجزئة', 'source_port' => 'ميناء ضباء'],
        ];

        foreach ($auctions as $auction) {
            MarketAuction::updateOrCreate(
                ['market' => $auction['market'], 'date' => $auction['date'], 'species' => $auction['species']],
                $auction
            );
        }
    }
}