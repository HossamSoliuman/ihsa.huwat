<?php

namespace Database\Seeders;

use App\Models\Boat;
use App\Models\CounterApplication;
use App\Models\HiringRound;
use App\Models\OperatingCompany;
use App\Models\Port;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * شركة تشغيل تجريبية تُختبر بها بوابة الشركة من أول دخول: تشغّل ميناء قارب
 * المالك التجريبي وميناءً ثانيًا، وحسابها 0500000006، وجولة توظيف مفتوحة
 * فيها طلبان بانتظار المراجعة.
 *
 * عدّاد الوزارة التجريبي (0500000003) يبقى عدّاد وزارة في الميناء نفسه —
 * النوعان يعملان معًا. يُعاد تشغيله بلا تكرار.
 */
class DemoCompanySeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('phone', '0500000001')->first();
        $homePortId = $owner ? Boat::forOwner($owner)->orderBy('id')->value('port_id') : null;
        $ports = Port::query()
            ->when($homePortId, fn ($q) => $q->orderByRaw('id = ? DESC', [$homePortId]))
            ->orderBy('id')
            ->limit(2)
            ->get();

        if ($ports->isEmpty()) {
            return;
        }

        $company = OperatingCompany::updateOrCreate(['commercial_register' => '1010000006'], [
            'name' => 'شركة الساحل للتشغيل (تجريبية)',
            'phone' => '0500000006',
            'email' => 'company@hawat.test',
            'contact_name' => 'مدير التشغيل',
            'status' => OperatingCompany::ACTIVE,
        ]);

        foreach ($ports as $port) {
            // الميناء لشركة واحدة: لا يُنتزع من شركة حقيقية أُسند إليها.
            $taken = DB::table('operating_company_ports')->where('port_id', $port->id)->exists();
            if (! $taken) {
                $company->ports()->attach($port->id, ['started_at' => today()]);
            }
        }

        User::firstOrCreate(['phone' => '0500000006'], [
            'name' => 'مدير شركة الساحل',
            'password' => 'password',
            'role_id' => Role::key(Role::COMPANY)->id,
            'operating_company_id' => $company->id,
        ]);

        $port = $company->ports()->orderBy('ports.id')->first();
        if ($port === null || $company->hiringRounds()->exists()) {
            return;
        }

        $round = HiringRound::create([
            'operating_company_id' => $company->id,
            'port_id' => $port->id,
            'title' => 'توظيف عدّادين — '.$port->name,
            'seats' => 4,
            'opens_at' => today()->subDays(3),
            'closes_at' => today()->addMonth(),
            'status' => HiringRound::OPEN,
        ]);

        foreach ([['متقدّم تجريبي أول', '0500000007', '1000000071', 3], ['متقدّم تجريبي ثانٍ', '0500000010', '1000000072', 0]] as [$name, $phone, $nationalId, $years]) {
            CounterApplication::create([
                'hiring_round_id' => $round->id,
                'operating_company_id' => $company->id,
                'port_id' => $port->id,
                'token' => Str::random(48),
                'name' => $name,
                'phone' => $phone,
                'national_id' => $nationalId,
                'qualification' => 'ثانوية عامة',
                'experience_years' => $years,
                'password' => 'password',
                'phone_verified_at' => now(),
                'status' => CounterApplication::PENDING,
            ]);
        }
    }
}
