<?php
require_once __DIR__ . '/../../config/config.php';

$currentUserData = requireLogin(['super_admin', 'port_supervisor', 'gov_supervisor']);
$pageTitle   = 'التنبيهات والرقابة';
$activeRoute = 'alerts.php';

$pdo = db();
$role = $currentUserData['role_code'];

/* ------------------------------------------------------------
   تحديد نطاق الموانئ حسب الدور
------------------------------------------------------------ */
if ($role === 'port_supervisor') {
    $portIds = [(int)$currentUserData['port_id']];
} elseif ($role === 'gov_supervisor') {
    $stmt = $pdo->prepare("SELECT id FROM ports WHERE governorate_id = ? AND is_active = 1");
    $stmt->execute([$currentUserData['governorate_id']]);
    $portIds = array_column($stmt->fetchAll(), 'id');
} else {
    $portIds = array_column($pdo->query("SELECT id FROM ports WHERE is_active = 1")->fetchAll(), 'id');
}

if (empty($portIds)) {
    $portIds = [0]; // يمنع أخطاء IN () الفارغة
}
$placeholders = implode(',', array_fill(0, count($portIds), '?'));

$alerts = []; // كل عنصر: type, message, severity, related, time

/* ------------------------------------------------------------
   1) قارب وصل ولم يبدأ إحصاؤه (بدون موظف مسند بعد 15 دقيقة)
------------------------------------------------------------ */
$sql = "SELECT t.trip_code, p.name AS port_name, t.actual_arrival
        FROM trips t JOIN ports p ON p.id = t.port_id
        WHERE t.port_id IN ($placeholders) AND t.status IN ('arrived','waiting_employee')
          AND t.assigned_employee_id IS NULL
          AND t.actual_arrival <= (NOW() - INTERVAL 15 MINUTE)";
$stmt = $pdo->prepare($sql);
$stmt->execute($portIds);
foreach ($stmt->fetchAll() as $r) {
    $alerts[] = ['type' => 'قارب وصل ولم يبدأ إحصاؤه', 'message' => "الرحلة {$r['trip_code']} بميناء {$r['port_name']} بانتظار إسناد موظف", 'severity' => 'critical', 'time' => $r['actual_arrival']];
}

/* ------------------------------------------------------------
   2) رحلة تجاوزت وقت الانتظار (موظف مسند لكنه لم يبدأ الإحصاء بعد 30 دقيقة)
------------------------------------------------------------ */
$sql = "SELECT t.trip_code, p.name AS port_name, t.actual_arrival
        FROM trips t JOIN ports p ON p.id = t.port_id
        WHERE t.port_id IN ($placeholders) AND t.status IN ('arrived','waiting_employee')
          AND t.assigned_employee_id IS NOT NULL
          AND t.actual_arrival <= (NOW() - INTERVAL 30 MINUTE)";
$stmt = $pdo->prepare($sql);
$stmt->execute($portIds);
foreach ($stmt->fetchAll() as $r) {
    $alerts[] = ['type' => 'رحلة تجاوزت وقت الانتظار', 'message' => "الرحلة {$r['trip_code']} بميناء {$r['port_name']} تجاوزت وقت الانتظار المسموح", 'severity' => 'warning', 'time' => $r['actual_arrival']];
}

/* ------------------------------------------------------------
   3) فرق تجاوز الحد المسموح (فروقات كبيرة غير معتمدة)
------------------------------------------------------------ */
$sql = "SELECT t.trip_code, p.name AS port_name, td.diff_percent, td.created_at
        FROM trip_discrepancies td JOIN trips t ON t.id = td.trip_id JOIN ports p ON p.id = t.port_id
        WHERE t.port_id IN ($placeholders) AND td.severity = 'major' AND td.review_status != 'approved'";
$stmt = $pdo->prepare($sql);
$stmt->execute($portIds);
foreach ($stmt->fetchAll() as $r) {
    $alerts[] = ['type' => 'فرق تجاوز الحد المسموح', 'message' => "الرحلة {$r['trip_code']} بميناء {$r['port_name']} بفرق " . round($r['diff_percent'],1) . "% بانتظار اعتماد المشرف", 'severity' => 'critical', 'time' => $r['created_at']];
}

