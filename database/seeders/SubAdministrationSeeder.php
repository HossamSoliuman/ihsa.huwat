<?php

namespace Database\Seeders;

use App\Models\AdminTask;
use App\Models\NotificationSetting;
use App\Models\OrgPosition;
use App\Models\OrgStaff;
use App\Models\StaffNotification;
use Illuminate\Database\Seeder;

class SubAdministrationSeeder extends Seeder
{
    public function run(): void
    {
        $positions = $this->seedPositions();
        $staff = $this->seedStaff($positions);

        $this->seedTasks($positions, $staff);
        $this->seedNotifications($staff);
        $this->seedNotificationChannels();
    }

    /**
     * الهيكل من الوكالة إلى الأقسام — الأب يُشار إليه بمسمّاه هنا ويُحفظ بمعرّفه.
     *
     * @return array<string, OrgPosition>
     */
    private function seedPositions(): array
    {
        $rows = [
            ['title' => 'وكالة الوزارة للثروة السمكية', 'title_en' => 'Deputy Ministry for Fisheries', 'level' => 'وكيل وزارة', 'parent' => null, 'linked_role' => 'top_management', 'scope_level' => 'kingdom', 'authorities' => 'اعتماد السياسات، إقرار الحصص، اعتماد المؤشرات الوطنية', 'responsibilities' => 'الإشراف العام على قطاع المصايد البحرية وسياساته', 'display_order' => 1],
            ['title' => 'الإدارة العامة للمصايد البحرية', 'title_en' => 'General Directorate of Marine Fisheries', 'level' => 'مدير عام', 'parent' => 'وكالة الوزارة للثروة السمكية', 'linked_role' => 'fisheries_admin', 'scope_level' => 'kingdom', 'authorities' => 'اعتماد الرخص، إسناد المهام، اعتماد التقارير', 'responsibilities' => 'تشغيل القطاع وربط الإدارات الفرعية ببعضها', 'reports_to' => 'وكيل الوزارة للثروة السمكية', 'display_order' => 2],
            ['title' => 'إدارة الإحصاء والمعلومات', 'title_en' => 'Statistics & Information Directorate', 'level' => 'مدير إدارة', 'parent' => 'الإدارة العامة للمصايد البحرية', 'linked_role' => 'supervision', 'scope_level' => 'kingdom', 'authorities' => 'اعتماد المصيد، مراجعة الفروقات، إصدار النشرات', 'responsibilities' => 'رصد المصيد واعتماده وإصدار المؤشرات والتقارير', 'reports_to' => 'مدير عام المصايد البحرية', 'display_order' => 3],
            ['title' => 'إدارة التراخيص والخدمات', 'title_en' => 'Licensing & Services Directorate', 'level' => 'مدير إدارة', 'parent' => 'الإدارة العامة للمصايد البحرية', 'linked_role' => 'fisheries_admin', 'scope_level' => 'kingdom', 'authorities' => 'إصدار الرخص، معالجة الطلبات، رفض الطلبات', 'responsibilities' => 'رخص القوارب والصيادين وخدمات المستفيدين', 'reports_to' => 'مدير عام المصايد البحرية', 'display_order' => 4],
            ['title' => 'إدارة الرقابة والامتثال', 'title_en' => 'Compliance & Control Directorate', 'level' => 'مدير إدارة', 'parent' => 'الإدارة العامة للمصايد البحرية', 'linked_role' => 'supervision', 'scope_level' => 'kingdom', 'authorities' => 'تسجيل المخالفات، إسناد الإنذارات، إيقاف الرخص', 'responsibilities' => 'رصد المخالفات ومتابعة الإنذارات حتى إغلاقها', 'reports_to' => 'مدير عام المصايد البحرية', 'display_order' => 5],
            ['title' => 'قسم إحصاء المنطقة الشرقية', 'title_en' => 'Eastern Region Statistics Section', 'level' => 'مدير قسم', 'parent' => 'إدارة الإحصاء والمعلومات', 'linked_role' => 'region_manager', 'scope_level' => 'region', 'authorities' => 'اعتماد المصيد، مراجعة الفروقات', 'responsibilities' => 'إحصاء موانئ المنطقة الشرقية ومتابعة موظفيها', 'reports_to' => 'مدير إدارة الإحصاء والمعلومات', 'display_order' => 6],
            ['title' => 'قسم إحصاء البحر الأحمر', 'title_en' => 'Red Sea Statistics Section', 'level' => 'مدير قسم', 'parent' => 'إدارة الإحصاء والمعلومات', 'linked_role' => 'region_manager', 'scope_level' => 'region', 'authorities' => 'اعتماد المصيد، مراجعة الفروقات', 'responsibilities' => 'إحصاء موانئ البحر الأحمر ومتابعة موظفيها', 'reports_to' => 'مدير إدارة الإحصاء والمعلومات', 'display_order' => 7],
            ['title' => 'وحدة ميناء القطيف', 'title_en' => 'Qatif Port Unit', 'level' => 'مسؤول', 'parent' => 'قسم إحصاء المنطقة الشرقية', 'linked_role' => 'port_manager', 'scope_level' => 'port', 'authorities' => 'إنشاء سجلات المصيد، معالجة الطلبات', 'responsibilities' => 'استقبال الرحلات ووزن المصيد وإدخال البيانات', 'reports_to' => 'مدير قسم إحصاء المنطقة الشرقية', 'display_order' => 8],
        ];

        $positions = [];

        foreach ($rows as $row) {
            $parent = $row['parent'];
            unset($row['parent']);

            $row['parent_id'] = $parent === null ? null : $positions[$parent]->id;

            $positions[$row['title']] = OrgPosition::updateOrCreate(['title' => $row['title']], $row);
        }

        return $positions;
    }

