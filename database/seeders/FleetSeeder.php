<?php

namespace Database\Seeders;

use App\Models\Boat;
use App\Models\Fisher;
use App\Models\GearType;
use App\Models\Species;
use App\Models\StatisticsOfficer;
use Illuminate\Database\Seeder;

class FleetSeeder extends Seeder
{
    public function run(): void
    {
        $species = [
            ['code' => 1001, 'name_ar' => 'هامور', 'name_sci' => 'Epinephelus coioides', 'name_en' => 'Orange-spotted grouper', 'name_local_gulf' => 'هامور', 'name_local_red_sea' => 'ناجل', 'record_type' => 'نوع سمك', 'category' => 'أسماك', 'avg_weight_kg' => 3.4, 'avg_length_cm' => 62, 'season' => 'أكتوبر – مارس', 'top_port' => 'ميناء القطيف', 'status' => 'ضغط صيد مرتفع', 'review_status' => 'مصحح وموثق'],
            ['code' => 1002, 'name_ar' => 'شعري', 'name_sci' => 'Lethrinus nebulosus', 'name_en' => 'Spangled emperor', 'name_local_gulf' => 'شعري', 'name_local_red_sea' => 'شعور', 'record_type' => 'نوع سمك', 'category' => 'أسماك', 'avg_weight_kg' => 1.8, 'avg_length_cm' => 44, 'season' => 'طول العام', 'top_port' => 'ميناء جيزان', 'status' => 'مستقر', 'review_status' => 'مصحح وموثق'],
            ['code' => 1003, 'name_ar' => 'كنعد', 'name_sci' => 'Scomberomorus commerson', 'name_en' => 'Narrow-barred Spanish mackerel', 'name_local_gulf' => 'كنعد', 'name_local_red_sea' => 'دراك', 'record_type' => 'نوع سمك', 'category' => 'أسماك', 'avg_weight_kg' => 6.2, 'avg_length_cm' => 88, 'season' => 'نوفمبر – فبراير', 'top_port' => 'ميناء الجبيل', 'status' => 'مراقبة', 'review_status' => 'منسق آليًا'],
            ['code' => 1004, 'name_ar' => 'صافي', 'name_sci' => 'Siganus rivulatus', 'name_en' => 'Marbled spinefoot', 'name_local_gulf' => 'صافي', 'name_local_red_sea' => 'صافي', 'record_type' => 'نوع سمك', 'category' => 'أسماك', 'avg_weight_kg' => 0.4, 'avg_length_cm' => 24, 'season' => 'طول العام', 'top_port' => 'ميناء جدة الإسلامي', 'status' => 'مستقر', 'review_status' => 'مصحح وموثق'],
            ['code' => 2001, 'name_ar' => 'روبيان أخضر', 'name_sci' => 'Penaeus semisulcatus', 'name_en' => 'Green tiger prawn', 'name_local_gulf' => 'روبيان', 'record_type' => 'نوع سمك', 'category' => 'روبيان', 'avg_weight_kg' => 0.05, 'avg_length_cm' => 16, 'season' => 'أغسطس – ديسمبر', 'top_port' => 'ميناء القطيف', 'status' => 'ضغط صيد مرتفع', 'review_status' => 'مصحح وموثق'],
            ['code' => 3001, 'name_ar' => 'شربة', 'name_sci' => 'Portunus segnis', 'name_en' => 'Blue swimming crab', 'name_local_gulf' => 'قبقب', 'record_type' => 'نوع سمك', 'category' => 'قشريات', 'avg_weight_kg' => 0.22, 'avg_length_cm' => 14, 'season' => 'طول العام', 'top_port' => 'ميناء الجبيل', 'status' => 'مستقر', 'review_status' => 'مقبول مبدئيًا'],
        ];

        foreach ($species as $item) {
            Species::updateOrCreate(['name_ar' => $item['name_ar']], $item + ['directory_status' => 'نشط']);
        }

        $gears = [
            ['name' => 'شبك خيشوم', 'code' => 'GN', 'category' => 'شباك', 'isscfg_code' => 'GNS', 'min_mesh_size_mm' => 60, 'selective' => true],
            ['name' => 'سنارة يدوية', 'code' => 'HL', 'category' => 'خطوط', 'isscfg_code' => 'LHP', 'min_mesh_size_mm' => null, 'selective' => true],
            ['name' => 'خيط طويل', 'code' => 'LL', 'category' => 'خطوط', 'isscfg_code' => 'LLS', 'min_mesh_size_mm' => null, 'selective' => true],
            ['name' => 'شبك جر قاعي', 'code' => 'TR', 'category' => 'جر', 'isscfg_code' => 'OTB', 'min_mesh_size_mm' => 40, 'selective' => false],
            ['name' => 'قراقير (أشراك)', 'code' => 'TP', 'category' => 'أشراك', 'isscfg_code' => 'FPO', 'min_mesh_size_mm' => 50, 'selective' => true],
        ];

        foreach ($gears as $gear) {
            GearType::updateOrCreate(['name' => $gear['name']], $gear + ['active' => true]);
        }

        $boats = [
            ['name' => 'الفجر', 'boat_number' => 'JBL-1042', 'port' => 'ميناء الجبيل', 'region' => 'الشرقية', 'governorate' => 'الجبيل', 'captain' => 'سعد المطيري', 'boat_type' => 'لنش', 'length_m' => 12.5, 'crew_capacity' => 6, 'license_type' => 'حرفي', 'license_number' => 'LIC-88214', 'license_expiry' => '2026-11-30', 'license_status' => 'سارية', 'trips_count' => 184, 'total_catch_kg' => 42600, 'violations_count' => 0, 'status' => 'نشط'],
            ['name' => 'النورس', 'boat_number' => 'QTF-2287', 'port' => 'ميناء القطيف', 'region' => 'الشرقية', 'governorate' => 'القطيف', 'captain' => 'علي الدوسري', 'boat_type' => 'طراد', 'length_m' => 16.0, 'crew_capacity' => 9, 'license_type' => 'تجاري', 'license_number' => 'LIC-77410', 'license_expiry' => '2026-09-15', 'license_status' => 'قريبة الانتهاء', 'trips_count' => 226, 'total_catch_kg' => 68400, 'violations_count' => 1, 'status' => 'نشط'],
            ['name' => 'ريح البحر', 'boat_number' => 'JZN-3391', 'port' => 'ميناء جيزان', 'region' => 'جازان', 'governorate' => 'جيزان', 'captain' => 'محمد عاكش', 'boat_type' => 'قارب صغير', 'length_m' => 8.0, 'crew_capacity' => 4, 'license_type' => 'حرفي', 'license_number' => 'LIC-55129', 'license_expiry' => '2025-12-31', 'license_status' => 'منتهية', 'trips_count' => 142, 'total_catch_kg' => 28800, 'violations_count' => 3, 'status' => 'متوقف'],
            ['name' => 'اللؤلؤة', 'boat_number' => 'JED-4416', 'port' => 'ميناء جدة الإسلامي', 'region' => 'مكة المكرمة', 'governorate' => 'جدة', 'captain' => 'خالد الحربي', 'boat_type' => 'لنش', 'length_m' => 13.2, 'crew_capacity' => 7, 'license_type' => 'تجاري', 'license_number' => 'LIC-91002', 'license_expiry' => '2027-03-20', 'license_status' => 'سارية', 'trips_count' => 198, 'total_catch_kg' => 51200, 'violations_count' => 0, 'status' => 'نشط'],
            ['name' => 'سهيل', 'boat_number' => 'DUB-5520', 'port' => 'ميناء ضباء', 'region' => 'تبوك', 'governorate' => 'ضباء', 'captain' => 'فهد البلوي', 'boat_type' => 'قارب صغير', 'length_m' => 7.5, 'crew_capacity' => 3, 'license_type' => 'حرفي', 'license_number' => 'LIC-33875', 'license_expiry' => '2026-06-01', 'license_status' => 'سارية', 'trips_count' => 96, 'total_catch_kg' => 18400, 'violations_count' => 0, 'status' => 'نشط'],
        ];

        foreach ($boats as $boat) {
            Boat::updateOrCreate(['boat_number' => $boat['boat_number']], $boat);
        }

        $fishers = [
            ['name' => 'سعد المطيري', 'national_id' => '1045887421', 'phone' => '0555120044', 'role' => 'كابتن', 'port' => 'ميناء الجبيل', 'region' => 'الشرقية', 'governorate' => 'الجبيل', 'boat' => 'الفجر', 'license_number' => 'FSH-1201', 'license_expiry' => '2026-11-30', 'experience_years' => 18, 'trips_count' => 184],
            ['name' => 'علي الدوسري', 'national_id' => '1077412903', 'phone' => '0555871220', 'role' => 'كابتن', 'port' => 'ميناء القطيف', 'region' => 'الشرقية', 'governorate' => 'القطيف', 'boat' => 'النورس', 'license_number' => 'FSH-1288', 'license_expiry' => '2026-09-15', 'experience_years' => 22, 'trips_count' => 226],
            ['name' => 'محمد عاكش', 'national_id' => '1099254410', 'phone' => '0544120988', 'role' => 'كابتن', 'port' => 'ميناء جيزان', 'region' => 'جازان', 'governorate' => 'جيزان', 'boat' => 'ريح البحر', 'license_number' => 'FSH-1330', 'license_expiry' => '2025-12-31', 'experience_years' => 12, 'trips_count' => 142, 'status' => 'موقوف'],
            ['name' => 'ناصر الزهراني', 'national_id' => '1066231145', 'phone' => '0533871402', 'role' => 'صياد', 'port' => 'ميناء جدة الإسلامي', 'region' => 'مكة المكرمة', 'governorate' => 'جدة', 'boat' => 'اللؤلؤة', 'license_number' => 'FSH-1402', 'license_expiry' => '2027-03-20', 'experience_years' => 9, 'trips_count' => 118],
            ['name' => 'فهد البلوي', 'national_id' => '1088740219', 'phone' => '0566120388', 'role' => 'كابتن', 'port' => 'ميناء ضباء', 'region' => 'تبوك', 'governorate' => 'ضباء', 'boat' => 'سهيل', 'license_number' => 'FSH-1466', 'license_expiry' => '2026-06-01', 'experience_years' => 15, 'trips_count' => 96],
        ];

        foreach ($fishers as $fisher) {
            Fisher::updateOrCreate(['national_id' => $fisher['national_id']], $fisher + ['status' => $fisher['status'] ?? 'نشط']);
        }

        $officers = [
            ['name' => 'عبدالله القحطاني', 'employee_number' => 'EMP-2201', 'email' => 'a.qahtani@hawat.gov.sa', 'phone' => '0551002233', 'port' => 'ميناء الجبيل', 'region' => 'الشرقية', 'governorate' => 'الجبيل', 'shift' => 'صباحية', 'trips_counted' => 640],
            ['name' => 'ريم العتيبي', 'employee_number' => 'EMP-2244', 'email' => 'r.otaibi@hawat.gov.sa', 'phone' => '0552114488', 'port' => 'ميناء القطيف', 'region' => 'الشرقية', 'governorate' => 'القطيف', 'shift' => 'مسائية', 'trips_counted' => 580],
            ['name' => 'يحيى الفيفي', 'employee_number' => 'EMP-2290', 'email' => 'y.faifi@hawat.gov.sa', 'phone' => '0553998712', 'port' => 'ميناء جيزان', 'region' => 'جازان', 'governorate' => 'جيزان', 'shift' => 'صباحية', 'trips_counted' => 720],
            ['name' => 'مازن الغامدي', 'employee_number' => 'EMP-2318', 'email' => 'm.ghamdi@hawat.gov.sa', 'phone' => '0554120099', 'port' => 'ميناء جدة الإسلامي', 'region' => 'مكة المكرمة', 'governorate' => 'جدة', 'shift' => 'ليلية', 'trips_counted' => 410],
        ];

        foreach ($officers as $officer) {
            StatisticsOfficer::updateOrCreate(['employee_number' => $officer['employee_number']], $officer + ['status' => 'نشط']);
        }
    }
}