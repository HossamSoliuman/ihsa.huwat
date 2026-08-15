<?php

namespace Database\Seeders;

use App\Models\Boat;
use App\Models\FishingSeason;
use App\Models\SeasonLicense;
use Illuminate\Database\Seeder;

class SeasonsSeeder extends Seeder
{
    public function run(): void
    {
        $seasons = [
            ['name' => 'موسم الروبيان — الخليج العربي', 'species' => 'الروبيان', 'sea' => 'الخليج العربي', 'region' => 'المنطقة الشرقية', 'start_month' => 8, 'end_month' => 1, 'start_date' => '2026-08-01', 'end_date' => '2027-01-31', 'gear' => 'شبك جر', 'gear_type' => 'شبك جر', 'license_type' => 'رخصة موسمية', 'licenses_max' => 400, 'boats_count' => 320, 'min_size_cm' => 12, 'authority' => 'وزارة البيئة والمياه والزراعة', 'decision_number' => 'ق-1447/12', 'decision_date' => '2026-07-01', 'approval_status' => 'منشور', 'quota_tons' => 4500, 'used_quota_tons' => 380, 'status' => 'مفتوح'],
            ['name' => 'موسم الكنعد — الخليج العربي', 'species' => 'الكنعد', 'sea' => 'الخليج العربي', 'region' => 'المنطقة الشرقية', 'start_month' => 10, 'end_month' => 3, 'start_date' => '2026-10-01', 'end_date' => '2027-03-31', 'gear' => 'شبك خيشوم', 'gear_type' => 'شبك خيشوم', 'license_type' => 'رخصة موسمية', 'licenses_max' => 250, 'boats_count' => 180, 'min_size_cm' => 45, 'authority' => 'وزارة البيئة والمياه والزراعة', 'decision_number' => 'ق-1447/18', 'decision_date' => '2026-09-01', 'approval_status' => 'معتمد', 'quota_tons' => 2800, 'used_quota_tons' => 0, 'status' => 'مغلق'],
            ['name' => 'موسم الحريد — البحر الأحمر', 'species' => 'الحريد', 'sea' => 'البحر الأحمر', 'region' => 'جازان', 'start_month' => 4, 'end_month' => 9, 'start_date' => '2026-04-01', 'end_date' => '2026-09-30', 'gear' => 'سنارة', 'gear_type' => 'سنارة', 'license_type' => 'رخصة موسمية', 'licenses_max' => 180, 'boats_count' => 140, 'min_size_cm' => 20, 'authority' => 'وزارة البيئة والمياه والزراعة', 'decision_number' => 'ق-1447/05', 'decision_date' => '2026-03-10', 'approval_status' => 'منشور', 'quota_tons' => 1200, 'used_quota_tons' => 940, 'status' => 'مفتوح'],
            ['name' => 'موسم الناجل — البحر الأحمر', 'species' => 'الناجل', 'sea' => 'البحر الأحمر', 'region' => 'مكة المكرمة', 'start_month' => 6, 'end_month' => 12, 'start_date' => '2026-06-01', 'end_date' => '2026-12-31', 'gear' => 'سنارة', 'gear_type' => 'سنارة', 'license_type' => 'رخصة موسمية', 'licenses_max' => 150, 'boats_count' => 110, 'min_size_cm' => 25, 'authority' => 'وزارة البيئة والمياه والزراعة', 'decision_number' => 'ق-1447/09', 'decision_date' => '2026-05-15', 'approval_status' => 'قيد المراجعة', 'quota_tons' => 900, 'used_quota_tons' => 310, 'status' => 'مفتوح'],
        ];

        foreach ($seasons as $item) {
            FishingSeason::updateOrCreate(
                ['species' => $item['species'], 'region' => $item['region']],
                $item
            );
        }

        $licenses = [
            ['season_species' => 'الروبيان', 'license_number' => 'SL-1001', 'boat_name' => 'نجم الخليج', 'fisher_name' => 'أحمد الشمري', 'holder_name' => 'أحمد الشمري', 'issue_date' => '2026-07-25', 'expiry_date' => '2027-01-31', 'gear_type' => 'شبك جر', 'allowed_area' => 'مياه القطيف — شمال جزيرة تاروت', 'quota_kg' => 45000, 'used_kg' => 6200, 'status' => 'سارية'],
            ['season_species' => 'الروبيان', 'license_number' => 'SL-1002', 'boat_name' => 'لؤلؤة الدمام', 'fisher_name' => 'سالم القحطاني', 'holder_name' => 'سالم القحطاني', 'issue_date' => '2026-07-28', 'expiry_date' => '2027-01-31', 'gear_type' => 'شبك جر', 'allowed_area' => 'مياه الدمام — مصيدة أبو علي', 'quota_kg' => 60000, 'used_kg' => 9100, 'status' => 'سارية'],
            ['season_species' => 'الحريد', 'license_number' => 'SL-2001', 'boat_name' => 'درة فرسان', 'fisher_name' => 'علي عسيري', 'holder_name' => 'علي عسيري', 'issue_date' => '2026-04-01', 'expiry_date' => '2026-09-30', 'gear_type' => 'سنارة', 'allowed_area' => 'أرخبيل فرسان', 'quota_kg' => 30000, 'used_kg' => 24800, 'status' => 'سارية'],
            ['season_species' => 'الناجل', 'license_number' => 'SL-3001', 'boat_name' => 'فجر البحر', 'fisher_name' => 'خالد الحربي', 'holder_name' => 'خالد الحربي', 'issue_date' => '2026-06-05', 'expiry_date' => '2026-12-31', 'gear_type' => 'سنارة', 'allowed_area' => 'شعاب أبحر — شمال جدة', 'quota_kg' => 20000, 'used_kg' => 7400, 'status' => 'سارية'],
        ];

        foreach ($licenses as $item) {
            $season = FishingSeason::where('species', $item['season_species'])->first();
            $boat = Boat::where('name', $item['boat_name'])->first();
            SeasonLicense::updateOrCreate(
                ['license_number' => $item['license_number']],
                ['fishing_season_id' => $season->id, 'boat_id' => $boat?->id]
                    + collect($item)->except('season_species')->all()
            );
        }

        // عدّادات الرخص مشتقّة من الرخص الفعلية
        foreach (FishingSeason::with('licenses')->get() as $season) {
            $season->update([
                'licenses_issued' => $season->licenses->where('status', '!=', 'ملغاة')->count(),
                'licenses_active' => $season->licenses->where('status', 'سارية')->count(),
            ]);
        }
    }
}