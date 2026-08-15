<?php

namespace Database\Seeders;

use App\Models\FishingSite;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use Illuminate\Database\Seeder;

class GeographicSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['name' => 'المنطقة الشرقية', 'code' => 'EST', 'coast_length_km' => 1100, 'total_catch_tons' => 18400, 'active_fishers' => 3200, 'active_boats' => 1450],
            ['name' => 'مكة المكرمة', 'code' => 'MKA', 'coast_length_km' => 600, 'total_catch_tons' => 14200, 'active_fishers' => 2800, 'active_boats' => 1200],
            ['name' => 'تبوك', 'code' => 'TBK', 'coast_length_km' => 700, 'total_catch_tons' => 6800, 'active_fishers' => 1100, 'active_boats' => 520],
            ['name' => 'جازان', 'code' => 'JZN', 'coast_length_km' => 300, 'total_catch_tons' => 11500, 'active_fishers' => 2400, 'active_boats' => 980],
        ];

        foreach ($regions as $region) {
            Region::updateOrCreate(['name' => $region['name']], $region);
        }

        $governorates = [
            ['region' => 'المنطقة الشرقية', 'name' => 'القطيف', 'code' => 'QTF', 'ports_count' => 2, 'active_boats' => 780, 'active_fishers' => 1700, 'total_catch_tons' => 9800],
            ['region' => 'المنطقة الشرقية', 'name' => 'الدمام', 'code' => 'DMM', 'ports_count' => 1, 'active_boats' => 670, 'active_fishers' => 1500, 'total_catch_tons' => 8600],
            ['region' => 'مكة المكرمة', 'name' => 'جدة', 'code' => 'JED', 'ports_count' => 2, 'active_boats' => 820, 'active_fishers' => 1900, 'total_catch_tons' => 9400],
            ['region' => 'مكة المكرمة', 'name' => 'الليث', 'code' => 'LTH', 'ports_count' => 1, 'active_boats' => 380, 'active_fishers' => 900, 'total_catch_tons' => 4800],
            ['region' => 'تبوك', 'name' => 'ضباء', 'code' => 'DBA', 'ports_count' => 1, 'active_boats' => 320, 'active_fishers' => 650, 'total_catch_tons' => 4100],
            ['region' => 'تبوك', 'name' => 'الوجه', 'code' => 'WJH', 'ports_count' => 1, 'active_boats' => 200, 'active_fishers' => 450, 'total_catch_tons' => 2700],
            ['region' => 'جازان', 'name' => 'جيزان', 'code' => 'GZN', 'ports_count' => 1, 'active_boats' => 620, 'active_fishers' => 1500, 'total_catch_tons' => 7300],
            ['region' => 'جازان', 'name' => 'فرسان', 'code' => 'FRS', 'ports_count' => 1, 'active_boats' => 360, 'active_fishers' => 900, 'total_catch_tons' => 4200],
        ];

        foreach ($governorates as $item) {
            $region = Region::where('name', $item['region'])->first();
            Governorate::updateOrCreate(
                ['name' => $item['name']],
                ['region_id' => $region->id] + collect($item)->except('region')->all()
            );
        }

        $ports = [
            ['governorate' => 'القطيف', 'name' => 'ميناء القطيف', 'code' => 'PQTF', 'lat' => 26.565, 'lng' => 50.008, 'boats_count' => 520, 'active_boats' => 410, 'fishers_count' => 1150, 'daily_trips' => 85, 'monthly_trips' => 2400, 'total_catch_tons' => 6200, 'statistics_staff' => 6],
            ['governorate' => 'القطيف', 'name' => 'ميناء دارين', 'code' => 'PDRN', 'lat' => 26.556, 'lng' => 50.075, 'boats_count' => 260, 'active_boats' => 190, 'fishers_count' => 550, 'daily_trips' => 40, 'monthly_trips' => 1150, 'total_catch_tons' => 3600, 'statistics_staff' => 3],
            ['governorate' => 'الدمام', 'name' => 'ميناء الدمام', 'code' => 'PDMM', 'lat' => 26.435, 'lng' => 50.104, 'boats_count' => 670, 'active_boats' => 520, 'fishers_count' => 1500, 'daily_trips' => 95, 'monthly_trips' => 2750, 'total_catch_tons' => 8600, 'statistics_staff' => 7],
            ['governorate' => 'جدة', 'name' => 'ميناء جدة للصيد', 'code' => 'PJED', 'lat' => 21.485, 'lng' => 39.192, 'boats_count' => 560, 'active_boats' => 430, 'fishers_count' => 1300, 'daily_trips' => 80, 'monthly_trips' => 2300, 'total_catch_tons' => 6400, 'statistics_staff' => 6],
            ['governorate' => 'جدة', 'name' => 'مرسى ثول', 'code' => 'PTHL', 'lat' => 22.283, 'lng' => 39.106, 'boats_count' => 260, 'active_boats' => 200, 'fishers_count' => 600, 'daily_trips' => 35, 'monthly_trips' => 1000, 'total_catch_tons' => 3000, 'statistics_staff' => 3],
            ['governorate' => 'الليث', 'name' => 'ميناء الليث', 'code' => 'PLTH', 'lat' => 20.15, 'lng' => 40.266, 'boats_count' => 380, 'active_boats' => 290, 'fishers_count' => 900, 'daily_trips' => 55, 'monthly_trips' => 1600, 'total_catch_tons' => 4800, 'statistics_staff' => 4],
            ['governorate' => 'ضباء', 'name' => 'ميناء ضباء', 'code' => 'PDBA', 'lat' => 27.351, 'lng' => 35.69, 'boats_count' => 320, 'active_boats' => 240, 'fishers_count' => 650, 'daily_trips' => 45, 'monthly_trips' => 1300, 'total_catch_tons' => 4100, 'statistics_staff' => 4],
            ['governorate' => 'جيزان', 'name' => 'ميناء جيزان', 'code' => 'PGZN', 'lat' => 16.889, 'lng' => 42.551, 'boats_count' => 620, 'active_boats' => 480, 'fishers_count' => 1500, 'daily_trips' => 90, 'monthly_trips' => 2600, 'total_catch_tons' => 7300, 'statistics_staff' => 6],
        ];

        foreach ($ports as $item) {
            $governorate = Governorate::where('name', $item['governorate'])->first();
            Port::updateOrCreate(
                ['name' => $item['name']],
                ['governorate_id' => $governorate->id] + collect($item)->except('governorate')->all()
            );
        }

        $sites = [
            ['port' => 'ميناء القطيف', 'name' => 'شعاب تاروت', 'site_type' => 'شعاب مرجانية', 'lat' => 26.62, 'lng' => 50.12, 'pressure_level' => 'متوسط', 'catch_kg' => 84000],
            ['port' => 'ميناء الدمام', 'name' => 'مصيدة أبو علي', 'site_type' => 'مياه ضحلة', 'lat' => 27.05, 'lng' => 49.55, 'pressure_level' => 'مرتفع', 'catch_kg' => 112000],
            ['port' => 'ميناء جدة للصيد', 'name' => 'شعاب أبحر', 'site_type' => 'شعاب مرجانية', 'lat' => 21.72, 'lng' => 39.08, 'pressure_level' => 'متوسط', 'catch_kg' => 76000],
            ['port' => 'ميناء الليث', 'name' => 'جزر أم القماري', 'site_type' => 'جزر', 'lat' => 19.95, 'lng' => 40.1, 'pressure_level' => 'منخفض', 'catch_kg' => 43000],
            ['port' => 'ميناء ضباء', 'name' => 'خليج ضباء الشمالي', 'site_type' => 'خليج', 'lat' => 27.45, 'lng' => 35.6, 'pressure_level' => 'منخفض', 'catch_kg' => 38000],
            ['port' => 'ميناء جيزان', 'name' => 'أرخبيل فرسان', 'site_type' => 'جزر', 'lat' => 16.7, 'lng' => 42.12, 'pressure_level' => 'مرتفع', 'catch_kg' => 96000],
        ];

        foreach ($sites as $item) {
            $port = Port::where('name', $item['port'])->first();
            FishingSite::updateOrCreate(
                ['name' => $item['name']],
                ['port_id' => $port->id] + collect($item)->except('port')->all()
            );
        }

        // عدّادات المنطقة مشتقّة من محافظاتها حتى لا تتعارض مع البيانات المدخلة
        foreach (Region::with('governorates')->get() as $region) {
            $region->update([
                'governorates_count' => $region->governorates->count(),
                'ports_count' => (int) $region->governorates->sum('ports_count'),
            ]);
        }
    }
}