    /**
     * @param  array<string, OrgPosition>  $positions
     * @return array<string, OrgStaff>
     */
    private function seedStaff(array $positions): array
    {
        $rows = [
            ['name' => 'سعود بن ناصر الدوسري', 'position' => 'الإدارة العامة للمصايد البحرية', 'job_number' => 'MF-1001', 'email' => 'dg@mewa.gov.sa', 'rank' => 'الرتبة الأولى', 'can_create' => true, 'can_process' => true, 'can_approve' => true, 'can_reject' => true, 'can_assign' => true],
            ['name' => 'هند بنت عبدالله القحطاني', 'position' => 'إدارة الإحصاء والمعلومات', 'job_number' => 'MF-1042', 'email' => 'stats@mewa.gov.sa', 'rank' => 'الرتبة الثانية', 'can_create' => true, 'can_process' => true, 'can_approve' => true, 'can_assign' => true],
            ['name' => 'ماجد بن سالم الغامدي', 'position' => 'إدارة التراخيص والخدمات', 'job_number' => 'MF-1077', 'email' => 'licensing@mewa.gov.sa', 'rank' => 'الرتبة الثانية', 'can_create' => true, 'can_process' => true, 'can_approve' => true, 'can_reject' => true],
            ['name' => 'ريم بنت فهد العتيبي', 'position' => 'إدارة الرقابة والامتثال', 'job_number' => 'MF-1093', 'email' => 'compliance@mewa.gov.sa', 'rank' => 'الرتبة الثالثة', 'can_create' => true, 'can_process' => true, 'can_assign' => true],
            ['name' => 'علي بن حسن الشمري', 'position' => 'قسم إحصاء المنطقة الشرقية', 'job_number' => 'MF-2011', 'email' => 'east@mewa.gov.sa', 'rank' => 'الرتبة الثالثة', 'can_create' => true, 'can_process' => true, 'can_approve' => true],
            ['name' => 'خالد بن مرزوق الحربي', 'position' => 'قسم إحصاء البحر الأحمر', 'job_number' => 'MF-2019', 'email' => 'redsea@mewa.gov.sa', 'rank' => 'الرتبة الثالثة', 'status' => 'إجازة', 'can_create' => true, 'can_process' => true],
            ['name' => 'نورة بنت سعد المطيري', 'position' => 'وحدة ميناء القطيف', 'job_number' => 'MF-3055', 'email' => 'qatif@mewa.gov.sa', 'rank' => 'الرتبة الرابعة', 'can_create' => true, 'can_process' => true],
        ];

        $staff = [];

        foreach ($rows as $row) {
            $position = $positions[$row['position']];
            unset($row['position']);

            $row['org_position_id'] = $position->id;
            $row['start_date'] = now()->subYears(3)->toDateString();

            $staff[$row['name']] = OrgStaff::updateOrCreate(['job_number' => $row['job_number']], $row);
        }

        return $staff;
    }

