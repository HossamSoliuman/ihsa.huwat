<?php
declare(strict_types=1);
?>
</main>

<footer class="employment-footer">
    <div class="employment-container employment-footer-inner">
        <div>
            <strong>بوابة التوظيف</strong>
            <p>فرص مهنية لخدمة الموانئ وقطاع الإحصاء.</p>
        </div>
        <nav aria-label="روابط التذييل">
            <a href="<?= e(BASE_URL . '/') ?>">الرئيسية</a>
            <a href="<?= e(BASE_URL . '/#available-jobs') ?>">الوظائف المتاحة</a>
            <a href="<?= e(BASE_URL . '/login.php') ?>">دخول الموظفين</a>
        </nav>
        <p class="employment-copyright">جميع الحقوق محفوظة &copy; <?= date('Y') ?></p>
    </div>
</footer>

<script src="<?= e(assetUrl('js/employment.js')) ?>"></script>
</body>
</html>
