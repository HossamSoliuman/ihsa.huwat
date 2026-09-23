<?php

namespace Database\Seeders;

use App\Models\Boat;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Fisher;
use App\Models\FisherRole;
use App\Models\Role;
use App\Models\StatisticsOfficer;
use App\Models\Trip;
use App\Models\User;
use App\Services\Trips\TripService;
use Illuminate\Database\Seeder;

/**
 * يجهّز المالك التجريبي (0500000001) ليُختبر مسار الرحلة كاملًا من أول دخول:
 * قاربان من الأسطول المبذور يُنسبان إليه مع رحلاتهما، وكابتن يدخل التطبيق
 * بجواله 0500000002، وعدّاد على ميناء القارب الأول بجواله 0500000003،
 * وزبونان. لا يُنشئ شيئًا جديدًا في الأسطول.
 */
class DemoOwnerSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('phone', '0500000001')->first();

        if ($owner === null) {
            return;
        }

        $password = env('SEED_PASSWORD', 'hawat@2026');

        $captain = User::firstOrCreate(
            ['phone' => '0500000002'],
            [
                'name' => 'كابتن تجريبي',
                'password' => $password,
                'role_id' => Role::key(Role::CAPTAIN)->id,
                'owner_id' => $owner->id,
            ],
        );

        $boats = Boat::whereNull('owner_id')->orderBy('id')->limit(2)->get();

        foreach ($boats as $i => $boat) {
            $boat->update(['owner_id' => $owner->id] + ($i === 0 ? ['captain_id' => $captain->id] : []));
            Trip::where('boat_id', $boat->id)->whereNull('owner_id')->update(['owner_id' => $owner->id]);

            // رحلات القارب الأول تُسند للكابتن التجريبي حتى تمتلئ بوابته من أول دخول.
            if ($i === 0) {
                Trip::where('boat_id', $boat->id)->whereNull('captain_id')->update(['captain_id' => $captain->id, 'captain_name' => $captain->name]);
            }
        }

        // قارب المالك الأول — يُقرأ من أسطوله لا من الأسطول الحرّ حتى يعمل
        // البذّار في التشغيل الثاني أيضًا (البذر الأول أسند القوارب إليه).
        $homeBoat = Boat::forOwner($owner)->orderBy('id')->first();

        // رحلة بانتظار الكابتن دائمًا — ليُجرَّب البدء والإلغاء وإرسال المخرجات.
        if ($homeBoat !== null && ! Trip::forCaptain($captain)->awaitingCaptain()->exists()) {
            app(TripService::class)->create($owner, ['boat_id' => $homeBoat->id, 'captain_id' => $captain->id, 'planned_days' => 3]);
        }

        // الرحلات المبذورة التي عُدّت قبل أن يكون لها مالك: يُفتح مصيدها للبيع
        // الآن حتى تظهر في "الرحلات التي تحتاج تسجيل البيع" من أول دخول.
        Trip::forOwner($owner)
            ->whereIn('status', [Trip::AWAITING_APPROVAL, Trip::APPROVED])
            ->where('sale_status', Trip::SALE_NOT_STARTED)
            ->whereHas('catchRecords')
            ->get()
            ->each(fn (Trip $trip) => app(TripService::class)->openForSale($trip));

        if ($homeBoat !== null) {
            // العدّاد موظف الإحصاء في ميناء قارب المالك: يصله طابور ما يعود إليه.
            $counter = User::firstOrCreate(
                ['phone' => '0500000003'],
                [
                    'name' => 'عدّاد تجريبي',
                    'password' => $password,
                    'role_id' => Role::key(Role::COUNTER)->id,
                ],
            );

            StatisticsOfficer::updateOrCreate(['user_id' => $counter->id], [
                'port_id' => $homeBoat->port_id,
                'name' => $counter->name,
                'phone' => $counter->phone,
                'employee_number' => StatisticsOfficer::numberFor($counter),
                'shift' => 'صباحية',
                'status' => 'نشط',
            ]);

            Fisher::updateOrCreate(['user_id' => $captain->id], [
                'owner_id' => $owner->id,
                'port_id' => $homeBoat->port_id,
                'boat_id' => $homeBoat->id,
                'name' => $captain->name,
                'phone' => $captain->phone,
                'national_id' => '1000000002',
                'fisher_role_id' => FisherRole::named(Fisher::CAPTAIN_ROLE)->id,
                'role' => Fisher::CAPTAIN_ROLE,
            ]);
        }

        foreach (['سوق السمك المركزي' => '0555000001', 'مطاعم الخليج' => '0555000002'] as $name => $phone) {
            Customer::firstOrCreate(
                ['account_user_id' => $owner->id, 'name' => $name],
                ['phone' => $phone, 'customer_type_id' => CustomerType::named('منتظم')->id],
            );
        }
    }
}