    /**
     * @param  array<string, OrgPosition>  $positions
     * @param  array<string, OrgStaff>  $staff
     */
    private function seedTasks(array $positions, array $staff): void
    {
        $rows = [
            ['title' => 'اعتماد المصيد الشهري لموانئ المنطقة الشرقية', 'position' => 'قسم إحصاء المنطقة الشرقية', 'assignee' => 'علي بن حسن الشمري', 'required_permission' => 'اعتماد', 'task_type' => 'اعتماد', 'priority' => 'مهمة', 'status' => 'قيد التنفيذ', 'due' => 3, 'recurrence' => 'شهري'],
            ['title' => 'مراجعة الفروقات بين إدخال الكابتن والوزن الفعلي', 'position' => 'إدارة الإحصاء والمعلومات', 'assignee' => 'هند بنت عبدالله القحطاني', 'required_permission' => 'معالجة', 'task_type' => 'مراجعة', 'priority' => 'عاجلة', 'status' => 'مجدولة', 'due' => 1, 'recurrence' => 'أسبوعي'],
            ['title' => 'إصدار رخص موسم الروبيان', 'position' => 'إدارة التراخيص والخدمات', 'assignee' => 'ماجد بن سالم الغامدي', 'required_permission' => 'إنشاء', 'task_type' => 'إصدار رخصة', 'priority' => 'مهمة', 'status' => 'مجدولة', 'due' => 7, 'recurrence' => 'سنوي'],
            ['title' => 'إغلاق الإنذارات المفتوحة منذ أكثر من أسبوعين', 'position' => 'إدارة الرقابة والامتثال', 'assignee' => 'ريم بنت فهد العتيبي', 'required_permission' => 'إسناد', 'task_type' => 'متابعة', 'priority' => 'عادية', 'status' => 'مجدولة', 'due' => 5, 'recurrence' => 'أسبوعي'],
            ['title' => 'تقرير الإنتاج الشهري للإدارة العليا', 'position' => 'إدارة الإحصاء والمعلومات', 'assignee' => 'هند بنت عبدالله القحطاني', 'required_permission' => 'اعتماد', 'task_type' => 'تقرير', 'priority' => 'مهمة', 'status' => 'مكتملة', 'due' => -4, 'recurrence' => 'شهري'],
            ['title' => 'اجتماع تنسيقي مع مديري الموانئ', 'position' => 'الإدارة العامة للمصايد البحرية', 'assignee' => 'سعود بن ناصر الدوسري', 'required_permission' => 'أي صلاحية', 'task_type' => 'اجتماع', 'priority' => 'عادية', 'status' => 'مجدولة', 'due' => 10, 'recurrence' => 'شهري'],
            ['title' => 'متابعة إدخال بيانات رحلات ميناء القطيف', 'position' => 'وحدة ميناء القطيف', 'assignee' => 'نورة بنت سعد المطيري', 'required_permission' => 'إنشاء', 'task_type' => 'متابعة', 'priority' => 'عادية', 'status' => 'متأخرة', 'due' => -6, 'recurrence' => 'يومي'],
        ];

        $sections = [
            'إصدار رخصة' => 'الخدمات والتراخيص',
            'تقرير' => 'الإحصاء',
            'مراجعة' => 'الإحصاء',
        ];

        foreach ($rows as $row) {
            $dueDate = now()->addDays($row['due'])->toDateString();

            AdminTask::updateOrCreate(['title' => $row['title']], [
                'org_position_id' => $positions[$row['position']]->id,
                'assigned_staff_id' => $staff[$row['assignee']]->id,
                'required_permission' => $row['required_permission'],
                'task_type' => $row['task_type'],
                'section' => $sections[$row['task_type']] ?? 'الإدارة الفرعية',
                'priority' => $row['priority'],
                'status' => $row['status'],
                'start_date' => now()->addDays(min(0, $row['due']))->toDateString(),
                'due_date' => $dueDate,
                'completed_at' => $row['status'] === 'مكتملة' ? now()->subDays(4) : null,
                'completed_by' => $row['status'] === 'مكتملة' ? $row['assignee'] : null,
                'recurrence' => $row['recurrence'],
            ]);
        }
    }

