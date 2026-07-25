<?php
/**
 * دوال مساعدة عامة تُستخدم في كل الصفحات
 */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function numberAr($number, int $decimals = 0): string
{
    if ($number === null) return '0';
    return number_format((float)$number, $decimals);
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * تصنيف نسبة الفرق بين إدخال الكابتن والوزن الفعلي
 */
function discrepancySeverity(float $percent): string
{
    $percent = abs($percent);
    if ($percent > 10) return 'major';
    if ($percent >= 5) return 'medium';
    if ($percent >= 3) return 'minor';
    return 'none';
}

function severityLabel(string $severity): string
{
    return match ($severity) {
        'major'  => 'فرق كبير',
        'medium' => 'فرق متوسط',
        'minor'  => 'فرق بسيط',
        default  => 'لا يوجد فرق',
    };
}

function severityBadgeClass(string $severity): string
{
    return match ($severity) {
        'major'  => 'badge-danger',
        'medium' => 'badge-warning',
        'minor'  => 'badge-info',
        default  => 'badge-success',
    };
}

function redirectWithMessage(string $url, string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: ' . $url);
    exit;
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
