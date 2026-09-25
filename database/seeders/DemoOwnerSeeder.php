<?php

namespace Database\Seeders;

use App\Models\Boat;
use App\Models\Consignment;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\DalalPartnership;
use App\Models\DalalProfile;
use App\Models\Fisher;
use App\Models\FisherRole;
use App\Models\Role;
use App\Models\StatisticsOfficer;
use App\Models\Trip;
use App\Models\User;
use App\Services\Sales\SaleService;
use App\Services\Stock\StockLedger;
use App\Services\Trips\TripService;
use Illuminate\Database\Seeder;

/**
 * يجهّز المالك التجريبي (0500000001) ليُختبر مسار الرحلة كاملًا من أول دخول:
 * قاربان من الأسطول المبذور يُنسبان إليه مع رحلاتهما، وكابتن يدخل التطبيق
 * بجواله 0500000002، وعدّاد على ميناء القارب الأول بجواله 0500000003،
 * وزبونان، ودلال بجواله 0500000004 باتفاق مقبول ومخزون مرسَل إليه. لا
 * يُنشئ شيئًا جديدًا في الأسطول.
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

        $this->seedDalal($owner, $homeBoat, $password);
    }

    /**
     * الدلال التجريبي (0500000004): ملفه ودكته، واتفاق مقبول مع المالك (5% عمولة
     * و2% أجور)، وعميلان، ونصف أول صنف في أول رحلة مفتوحة للبيع مرسَلًا إليه —
     * فيجد مخزونًا يبيع منه من أول دخول. الإرسال مرة واحدة فقط.
     */
    private function seedDalal(User $owner, ?Boat $homeBoat, string $password): void
    {
        $dalal = User::firstOrCreate(
            ['phone' => '0500000004'],
            ['name' => 'دلال تجريبي', 'password' => $password, 'role_id' => Role::key(Role::DALAL)->id],
        );

        DalalProfile::updateOrCreate(['user_id' => $dalal->id], [
            'port_id' => $homeBoat?->port_id,
            'dakka_name' => 'دكة السوق المركزي',
            'dakka_number' => 'D-104',
            'company_name' => 'مؤسسة الدلال التجريبي للأسماك',
            'cr_number' => '1010000004',
            'vat_number' => '300000000000004',
        ]);

        DalalPartnership::updateOrCreate(['owner_id' => $owner->id, 'dalal_id' => $dalal->id], [
            'commission_pct' => 5,
            'wage_pct' => 2,
            'message' => 'نرسل لك مصيد قواربنا للبيع في الدكة.',
            'status' => DalalPartnership::ACCEPTED,
            'responded_at' => now(),
        ]);

        foreach (['مطعم البحر الأحمر' => '0555000011', 'أسماك الواحة' => '0555000012'] as $name => $phone) {
            Customer::firstOrCreate(
                ['account_user_id' => $dalal->id, 'name' => $name],
                ['phone' => $phone, 'customer_type_id' => CustomerType::named('منتظم')->id],
            );
        }

        if (Consignment::forDalal($dalal)->exists()) {
            return;
        }

        $ledger = app(StockLedger::class);
        $trip = Trip::forOwner($owner)->where('sale_status', Trip::SALE_OPEN)->orderBy('id')->first();
        $line = $trip ? collect($ledger->availableLines($owner, $trip))->firstWhere(fn ($l) => $l['available_kg'] > 1) : null;

        if ($line === null) {
            return;
        }

        $sent = round($line['available_kg'] / 2, 2);

        app(SaleService::class)->consign($owner, [
            'trip_id' => $trip->id,
            'dalal_id' => $dalal->id,
            'items' => [['species_id' => $line['species_id'], 'weight_kg' => $sent]],
        ]);

        // بيعة أولى بدفعة جزئية — لتمتلئ رئيسة الدلال (المبيعات والأرباح وغير
        // المحصّل ومستحق الملاك) من أول دخول.
        app(SaleService::class)->sellFromStock($dalal, [
            'customer_id' => Customer::forAccount($dalal)->orderBy('id')->value('id'),
            'paid_amount' => 500,
            'items' => [['species_id' => $line['species_id'], 'weight_kg' => min(42, round($sent / 2, 2)), 'price_per_kg' => 36]],
        ]);
    }
}
