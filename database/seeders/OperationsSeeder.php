<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Boat;
use App\Models\BycatchRecord;
use App\Models\CatchRecord;
use App\Models\Species;
use App\Models\Trip;
use App\Models\Violation;
use Illuminate\Database\Seeder;

class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        $boats = Boat::with('port')->get()->keyBy('name');
        $species = Species::all()->keyBy('name_ar');

        $statuses = ['معتمدة', 'معتمدة', 'معتمدة', 'معتمدة', 'في البحر', 'في البحر', 'عادت للميناء', 'بانتظار الإحصاء', 'تحت الإحصاء', 'بانتظار الاعتماد', 'معتمدة', 'معتمدة'];
        $boatNames = ['نجم الخليج', 'لؤلؤة الدمام', 'فجر البحر', 'ريح الجنوب', 'درة فرسان', 'نجم الخليج', 'لؤلؤة الدمام', 'فجر البحر', 'درة فرسان', 'ريح الجنوب', 'نجم الخليج', 'درة فرسان'];

        foreach ($statuses as $i => $status) {
            $boat = $boats[$boatNames[$i]];
            $departure = now()->subDays($i * 3 + 1)->setTime(4, 30);
            $captainKg = 800 + $i * 120;
            $actualKg = in_array($status, ['معتمدة', 'بانتظار الاعتماد']) ? $captainKg - ($i % 3) * 35 : null;

            Trip::updateOrCreate(
                ['trip_number' => sprintf('TR-2026-%04d', $i + 1)],
                [
                    'boat_id' => $boat->id,
                    'departure_port_id' => $boat->port_id,
                    'captain_name' => $boat->captain,
                    'crew_count' => $boat->crew_count,
                    'departure_time' => $departure,
                    'return_time' => in_array($status, ['مجدولة', 'في البحر']) ? null : $departure->copy()->addHours(10),
                    'duration_hours' => in_array($status, ['مجدولة', 'في البحر']) ? null : 10,
                    'gear_type' => ['سنارة', 'شبك خيشوم', 'خيط طويل'][$i % 3],
                    'captain_input_kg' => $captainKg,
                    'actual_weight_kg' => $actualKg,
                    'diff_kg' => $actualKg !== null ? $captainKg - $actualKg : null,
                    'approved_kg' => $status === 'معتمدة' ? $actualKg : null,
                    'status' => $status,
                    'statistics_officer' => $status === 'معتمدة' ? 'محمد العوامي' : null,
                ]
            );
        }

        $trips = Trip::where('status', 'معتمدة')->get();
        $speciesNames = ['الهامور', 'الشعري', 'الكنعد', 'الناجل', 'الحريد', 'الروبيان'];

        foreach (range(0, 11) as $m) {
            $month = now()->subMonths($m);
            foreach (array_slice($speciesNames, 0, 3 + $m % 3) as $j => $name) {
                $trip = $trips[($m + $j) % max($trips->count(), 1)];
                CatchRecord::updateOrCreate(
                    ['trip_id' => $trip->id, 'species_id' => $species[$name]->id, 'recorded_at' => $month->copy()->startOfMonth()->addDays(9)->toDateString()],
                    [
                        'quantity_kg' => 2200 + ($m * 137 + $j * 411) % 2800,
                        'avg_weight_kg' => $species[$name]->avg_weight_kg,
                        'price_per_kg' => 22 + ($j * 7) % 30,
                        'total_value' => null,
                    ]
                );
            }
        }

        $bycatch = [
            ['trip_number' => 'TR-2026-0001', 'species_name' => 'سلحفاة بحرية', 'quantity_kg' => 12, 'action_taken' => 'إعادة للبحر حية', 'status' => 'مراجع'],
            ['trip_number' => 'TR-2026-0002', 'species_name' => 'قرش صغير', 'quantity_kg' => 28, 'action_taken' => 'إعادة للبحر', 'status' => 'مسجل'],
            ['trip_number' => 'TR-2026-0003', 'species_name' => 'شفنين بحري', 'quantity_kg' => 15, 'action_taken' => 'تسليم للأبحاث', 'status' => 'مسجل'],
        ];

        foreach ($bycatch as $item) {
            $trip = Trip::where('trip_number', $item['trip_number'])->first();
            BycatchRecord::updateOrCreate(
                ['trip_id' => $trip->id, 'species_name' => $item['species_name']],
                collect($item)->except('trip_number')->all()
            );
        }

        $alerts = [
            ['title' => 'انخفاض مصيد الهامور في القطيف', 'type' => 'انخفاض المصيد', 'severity' => 'مرتفع', 'region' => 'المنطقة الشرقية', 'port' => 'ميناء القطيف', 'species' => 'الهامور', 'description' => 'انخفاض 22% في متوسط المصيد اليومي مقارنة بالشهر السابق', 'date' => now()->subDays(2)->toDateString()],
            ['title' => 'ضغط صيد مرتفع على الكنعد', 'type' => 'ضغط صيد مرتفع', 'severity' => 'حرج', 'region' => 'المنطقة الشرقية', 'species' => 'الكنعد', 'description' => 'تجاوز معدل الصيد الحد المستدام للأسبوع الثالث على التوالي', 'date' => now()->subDays(4)->toDateString()],
            ['title' => 'فرق مرتفع في رحلة TR-2026-0011', 'type' => 'فرق مرتفع', 'severity' => 'متوسط', 'port' => 'ميناء جيزان', 'boat' => 'درة فرسان', 'description' => 'فرق 8% بين إدخال الكابتن والوزن الفعلي', 'date' => now()->subDays(1)->toDateString()],
            ['title' => 'اقتراب إغلاق موسم الحريد', 'type' => 'إغلاق موسم صيد', 'severity' => 'منخفض', 'region' => 'جازان', 'species' => 'الحريد', 'description' => 'يغلق الموسم بعد 46 يومًا وتم استهلاك 78% من الحصة', 'date' => now()->toDateString()],
            ['title' => 'رخصة منتهية لقارب شراع تبوك', 'type' => 'رخصة منتهية', 'severity' => 'مرتفع', 'port' => 'ميناء ضباء', 'boat' => 'شراع تبوك', 'description' => 'رخصة الصيد منتهية منذ 12 يومًا مع محاولة تسجيل رحلة', 'date' => now()->subDays(3)->toDateString()],
        ];

        foreach ($alerts as $item) {
            Alert::updateOrCreate(['title' => $item['title']], $item);
        }

        $violations = [
            ['boat_name' => 'درة فرسان', 'violation_type' => 'صيد في منطقة محظورة', 'severity' => 'مرتفع', 'location' => 'محمية فرسان', 'description' => 'رصد القارب داخل حدود المحمية أثناء موسم التكاثر', 'fine_amount' => 15000, 'action' => 'غرامة وإنذار', 'date' => now()->subDays(20)->toDateString(), 'status' => 'مغلقة'],
            ['boat_name' => 'شراع تبوك', 'violation_type' => 'رخصة منتهية', 'severity' => 'متوسط', 'location' => 'ميناء ضباء', 'description' => 'محاولة انطلاق برخصة منتهية الصلاحية', 'fine_amount' => 5000, 'action' => 'إيقاف مؤقت', 'date' => now()->subDays(3)->toDateString(), 'status' => 'قيد المعالجة'],
            ['boat_name' => null, 'violation_type' => 'أدوات صيد مخالفة', 'severity' => 'متوسط', 'location' => 'شعاب أبحر', 'description' => 'ضبط شباك بفتحات أصغر من الحد النظامي', 'fine_amount' => 8000, 'action' => 'مصادرة الأدوات', 'date' => now()->subDays(11)->toDateString(), 'status' => 'مسجلة'],
            ['boat_name' => 'لؤلؤة الدمام', 'violation_type' => 'مخالفة موسمية', 'severity' => 'منخفض', 'location' => 'ميناء الدمام', 'description' => 'تفريغ كمية تتجاوز حصة الرخصة اليومية', 'fine_amount' => 3000, 'action' => 'إنذار', 'date' => now()->subDays(7)->toDateString(), 'status' => 'مسجلة'],
        ];

        foreach ($violations as $item) {
            $boat = $item['boat_name'] ? Boat::where('name', $item['boat_name'])->first() : null;
            Violation::updateOrCreate(
                ['violation_type' => $item['violation_type'], 'date' => $item['date']],
                ['boat_id' => $boat?->id] + collect($item)->except('boat_name')->all()
            );
        }

        // حالة القارب مشتقّة من رحلاته: القارب صاحب رحلة جارية يظهر «في البحر»
        // على الخريطة البحرية وفي إحصاءات صفحة القوارب.
        $atSeaBoatIds = Trip::where('status', 'في البحر')->pluck('boat_id')->unique();
        Boat::whereIn('id', $atSeaBoatIds)->update(['status' => 'في البحر']);
    }
}