/* ------------------------------------------------------------
   4) صنف غير مسجل من الكابتن (خلال آخر 3 أيام)
------------------------------------------------------------ */
$sql = "SELECT t.trip_code, p.name AS port_name, fs.name_ar, t.actual_arrival
        FROM catch_details cd
        JOIN trips t ON t.id = cd.trip_id
        JOIN ports p ON p.id = t.port_id
        JOIN fish_species fs ON fs.id = cd.species_id
        WHERE t.port_id IN ($placeholders) AND cd.is_unreported_by_captain = 1
          AND t.actual_arrival >= (NOW() - INTERVAL 3 DAY)";
$stmt = $pdo->prepare($sql);
$stmt->execute($portIds);
foreach ($stmt->fetchAll() as $r) {
    $alerts[] = ['type' => 'صنف غير مسجل من الكابتن', 'message' => "الرحلة {$r['trip_code']} بميناء {$r['port_name']}: صنف ({$r['name_ar']}) لم يدخله الكابتن", 'severity' => 'warning', 'time' => $r['actual_arrival']];
}

/* ------------------------------------------------------------
   5) صورة الميزان غير مرفقة / 6) توقيع الكابتن غير موجود (رحلات معتمدة اليوم)
------------------------------------------------------------ */
$sql = "SELECT t.id, t.trip_code, p.name AS port_name, t.approved_at
        FROM trips t JOIN ports p ON p.id = t.port_id
        WHERE t.port_id IN ($placeholders) AND t.status IN ('approved','closed')
          AND DATE(t.approved_at) = CURDATE()";
$stmt = $pdo->prepare($sql);
$stmt->execute($portIds);
$approvedToday = $stmt->fetchAll();

if (!empty($approvedToday)) {
    $tripIds = array_column($approvedToday, 'id');
    $tp = implode(',', array_fill(0, count($tripIds), '?'));
    $stmt = $pdo->prepare("SELECT trip_id, type FROM trip_attachments WHERE trip_id IN ($tp)");
    $stmt->execute($tripIds);
    $attachmentsByTrip = [];
    foreach ($stmt->fetchAll() as $a) {
        $attachmentsByTrip[$a['trip_id']][] = $a['type'];
    }
    foreach ($approvedToday as $t) {
        $types = $attachmentsByTrip[$t['id']] ?? [];
        if (!in_array('scale_photo', $types, true)) {
            $alerts[] = ['type' => 'صورة الميزان غير مرفقة', 'message' => "الرحلة {$t['trip_code']} بميناء {$t['port_name']} معتمدة بدون صورة ميزان", 'severity' => 'warning', 'time' => $t['approved_at']];
        }
        if (!in_array('captain_signature', $types, true)) {
            $alerts[] = ['type' => 'توقيع الكابتن غير موجود', 'message' => "الرحلة {$t['trip_code']} بميناء {$t['port_name']} معتمدة بدون توقيع الكابتن", 'severity' => 'warning', 'time' => $t['approved_at']];
        }
    }
}

/* ------------------------------------------------------------
   7) موظف غائب دون بديل (اليوم)
------------------------------------------------------------ */
$sql = "SELECT u.full_name, p.name AS port_name, s.name AS shift_name, ea.port_id, ea.shift_id
        FROM attendance a
        JOIN employees e ON e.id = a.employee_id
        JOIN users u ON u.id = e.user_id
        JOIN employee_assignments ea ON ea.employee_id = a.employee_id AND ea.assignment_date = a.attendance_date
        JOIN ports p ON p.id = ea.port_id
        JOIN shifts s ON s.id = ea.shift_id
        WHERE ea.port_id IN ($placeholders) AND a.attendance_date = CURDATE() AND a.status = 'absent'";
