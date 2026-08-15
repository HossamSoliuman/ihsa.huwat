<?php

namespace Database\Seeders;

use App\Models\Boat;
use App\Models\Fisher;
use App\Models\GearType;
use App\Models\Port;
use App\Models\Species;
use App\Models\StatisticsOfficer;
use Illuminate\Database\Seeder;

class FleetSeeder extends Seeder
{
    public function run(): void
    {
        $species = [
            ['code' => 101, 'name_ar' => 'الهامور', 'name_sci' => 'Epinephelus coioides', 'name_en' => 'Orange-spotted grouper', 'category' => 'أسماك', 'avg_weight_kg' => 3.2, 'avg_length_cm' => 55, 'season' => 'طوال العام', 'regions' => 'الخليج العربي', 'status' => 'مراقبة'],
            ['code' => 102, 'name_ar' => 'الشعري', 'name_sci' => 'Lethrinus nebulosus', 'name_en' => 'Spangled emperor', 'category' => 'أسماك', 'avg_weight_kg' => 1.4, 'avg_length_cm' => 42, 'season' => 'طوال العام', 'regions' => 'الخليج العربي، البحر الأحمر', 'status' => 'مستقر'],
            ['code' => 103, 'name_ar' => 'الكنعد', 'name_sci' => 'Scomberomorus commerson', 'name_en' => 'Narrow-barred Spanish mackerel', 'category' => 'أسماك', 'avg_weight_kg' => 4.8, 'avg_length_cm' => 85, 'season' => 'أكتوبر - مارس', 'regions' => 'الخليج العربي', 'status' => 'ضغط صيد مرتفع'],
            ['code' => 104, 'name_ar' => 'الناجل', 'name_sci' => 'Plectropomus pessuliferus', 'name_en' => 'Roving coralgrouper', 'category' => 'أسماك', 'avg_weight_kg' => 2.1, 'avg_length_cm' => 48, 'season' => 'طوال العام', 'regions' => 'البحر الأحمر', 'status' => 'مراقبة'],
            ['code' => 105, 'name_ar' => 'الحريد', 'name_sci' => 'Hipposcarus harid', 'name_en' => 'Longnose parrotfish', 'category' => 'أسماك', 'avg_weight_kg' => 1.1, 'avg_length_cm' => 38, 'season' => 'أبريل - سبتمبر', 'regions' => 'البحر الأحمر', 'status' => 'مستقر'],
            ['code' => 106, 'name_ar' => 'الروبيان', 'name_sci' => 'Penaeus semisulcatus', 'name_en' => 'Green tiger prawn', 'category' => 'روبيان', 'avg_weight_kg' => 0.03, 'avg_length_cm' => 18, 'season' => 'أغسطس - يناير', 'regions' => 'الخليج العربي', 'status' => 'مستقر'],
        ];

        foreach ($species as $item) {
            Species::updateOrCreate(['name_ar' => $item['name_ar']], $item);
        }

        $gearTypes = [
            ['name' => 'شبك خيشوم', 'code' => 'GN', 'category' => 'شباك', 'selectivity' => 'متوسطة'],
            ['name' => 'سنارة', 'code' => 'HL', 'category' => 'خيوط', 'selectivity' => 'عالية'],
            ['name' => 'خيط طويل', 'code' => 'LL', 'category' => 'خيوط', 'selectivity' => 'متوسطة'],
            ['name' => 'شبك جر', 'code' => 'TR', 'category' => 'شباك', 'selectivity' => 'منخفضة'],
            ['name' => 'أشراك', 'code' => 'TP', 'category' => 'فخاخ', 'selectivity' => 'عالية'],
        ];

        foreach ($gearTypes as $item) {
            GearType::updateOrCreate(['name' => $item['name']], $item);
        }

        $boats = [
            ['port' => 'ميناء القطيف', 'name' => 'نجم الخليج', 'boat_number' => 'B-1001', 'boat_type' => 'طراد', 'length_m' => 12.5, 'captain' => 'أحمد الشمري', 'crew_count' => 4, 'status' => 'نشط'],
            ['port' => 'ميناء الدمام', 'name' => 'لؤلؤة الدمام', 'boat_number' => 'B-1002', 'boat_type' => 'لنش', 'length_m' => 18.0, 'captain' => 'سالم القحطاني', 'crew_count' => 7, 'status' => 'نشط'],
            ['port' => 'ميناء جدة للصيد', 'name' => 'فجر البحر', 'boat_number' => 'B-2001', 'boat_type' => 'طراد', 'length_m' => 11.0, 'captain' => 'خالد الحربي', 'crew_count' => 3, 'status' => 'نشط'],
            ['port' => 'ميناء الليث', 'name' => 'ريح الجنوب', 'boat_number' => 'B-2002', 'boat_type' => 'قارب تقليدي', 'length_m' => 9.5, 'captain' => 'فهد الزهراني', 'crew_count' => 3, 'status' => 'نشط'],
            ['port' => 'ميناء ضباء', 'name' => 'شراع تبوك', 'boat_number' => 'B-3001', 'boat_type' => 'طراد', 'length_m' => 10.5, 'captain' => 'ماجد العنزي', 'crew_count' => 3, 'status' => 'صيانة'],
            ['port' => 'ميناء جيزان', 'name' => 'درة فرسان', 'boat_number' => 'B-4001', 'boat_type' => 'لنش', 'length_m' => 16.0, 'captain' => 'علي عسيري', 'crew_count' => 6, 'status' => 'نشط', 'violations_count' => 1],
        ];

        foreach ($boats as $item) {
            $port = Port::where('name', $item['port'])->first();
            Boat::updateOrCreate(
                ['boat_number' => $item['boat_number']],
                ['port_id' => $port->id] + collect($item)->except('port')->all()
            );
        }

        $fishers = [
            ['port' => 'ميناء القطيف', 'name' => 'أحمد الشمري', 'national_id' => '1010101010', 'license_number' => 'F-501', 'role' => 'كابتن', 'phone' => '0551000001'],
            ['port' => 'ميناء الدمام', 'name' => 'سالم القحطاني', 'national_id' => '1020202020', 'license_number' => 'F-502', 'role' => 'كابتن', 'phone' => '0551000002'],
            ['port' => 'ميناء جدة للصيد', 'name' => 'خالد الحربي', 'national_id' => '1030303030', 'license_number' => 'F-503', 'role' => 'كابتن', 'phone' => '0551000003'],
            ['port' => 'ميناء الليث', 'name' => 'فهد الزهراني', 'national_id' => '1040404040', 'license_number' => 'F-504', 'role' => 'كابتن', 'phone' => '0551000004'],
            ['port' => 'ميناء ضباء', 'name' => 'ماجد العنزي', 'national_id' => '1050505050', 'license_number' => 'F-505', 'role' => 'كابتن', 'phone' => '0551000005'],
            ['port' => 'ميناء جيزان', 'name' => 'علي عسيري', 'national_id' => '1060606060', 'license_number' => 'F-506', 'role' => 'كابتن', 'phone' => '0551000006'],
        ];

        foreach ($fishers as $item) {
            $port = Port::where('name', $item['port'])->first();
            Fisher::updateOrCreate(
                ['national_id' => $item['national_id']],
                ['port_id' => $port->id] + collect($item)->except('port')->all()
            );
        }

        $officers = [
            ['port' => 'ميناء القطيف', 'name' => 'محمد العوامي', 'employee_number' => 'SO-01', 'shift' => 'صباحية', 'trips_counted' => 1840],
            ['port' => 'ميناء الدمام', 'name' => 'عبدالله الدوسري', 'employee_number' => 'SO-02', 'shift' => 'مسائية', 'trips_counted' => 1620],
            ['port' => 'ميناء جدة للصيد', 'name' => 'ياسر الغامدي', 'employee_number' => 'SO-03', 'shift' => 'صباحية', 'trips_counted' => 1510],
            ['port' => 'ميناء جيزان', 'name' => 'حسن مدخلي', 'employee_number' => 'SO-04', 'shift' => 'صباحية', 'trips_counted' => 1730],
        ];

        foreach ($officers as $item) {
            $port = Port::where('name', $item['port'])->first();
            StatisticsOfficer::updateOrCreate(
                ['employee_number' => $item['employee_number']],
                ['port_id' => $port->id] + collect($item)->except('port')->all()
            );
        }
    }
}