    /**
     * @param  array<string, OrgStaff>  $staff
     */
    private function seedNotifications(array $staff): void
    {
        $rows = [
            ['recipient' => 'ماجد بن سالم الغامدي', 'title' => 'طلب رخصة قارب جديد', 'body' => "وصل طلب رخصة للقارب «نجم الخليج» من ميناء القطيف.\nالطلب بانتظار المعالجة خلال ٣ أيام عمل.", 'request_number' => 'REQ-2026-0187', 'notification_type' => 'طلب جديد', 'priority' => 'عادية', 'read' => false],
            ['recipient' => 'سعود بن ناصر الدوسري', 'title' => 'طلب بانتظار الاعتماد الإداري', 'body' => "طلب تجديد رخصة موسمية يتجاوز الحصة المخصصة، ويحتاج اعتمادك قبل الإصدار.", 'request_number' => 'REQ-2026-0191', 'notification_type' => 'بانتظار الاعتماد', 'priority' => 'عاجلة', 'read' => false],
            ['recipient' => 'هند بنت عبدالله القحطاني', 'title' => 'تذكير: إغلاق المصيد الشهري', 'body' => 'يُغلق اعتماد المصيد الشهري بعد ثلاثة أيام — راجع الرحلات المعلّقة قبل الإغلاق.', 'notification_type' => 'تذكير', 'priority' => 'عادية', 'read' => false],
            ['recipient' => 'ريم بنت فهد العتيبي', 'title' => 'إنذار بلا مسؤول', 'body' => 'ثلاثة إنذارات حرجة بلا مسؤول متابع منذ أكثر من ٤٨ ساعة.', 'notification_type' => 'تذكير', 'priority' => 'عاجلة', 'read' => false],
            ['recipient' => 'علي بن حسن الشمري', 'title' => 'اعتماد مصيد ميناء الدمام', 'body' => 'تم اعتماد ٧٦٥ كجم من مصيد ميناء الدمام بعد مطابقة الوزن الفعلي.', 'request_number' => 'TR-2026-0001', 'notification_type' => 'أخرى', 'priority' => 'عادية', 'read' => true],
        ];

        foreach ($rows as $row) {
            $member = $staff[$row['recipient']];
            unset($row['recipient']);

            StaffNotification::updateOrCreate(['title' => $row['title']], $row + [
                'org_staff_id' => $member->id,
                'recipient_email' => $member->email,
                'recipient_name' => $member->name,
                'read_at' => $row['read'] ? now()->subDay() : null,
            ]);
        }
    }

    private function seedNotificationChannels(): void
    {
        $channels = [
            ['channel' => 'email', 'label' => 'إشعارات البريد الإلكتروني', 'description' => 'إرسال نسخة من كل تنبيه إلى بريد الموظف', 'enabled' => true, 'display_order' => 1],
            ['channel' => 'in_app', 'label' => 'إشعارات داخل النظام', 'description' => 'عرض التنبيهات في صفحة التنبيهات الإدارية', 'enabled' => true, 'display_order' => 2],
            ['channel' => 'low_catch', 'label' => 'تنبيهات انخفاض المصيد', 'description' => 'عند هبوط المصيد عن متوسط المنطقة', 'enabled' => true, 'display_order' => 3],
            ['channel' => 'violations', 'label' => 'تنبيهات المخالفات', 'description' => 'عند تسجيل مخالفة جديدة على قارب أو صياد', 'enabled' => true, 'display_order' => 4],
            ['channel' => 'licenses', 'label' => 'تنبيهات انتهاء الرخص', 'description' => 'قبل انتهاء رخصة القارب بثلاثين يومًا', 'enabled' => true, 'display_order' => 5],
        ];

        foreach ($channels as $channel) {
            NotificationSetting::updateOrCreate(['channel' => $channel['channel']], $channel);
        }
    }
}