$stmt = $pdo->prepare($sql);
$stmt->execute($portIds);
foreach ($stmt->fetchAll() as $r) {
    // تحقق من وجود بديل مؤقت لنفس الميناء والمناوبة اليوم
    $subCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM employee_assignments
         WHERE port_id = ? AND shift_id = ? AND assignment_date = CURDATE() AND is_temporary = 1"
    );
    $subCheck->execute([$r['port_id'], $r['shift_id']]);
    if ((int)$subCheck->fetchColumn() === 0) {
        $alerts[] = ['type' => 'موظف غائب دون بديل', 'message' => "الموظف {$r['full_name']} غائب اليوم بميناء {$r['port_name']} ({$r['shift_name']}) بدون بديل", 'severity' => 'critical', 'time' => date('Y-m-d')];
    }
}

/* ------------------------------------------------------------
   8) ميناء غير مغطى (بدون أي تكليف اليوم)
------------------------------------------------------------ */
$sql = "SELECT p.id, p.name FROM ports p
        WHERE p.id IN ($placeholders) AND p.is_active = 1
          AND NOT EXISTS (SELECT 1 FROM employee_assignments ea WHERE ea.port_id = p.id AND ea.assignment_date = CURDATE())";
$stmt = $pdo->prepare($sql);
$stmt->execute($portIds);
foreach ($stmt->fetchAll() as $r) {
    $alerts[] = ['type' => 'ميناء غير مغطى', 'message' => "الميناء {$r['name']} بدون أي موظف إحصاء مسند لليوم", 'severity' => 'critical', 'time' => date('Y-m-d')];
}

/* ------------------------------------------------------------
   9) ازدحام قوارب في ميناء (3 قوارب أو أكثر بانتظار/تحت الإحصاء حاليًا)
------------------------------------------------------------ */
$sql = "SELECT p.name, COUNT(*) AS c FROM trips t JOIN ports p ON p.id = t.port_id
        WHERE t.port_id IN ($placeholders) AND t.status IN ('arrived','waiting_employee','counting')
        GROUP BY p.id, p.name HAVING c >= 3";
$stmt = $pdo->prepare($sql);
$stmt->execute($portIds);
foreach ($stmt->fetchAll() as $r) {
    $alerts[] = ['type' => 'ازدحام قوارب في ميناء', 'message' => "ميناء {$r['name']} به {$r['c']} قوارب بانتظار/تحت الإحصاء حاليًا", 'severity' => 'warning', 'time' => date('Y-m-d H:i')];
}

/* ------------------------------------------------------------
   10) تعديل بيانات بعد الاعتماد (آخر 7 أيام)
------------------------------------------------------------ */
$sql = "SELECT t.trip_code, p.name AS port_name, t.approved_at
        FROM trips t JOIN ports p ON p.id = t.port_id
        WHERE t.port_id IN ($placeholders) AND t.edited_after_approval = 1
          AND t.approved_at >= (NOW() - INTERVAL 7 DAY)";
$stmt = $pdo->prepare($sql);
$stmt->execute($portIds);
foreach ($stmt->fetchAll() as $r) {
    $alerts[] = ['type' => 'تعديل بيانات بعد الاعتماد', 'message' => "الرحلة {$r['trip_code']} بميناء {$r['port_name']} تم تعديل بياناتها بعد الاعتماد", 'severity' => 'warning', 'time' => $r['approved_at']];
}

/* ------------------------------------------------------------
   11) عقد أو وثيقة قربت من الانتهاء (خلال 30 يوم) - لا يعتمد على نطاق الموانئ
------------------------------------------------------------ */
if ($role !== 'port_supervisor') {
    $sql = "SELECT u.full_name, e.contract_end_date FROM employees e JOIN users u ON u.id = e.user_id
            WHERE e.contract_end_date IS NOT NULL
              AND e.contract_end_date BETWEEN CURDATE() AND (CURDATE() + INTERVAL 30 DAY)";
    foreach ($pdo->query($sql)->fetchAll() as $r) {
        $alerts[] = ['type' => 'عقد قرب من الانتهاء', 'message' => "عقد الموظف {$r['full_name']} ينتهي بتاريخ {$r['contract_end_date']}", 'severity' => 'warning', 'time' => $r['contract_end_date']];
    }
}

