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
            ['name' => 'تبوك', 'code' => 'TBK', 'coast_length_km' => 620, 'governorates_count' => 6, 'ports_count' => 4, 'total_catch_tons' => 4820.5, 'active_boats' => 310, 'active_fishers' => 940],
            ['name' => 'مكة المكرمة', 'code' => 'MKH', 'coast_length_km' => 480, 'governorates_count' => 8, 'ports_count' => 6, 'total_catch_tons' => 9120.75, 'active_boats' => 720, 'active_fishers' => 2150],
            ['name' => 'جازان', 'code' => 'JZN', 'coast_length_km' => 310, 'governorates_count' => 9, 'ports_count' => 5, 'total_catch_tons' => 11340.25, 'active_boats' => 860, 'active_fishers' => 2640],
            ['name' => 'الشرقية', 'code' => 'EST', 'coast_length_km' => 720, 'governorates_count' => 7, 'ports_count' => 7, 'total_catch_tons' => 15680.4, 'active_boats' => 1180, 'active_fishers' => 3410],
        ];

        foreach ($regions as $region) {
            Region::updateOrCreate(['name' => $region['name']], $region);
        }

        $governorates = [
            ['name' => 'ضباء', 'region' => 'تبوك', 'ports_count' => 1, 'total_catch_tons' => 1420.5, 'active_boats' => 96, 'active_fishers' => 280],
            ['name' => 'الوجه', 'region' => 'تبوك', 'ports_count' => 1, 'total_catch_tons' => 1180.25, 'active_boats' => 84, 'active_fishers' => 240],
            ['name' => 'جدة', 'region' => 'مكة المكرمة', 'ports_count' => 2, 'total_catch_tons' => 4260.8, 'active_boats' => 320, 'active_fishers' => 980],
            ['name' => 'القنفذة', 'region' => 'مكة المكرمة', 'ports_count' => 2, 'total_catch_tons' => 2840.6, 'active_boats' => 210, 'active_fishers' => 640],
            ['name' => 'جيزان', 'region' => 'جازان', 'ports_count' => 2, 'total_catch_tons' => 5620.4, 'active_boats' => 410, 'active_fishers' => 1260],
            ['name' => 'فرسان', 'region' => 'جازان', 'ports_count' => 1, 'total_catch_tons' => 2180.9, 'active_boats' => 180, 'active_fishers' => 520],
            ['name' => 'الجبيل', 'region' => 'الشرقية', 'ports_count' => 2, 'total_catch_tons' => 6120.3, 'active_boats' => 460, 'active_fishers' => 1340],
            ['name' => 'القطيف', 'region' => 'الشرقية', 'ports_count' => 3, 'total_catch_tons' => 7240.7, 'active_boats' => 540, 'active_fishers' => 1580],
        ];

        foreach ($governorates as $governorate) {
            Governorate::updateOrCreate(['name' => $governorate['name']], $governorate + ['coastal' => true]);
        }

        $ports = [
            ['name' => 'ميناء ضباء', 'code' => 'DUB', 'region' => 'تبوك', 'governorate' => 'ضباء', 'lat' => 27.341, 'lng' => 35.773, 'boats_count' => 120, 'active_boats' => 96, 'fishers_count' => 280, 'daily_trips' => 24, 'monthly_trips' => 640, 'total_catch_tons' => 1420.5, 'statistics_staff' => 3],
            ['name' => 'ميناء الوجه', 'code' => 'WJH', 'region' => 'تبوك', 'governorate' => 'الوجه', 'lat' => 26.239, 'lng' => 36.472, 'boats_count' => 98, 'active_boats' => 84, 'fishers_count' => 240, 'daily_trips' => 18, 'monthly_trips' => 520, 'total_catch_tons' => 1180.25, 'statistics_staff' => 2],
            ['name' => 'ميناء جدة الإسلامي', 'code' => 'JED', 'region' => 'مكة المكرمة', 'governorate' => 'جدة', 'lat' => 21.4595, 'lng' => 39.1728, 'boats_count' => 340, 'active_boats' => 320, 'fishers_count' => 980, 'daily_trips' => 62, 'monthly_trips' => 1780, 'total_catch_tons' => 4260.8, 'statistics_staff' => 6],
            ['name' => 'ميناء القنفذة', 'code' => 'QNF', 'region' => 'مكة المكرمة', 'governorate' => 'القنفذة', 'lat' => 19.1264, 'lng' => 41.0789, 'boats_count' => 226, 'active_boats' => 210, 'fishers_count' => 640, 'daily_trips' => 41, 'monthly_trips' => 1180, 'total_catch_tons' => 2840.6, 'statistics_staff' => 4],
            ['name' => 'ميناء جيزان', 'code' => 'JZN', 'region' => 'جازان', 'governorate' => 'جيزان', 'lat' => 16.8892, 'lng' => 42.5511, 'boats_count' => 430, 'active_boats' => 410, 'fishers_count' => 1260, 'daily_trips' => 78, 'monthly_trips' => 2240, 'total_catch_tons' => 5620.4, 'statistics_staff' => 7],
            ['name' => 'ميناء فرسان', 'code' => 'FRS', 'region' => 'جازان', 'governorate' => 'فرسان', 'lat' => 16.7025, 'lng' => 42.1181, 'boats_count' => 190, 'active_boats' => 180, 'fishers_count' => 520, 'daily_trips' => 32, 'monthly_trips' => 920, 'total_catch_tons' => 2180.9, 'statistics_staff' => 3],
            ['name' => 'ميناء الجبيل', 'code' => 'JBL', 'region' => 'الشرقية', 'governorate' => 'الجبيل', 'lat' => 27.005, 'lng' => 49.661, 'boats_count' => 480, 'active_boats' => 460, 'fishers_count' => 1340, 'daily_trips' => 86, 'monthly_trips' => 2480, 'total_catch_tons' => 6120.3, 'statistics_staff' => 8],
            ['name' => 'ميناء القطيف', 'code' => 'QTF', 'region' => 'الشرقية', 'governorate' => 'القطيف', 'lat' => 26.5652, 'lng' => 49.9968, 'boats_count' => 560, 'active_boats' => 540, 'fishers_count' => 1580, 'daily_trips' => 94, 'monthly_trips' => 2720, 'total_catch_tons' => 7240.7, 'statistics_staff' => 9],
        ];

        foreach ($ports as $port) {
            Port::updateOrCreate(['name' => $port['name']], $port + ['status' => 'نشط']);
        }

        $sites = [
            ['name' => 'أبو شقور', 'region' => 'تبوك', 'nearest_port' => 'ميناء ضباء', 'lat' => 27.42, 'lng' => 35.61, 'depth_m' => 28.5, 'trips_count' => 180, 'boats_count' => 34, 'catch_kg' => 42800, 'avg_catch_per_trip' => 237.8, 'pressure_level' => 'طبيعي'],
            ['name' => 'البوجه', 'region' => 'تبوك', 'nearest_port' => 'ميناء الوجه', 'lat' => 26.31, 'lng' => 36.28, 'depth_m' => 34.0, 'trips_count' => 142, 'boats_count' => 28, 'catch_kg' => 31600, 'avg_catch_per_trip' => 222.5, 'pressure_level' => 'طبيعي'],
            ['name' => 'شعيبة الرايس', 'region' => 'مكة المكرمة', 'nearest_port' => 'ميناء جدة الإسلامي', 'lat' => 21.62, 'lng' => 38.94, 'depth_m' => 45.0, 'trips_count' => 420, 'boats_count' => 86, 'catch_kg' => 128400, 'avg_catch_per_trip' => 305.7, 'pressure_level' => 'مراقبة'],
            ['name' => 'مصب عتود', 'region' => 'جازان', 'nearest_port' => 'ميناء جيزان', 'lat' => 16.94, 'lng' => 42.41, 'depth_m' => 18.0, 'trips_count' => 640, 'boats_count' => 124, 'catch_kg' => 214600, 'avg_catch_per_trip' => 335.3, 'pressure_level' => 'ضغط مرتفع'],
            ['name' => 'أبو علي', 'region' => 'الشرقية', 'nearest_port' => 'ميناء الجبيل', 'lat' => 27.31, 'lng' => 49.58, 'depth_m' => 12.5, 'trips_count' => 780, 'boats_count' => 168, 'catch_kg' => 286400, 'avg_catch_per_trip' => 367.2, 'pressure_level' => 'إنذار'],
            ['name' => 'رأس تنورة', 'region' => 'الشرقية', 'nearest_port' => 'ميناء القطيف', 'lat' => 26.64, 'lng' => 50.16, 'depth_m' => 16.0, 'trips_count' => 520, 'boats_count' => 112, 'catch_kg' => 168200, 'avg_catch_per_trip' => 323.5, 'pressure_level' => 'مراقبة'],
        ];

        foreach ($sites as $site) {
            FishingSite::updateOrCreate(['name' => $site['name']], $site);
        }
    }
}