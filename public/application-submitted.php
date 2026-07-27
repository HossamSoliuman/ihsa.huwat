<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/includes/employment_public_functions.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$rawReference = $_GET['ref'] ?? '';
$reference = is_scalar($rawReference) ? strtoupper(trim((string)$rawReference)) : '';
$validReference = preg_match('/^APP-[A-F0-9]{24}$/', $reference) === 1;
$receipt = null;

if ($validReference && isset($_SESSION['employment_receipts'][$reference]) && is_array($_SESSION['employment_receipts'][$reference])) {
    $candidateReceipt = $_SESSION['employment_receipts'][$reference];
    if ((int)($candidateReceipt['submitted_at'] ?? 0) >= time() - 86400) {
        $receipt = $candidateReceipt;
    } else {
        unset($_SESSION['employment_receipts'][$reference]);
    }
}

if (!$validReference) {
    http_response_code(404);
}

$pageTitle = $validReference ? 'تم إرسال طلبك' : 'تعذر العثور على الطلب';
$pageDescription = $validReference
    ? 'تم استلام طلب التوظيف بنجاح.'
    : 'رقم طلب التوظيف غير صالح.';
$activePublicRoute = 'jobs';
$bodyClass = 'employment-submitted-page employment-hud-public';
$hidePublicHeader = true;
$forcePublicDarkTheme = true;

require BASE_PATH . '/includes/public_employment_header.php';
?>

<section class="employment-state-page employment-success-page">
    <div class="employment-container">
        <?php if (!$validReference): ?>
            <div class="employment-state-card">
                <span class="employment-state-icon" aria-hidden="true"><svg viewBox="0 0 72 72"><circle cx="36" cy="36" r="28"></circle><path d="M36 21v19M36 50h.01"></path></svg></span>
                <span class="employment-eyebrow">الرابط غير صالح</span>
                <h1>تعذر العثور على رقم الطلب</h1>
                <p>تحقق من الرابط أو ارجع إلى صفحة الوظائف المتاحة.</p>
                <a class="employment-button employment-button-primary" href="<?= e(BASE_URL . '/#available-jobs') ?>">عرض الوظائف</a>
            </div>
        <?php else: ?>
            <article class="employment-success-card">
                <div class="employment-success-mark" aria-hidden="true">
                    <svg viewBox="0 0 96 96"><circle cx="48" cy="48" r="39"></circle><path d="m30 49 12 12 25-29"></path></svg>
                </div>
                <span class="employment-eyebrow">وصل طلبك بنجاح</span>
                <h1><?= $receipt ? 'شكراً، تم استلام طلبك' : 'تم تسجيل رقم الطلب' ?></h1>
                <p>
                    <?php if ($receipt): ?>
                        استلمنا طلبك للتقديم على وظيفة <strong><?= e((string)$receipt['job_title']) ?></strong>. سيقوم فريق التوظيف بمراجعته والتواصل معك عبر بيانات الاتصال المسجلة إذا تم ترشيحك.
                    <?php else: ?>
                        احتفظ بالرقم المرجعي الظاهر أدناه. حفاظاً على خصوصيتك، لا نعرض تفاصيل الطلب بعد انتهاء جلسة التقديم.
                    <?php endif; ?>
                </p>

                <div class="employment-reference-ticket">
                    <small>الرقم المرجعي لطلبك</small>
                    <strong class="mono" data-application-reference><?= e($reference) ?></strong>
                    <button type="button" data-copy-reference>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="11" height="12" rx="2"></rect><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h2"></path></svg>
                        نسخ الرقم
                    </button>
                </div>
                <p class="employment-copy-status" data-copy-status aria-live="polite"></p>

                <?php if ($receipt): ?>
                    <dl class="employment-receipt-details">
                        <div><dt>الوظيفة</dt><dd><?= e((string)$receipt['job_title']) ?></dd></div>
                        <div><dt>مرجع الوظيفة</dt><dd class="mono"><?= e((string)$receipt['job_reference']) ?></dd></div>
                        <div><dt>البريد المسجل</dt><dd dir="ltr"><?= e((string)$receipt['email']) ?></dd></div>
                        <div><dt>تاريخ الإرسال</dt><dd><?= e(date('Y/m/d - H:i', (int)$receipt['submitted_at'])) ?></dd></div>
                    </dl>
                <?php endif; ?>

                <div class="employment-next-steps">
                    <h2>ماذا يحدث بعد ذلك؟</h2>
                    <ol>
                        <li><span>1</span><div><strong>مراجعة الطلب</strong><p>يتحقق فريق التوظيف من البيانات والمؤهلات والمرفقات.</p></div></li>
                        <li><span>2</span><div><strong>التواصل مع المرشحين</strong><p>سيتم التواصل عبر الجوال أو البريد المسجل عند الانتقال للمرحلة التالية.</p></div></li>
                        <li><span>3</span><div><strong>القرار النهائي</strong><p>بعد القبول، ينشئ المسؤول حساب الموظف ويرسل بيانات الدخول بطريقة مناسبة.</p></div></li>
                    </ol>
                </div>

                <div class="employment-success-actions">
                    <a class="employment-button employment-button-primary" href="<?= e(BASE_URL . '/#available-jobs') ?>">العودة إلى الوظائف</a>
                </div>
                <p class="employment-success-warning">لن نطلب منك كلمة المرور أو أي مبالغ مالية لمتابعة طلب التوظيف.</p>
            </article>
        <?php endif; ?>
    </div>
</section>

<?php require BASE_PATH . '/includes/public_employment_footer.php'; ?>