/* ------------------------------------------------------------
   ترتيب حسب الخطورة ثم الوقت
------------------------------------------------------------ */
$severityOrder = ['critical' => 0, 'warning' => 1, 'info' => 2];
usort($alerts, fn($a, $b) => $severityOrder[$a['severity']] <=> $severityOrder[$b['severity']]);

/* ------------------------------------------------------------
   مؤشرات مجمّعة حسب النوع
------------------------------------------------------------ */
$countByType = [];
foreach ($alerts as $a) {
    $countByType[$a['type']] = ($countByType[$a['type']] ?? 0) + 1;
}
$criticalCount = count(array_filter($alerts, fn($a) => $a['severity'] === 'critical'));
$warningCount  = count(array_filter($alerts, fn($a) => $a['severity'] === 'warning'));

require __DIR__ . '/../../includes/header.php';
?>

<div class="kpi-grid">
    <div class="kpi-card alert-tone"><span class="stat-label">تنبيهات حرجة</span><span class="stat-value"><?= numberAr($criticalCount) ?></span></div>
    <div class="kpi-card warn-tone"><span class="stat-label">تنبيهات تحذيرية</span><span class="stat-value"><?= numberAr($warningCount) ?></span></div>
    <div class="kpi-card"><span class="stat-label">قوارب لم يبدأ إحصاؤها</span><span class="stat-value"><?= numberAr($countByType['قارب وصل ولم يبدأ إحصاؤه'] ?? 0) ?></span></div>
    <div class="kpi-card"><span class="stat-label">رحلات تجاوزت الانتظار</span><span class="stat-value"><?= numberAr($countByType['رحلة تجاوزت وقت الانتظار'] ?? 0) ?></span></div>
    <div class="kpi-card"><span class="stat-label">فروقات كبيرة معلّقة</span><span class="stat-value"><?= numberAr($countByType['فرق تجاوز الحد المسموح'] ?? 0) ?></span></div>
    <div class="kpi-card"><span class="stat-label">موانئ غير مغطاة</span><span class="stat-value"><?= numberAr($countByType['ميناء غير مغطى'] ?? 0) ?></span></div>
    <div class="kpi-card"><span class="stat-label">موانئ مزدحمة</span><span class="stat-value"><?= numberAr($countByType['ازدحام قوارب في ميناء'] ?? 0) ?></span></div>
    <div class="kpi-card"><span class="stat-label">تعديلات بعد الاعتماد</span><span class="stat-value"><?= numberAr($countByType['تعديل بيانات بعد الاعتماد'] ?? 0) ?></span></div>
</div>

<div class="panel">
    <h3>كل التنبيهات النشطة (<?= numberAr(count($alerts)) ?>)</h3>
    <p class="panel-hint">تُحسب هذه التنبيهات مباشرة من البيانات الحية عند كل تحميل للصفحة.</p>
    <?php if (empty($alerts)): ?>
        <p class="panel-hint">لا توجد أي تنبيهات نشطة حاليًا ضمن نطاق صلاحيتك. ✅</p>
    <?php else: ?>
    <table>
        <thead><tr><th>النوع</th><th>التفاصيل</th><th>الخطورة</th><th>الوقت</th></tr></thead>
        <tbody>
        <?php foreach ($alerts as $a): ?>
            <tr>
                <td><?= e($a['type']) ?></td>
                <td><?= e($a['message']) ?></td>
                <td>
                    <?php $cls = $a['severity'] === 'critical' ? 'badge-danger' : ($a['severity'] === 'warning' ? 'badge-warning' : 'badge-info'); ?>
                    <span class="badge <?= $cls ?>"><?= $a['severity'] === 'critical' ? 'حرج' : ($a['severity'] === 'warning' ? 'تحذير' : 'معلومة') ?></span>
                </td>
                <td><?= e((string)$a['time']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
