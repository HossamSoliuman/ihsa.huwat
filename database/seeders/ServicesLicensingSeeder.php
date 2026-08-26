<?php

namespace Database\Seeders;

use App\Models\Fisher;
use App\Models\FisherServiceRequest;
use App\Models\FisherServiceStaff;
use App\Models\FisherServiceType;
use App\Models\FishingSeason;
use App\Models\Port;
use App\Models\Region;
use App\Models\SupportTicket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ServicesLicensingSeeder extends Seeder
{
    public function run(): void
    {
        $types = $this->seedTypes();
        $staff = $this->seedStaff($types);

        $this->seedRequests($types, $staff);
        $this->seedTickets($staff);
    }

    /**
     * كتالوج الخدمات الإحدى عشرة. "تصحيح بيانات رحلة" وحدها تخصّ الإحصاء —
     * طبيعة العمل فيها مراجعة بيانات لا إصدار وثيقة.
     *
     * @return array<string, FisherServiceType>
     */
    private function seedTypes(): array
    {
        $rows = [
            ['name' => 'تجديد رخصة صيد حرفي', 'icon' => 'refresh-cw', 'issues_license' => true],
            ['name' => 'تجديد رخصة عامل صيد', 'icon' => 'refresh-cw', 'issues_license' => true],
            ['name' => 'تجديد رخصة قارب صيد', 'icon' => 'refresh-cw', 'issues_license' => true],
            ['name' => 'إصدار رخصة صيد فئة راجل', 'icon' => 'file-plus', 'issues_license' => true],
            ['name' => 'إصدار رخصة تصريح صيد موسمي', 'icon' => 'calendar', 'requires_season' => true, 'issues_license' => true],
            ['name' => 'استبدال رخصة مفقودة أو تالفة', 'icon' => 'file-text', 'issues_license' => true],
            ['name' => 'تغيير القارب', 'icon' => 'ship'],
            ['name' => 'تحديث البيانات الشخصية', 'icon' => 'user'],
            ['name' => 'نقل الميناء', 'icon' => 'anchor'],
            ['name' => 'إصدار شهادة ممارسة الصيد', 'icon' => 'badge-check', 'issues_license' => true],
            ['name' => 'تصحيح بيانات رحلة', 'icon' => 'clipboard-check', 'section' => 'الإحصاء'],
        ];

        $types = [];

        foreach ($rows as $order => $row) {
            $types[$row['name']] = FisherServiceType::updateOrCreate(['name' => $row['name']], $row + [
                'section' => 'الخدمات والتراخيص',
                'requires_season' => false,
                'issues_license' => false,
                'display_order' => $order + 1,
                'active' => true,
            ]);
        }

        return $types;
    }

    /**
     * @param  array<string, FisherServiceType>  $types
     * @return array<string, FisherServiceStaff>
     */
    private function seedStaff(array $types): array
    {
        $port = fn (string $name) => Port::where('name', $name)->value('id');
        $region = fn (string $name) => Region::where('name', $name)->value('id');

        $rows = [
            [
                'name' => 'ماجد بن سالم الغامدي', 'job_number' => 'SV-1001', 'email' => 'licensing@mewa.gov.sa',
                'role' => 'مشرف', 'can_approve' => true, 'can_reject' => true, 'can_assign' => true,
                'notes' => 'مشرف القسم — يوقّع على إصدار الرخص',
            ],
            [
                'name' => 'عبدالرحمن بن فيصل الزهراني', 'job_number' => 'SV-1014', 'email' => 'renewals@mewa.gov.sa',
                'role' => 'معالج', 'region' => 'المنطقة الشرقية',
                'services' => ['تجديد رخصة صيد حرفي', 'تجديد رخصة عامل صيد', 'تجديد رخصة قارب صيد'],
            ],
            [
                'name' => 'لمياء بنت خالد العنزي', 'job_number' => 'SV-1027', 'email' => 'seasonal@mewa.gov.sa',
                'role' => 'معالج', 'can_reject' => true,
                'services' => ['إصدار رخصة تصريح صيد موسمي', 'إصدار رخصة صيد فئة راجل'],
            ],
            [
                'name' => 'فهد بن عايض القرني', 'job_number' => 'SV-1039', 'email' => 'reception@mewa.gov.sa',
                'role' => 'مستقبل طلبات', 'can_process' => false,
            ],
            [
                'name' => 'منيرة بنت تركي الشهري', 'job_number' => 'SV-1046', 'email' => 'support@hawat.gov.sa',
                'role' => 'معالج', 'can_approve' => true, 'notes' => 'مسؤولة تذاكر الدعم الفني',
            ],
        ];

        $staff = [];

        foreach ($rows as $row) {
            $services = $row['services'] ?? [];
            $portName = $row['port'] ?? null;
            $regionName = $row['region'] ?? null;
            unset($row['services'], $row['port'], $row['region']);

            $member = FisherServiceStaff::updateOrCreate(['job_number' => $row['job_number']], $row + [
                'section' => 'الخدمات والتراخيص',
                'assigned_port_id' => $portName === null ? null : $port($portName),
                'assigned_region_id' => $regionName === null ? null : $region($regionName),
                'can_create' => true,
                'can_process' => true,
                'can_approve' => false,
                'can_reject' => false,
                'can_assign' => false,
                'active' => true,
            ]);

            $member->serviceTypes()->sync(array_map(fn (string $name) => $types[$name]->id, $services));

            $staff[$member->name] = $member;
        }

        return $staff;
    }

    /**
     * طلبات تغطي المسار كاملًا — جديدة، وقيد المعالجة، وبانتظار الاعتماد،
     * ومعتمدة برخصة صادرة، ومرفوضة.
     *
     * @param  array<string, FisherServiceType>  $types
     * @param  array<string, FisherServiceStaff>  $staff
     */
    private function seedRequests(array $types, array $staff): void
    {
        $fishers = Fisher::with('port')->orderBy('id')->take(5)->get();

        if ($fishers->isEmpty()) {
            return;
        }

        $season = FishingSeason::orderBy('id')->first();

        $rows = [
            [
                'number' => 'SR-0001', 'type' => 'تجديد رخصة صيد حرفي', 'status' => 'جديدة',
                'priority' => 'عادية', 'submitted' => 2,
                'description' => 'انتهت الرخصة الشهر الماضي ويحتاج تجديدها قبل بدء الموسم.',
            ],
            [
                'number' => 'SR-0002', 'type' => 'استبدال رخصة مفقودة أو تالفة', 'status' => 'قيد المعالجة',
                'priority' => 'عاجلة', 'submitted' => 6, 'assigned' => true,
                'description' => 'فُقدت الرخصة أثناء رحلة صيد، وطُلب بلاغ فقدان.',
            ],
            [
                'number' => 'SR-0003', 'type' => 'إصدار رخصة تصريح صيد موسمي', 'status' => 'بانتظار الاعتماد',
                'priority' => 'عادية', 'submitted' => 11, 'processed' => 3, 'assigned' => true,
                'season' => true, 'new_license' => 'LIC-2026-0431', 'expiry' => 300,
                'resolution' => 'استُوفيت المستندات وسُجّل الموسم — بانتظار توقيع المشرف.',
            ],
            [
                'number' => 'SR-0004', 'type' => 'إصدار شهادة ممارسة الصيد', 'status' => 'معتمدة',
                'priority' => 'عادية', 'submitted' => 24, 'processed' => 18, 'assigned' => true,
                'new_license' => 'LIC-2026-0388', 'expiry' => 700, 'approved_by' => 'ماجد بن سالم الغامدي', 'approved' => 17,
            ],
            [
                'number' => 'SR-0005', 'type' => 'نقل الميناء', 'status' => 'مرفوضة',
                'priority' => 'عادية', 'submitted' => 31, 'processed' => 26, 'assigned' => true,
                'resolution' => 'رفض الاعتماد بواسطة ماجد بن سالم الغامدي — الميناء المطلوب بلغ سعته القصوى.',
            ],
        ];

        $processors = collect($staff)->filter(fn (FisherServiceStaff $member) => $member->can_process)->values();

        foreach ($rows as $index => $row) {
            $fisher = $fishers[$index % $fishers->count()];

            $request = FisherServiceRequest::updateOrCreate(['request_number' => $row['number']], [
                'fisher_service_type_id' => $types[$row['type']]->id,
                'fisher_id' => $fisher->id,
                'fisher_name' => $fisher->name,
                'national_id' => $fisher->national_id,
                'phone' => $fisher->phone,
                'nationality_type' => 'سعودي',
                'nationality' => 'سعودي',
                'profession' => 'صياد سمك',
                'port_id' => $fisher->port_id,
                'fishing_season_id' => ($row['season'] ?? false) ? $season?->id : null,
                'license_number' => $fisher->license_number,
                'description' => $row['description'] ?? null,
                'status' => $row['status'],
                'priority' => $row['priority'],
                'submitted_date' => now()->subDays($row['submitted'])->toDateString(),
                'processed_date' => isset($row['processed']) ? now()->subDays($row['processed'])->toDateString() : null,
                'resolution' => $row['resolution'] ?? null,
                'approved_by' => $row['approved_by'] ?? null,
                'approved_at' => isset($row['approved']) ? now()->subDays($row['approved']) : null,
                'new_license_number' => $row['new_license'] ?? null,
                'new_license_expiry' => isset($row['expiry']) ? now()->addDays($row['expiry'])->toDateString() : null,
            ]);

            if ($row['assigned'] ?? false) {
                $request->update(['assigned_staff_id' => $this->processorFor($request, $processors)?->id]);
            }
        }
    }

    /**
     * المعالج المناسب للطلب — يُختار بالقاعدة نفسها التي يفرضها النموذج، فلا
     * تُولَّد بيانات إسناد ترفضها الشاشة عند أول تعديل.
     *
     * الأخصّ أولًا: من قيّد نفسه بخدمات ونطاق يسبق من تخويله مفتوح، فيبقى
     * المشرف احتياطًا لا خيارًا أول.
     *
     * @param  Collection<int, FisherServiceStaff>  $processors
     */
    private function processorFor(FisherServiceRequest $request, Collection $processors): ?FisherServiceStaff
    {
        return $processors
            ->filter(fn (FisherServiceStaff $member) => $member->handles($request->loadMissing('port.governorate')))
            ->sortByDesc(fn (FisherServiceStaff $member) => $member->serviceTypes->count()
                + ($member->assigned_port_id !== null ? 2 : 0)
                + ($member->assigned_region_id !== null ? 1 : 0))
            ->first();
    }

    /**
     * @param  array<string, FisherServiceStaff>  $staff
     */
    private function seedTickets(array $staff): void
    {
        $agent = $staff['منيرة بنت تركي الشهري'] ?? null;

        $rows = [
            [
                'number' => 'TK-0001', 'subject' => 'تعذّر حفظ سجل مصيد في الإحصاء الميداني',
                'category' => 'مشكلة تقنية', 'priority' => 'عاجلة', 'module' => 'الإحصاء الميداني',
                'description' => 'عند حفظ وزن المصيد لرحلة ميناء القطيف تظهر رسالة خطأ ولا يُحفظ السجل.',
                'by' => 'نورة بنت سعد المطيري', 'email' => 'qatif@mewa.gov.sa', 'status' => 'قيد المعالجة',
                'submitted' => 3, 'assign' => true,
            ],
            [
                'number' => 'TK-0002', 'subject' => 'طلب صلاحية اعتماد لموظف جديد',
                'category' => 'صلاحيات الوصول', 'priority' => 'عادية', 'module' => 'الهيكل التنظيمي والصلاحيات',
                'description' => 'انضم موظف إلى إدارة التراخيص ويحتاج صلاحية الاعتماد ضمن نطاق البحر الأحمر.',
                'by' => 'ماجد بن سالم الغامدي', 'email' => 'licensing@mewa.gov.sa', 'status' => 'جديدة',
                'submitted' => 1,
            ],
            [
                'number' => 'TK-0003', 'subject' => 'اقتراح: تصدير قائمة الطلبات إلى CSV',
                'category' => 'اقتراح تطوير', 'priority' => 'عادية', 'module' => 'خدمات الصيادين',
                'description' => 'يفيد تصدير الطلبات المفلترة لإرفاقها بالتقرير الشهري.',
                'by' => 'لمياء بنت خالد العنزي', 'email' => 'seasonal@mewa.gov.sa', 'status' => 'تم الحل',
                'submitted' => 14, 'assign' => true,
                'resolution' => 'أُضيف التصدير إلى خطة الإصدار القادم وأُبلغت مقدّمة الطلب.',
                'resolved' => 9,
            ],
        ];

        foreach ($rows as $row) {
            SupportTicket::updateOrCreate(['ticket_number' => $row['number']], [
                'subject' => $row['subject'],
                'category' => $row['category'],
                'priority' => $row['priority'],
                'module' => $row['module'],
                'description' => $row['description'],
                'submitted_by_name' => $row['by'],
                'submitted_by_email' => $row['email'],
                'submitted_at' => now()->subDays($row['submitted']),
                'status' => $row['status'],
                'assigned_staff_id' => ($row['assign'] ?? false) ? $agent?->id : null,
                'assigned_at' => ($row['assign'] ?? false) ? now()->subDays($row['submitted'])->addHours(4) : null,
                'resolution' => $row['resolution'] ?? null,
                'resolved_at' => isset($row['resolved']) ? now()->subDays($row['resolved']) : null,
            ]);
        }
    }
}
