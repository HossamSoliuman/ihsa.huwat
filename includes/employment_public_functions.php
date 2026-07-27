<?php
declare(strict_types=1);

/**
 * Small, presentation-safe helpers shared by the unauthenticated employment pages.
 * config/config.php must be loaded before this file.
 */

function employmentPublicTypeLabels(): array
{
    return [
        'full_time'          => 'دوام كامل',
        'part_time'          => 'دوام جزئي',
        'temporary'          => 'مؤقت',
        'contract'           => 'عقد',
    ];
}

function employmentPublicTypeLabel(?string $value): string
{
    if ($value === null || $value === '') {
        return 'غير محدد';
    }

    return employmentPublicTypeLabels()[$value] ?? str_replace('_', ' ', $value);
}

function employmentPublicIdentityLabels(): array
{
    return [
        'national_id' => 'هوية وطنية',
        'residency'   => 'هوية مقيم',
        'passport'    => 'جواز سفر',
    ];
}

function employmentPublicEducationLabels(): array
{
    return [
        'high_school' => 'الثانوية العامة',
        'diploma'     => 'دبلوم',
        'bachelor'    => 'بكالوريوس',
        'master'      => 'ماجستير',
        'doctorate'   => 'دكتوراه',
        'other'       => 'مؤهل آخر',
    ];
}

function employmentPublicSourceLabels(): array
{
    return [
        'website'          => 'بوابة التوظيف',
        'social_media'     => 'منصات التواصل الاجتماعي',
        'referral'         => 'ترشيح من موظف',
        'job_fair'         => 'معرض توظيف',
        'other'            => 'مصدر آخر',
    ];
}

function employmentPublicDate(?string $date): string
{
    if ($date === null || $date === '') {
        return 'غير محدد';
    }

    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10));
    return $parsed ? $parsed->format('Y/m/d') : $date;
}

function employmentPublicSalary(mixed $minimum, mixed $maximum): string
{
    $hasMinimum = $minimum !== null && $minimum !== '';
    $hasMaximum = $maximum !== null && $maximum !== '';

    if (!$hasMinimum && !$hasMaximum) {
        return 'حسب سلم الرواتب';
    }

    if ($hasMinimum && $hasMaximum) {
        if ((float)$minimum === (float)$maximum) {
            return number_format((float)$minimum) . ' ريال';
        }
        return number_format((float)$minimum) . ' – ' . number_format((float)$maximum) . ' ريال';
    }

    return $hasMinimum
        ? 'يبدأ من ' . number_format((float)$minimum) . ' ريال'
        : 'حتى ' . number_format((float)$maximum) . ' ريال';
}

function employmentPublicExcerpt(?string $text, int $limit = 180): string
{
    $text = trim((string)$text);
    if (mb_strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(mb_substr($text, 0, $limit - 1)) . '…';
}

function employmentPublicLines(?string $text): array
{
    $lines = preg_split('/\R/u', trim((string)$text)) ?: [];
    $lines = array_map(
        static fn(string $line): string => trim((string)preg_replace('/^[\s\x{2022}\-*]+/u', '', $line)),
        $lines
    );

    return array_values(array_filter($lines, static fn(string $line): bool => $line !== ''));
}

function employmentPublicOld(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_scalar($value) ? (string)$value : $default;
}

function employmentPublicSelected(string $key, string|int $value, string|int|null $default = null): string
{
    $current = $_POST[$key] ?? $default;
    return is_scalar($current) && (string)$current === (string)$value ? ' selected' : '';
}

function employmentPublicChecked(string $key, string|int $value, string|int|null $default = null): string
{
    $current = $_POST[$key] ?? $default;
    return is_scalar($current) && (string)$current === (string)$value ? ' checked' : '';
}

function employmentPublicErrorAttributes(array $errors, string $field): string
{
    if (!isset($errors[$field])) {
        return '';
    }

    return ' aria-invalid="true" aria-describedby="error-' . e($field) . '"';
}

function employmentPublicError(array $errors, string $field): string
{
    if (!isset($errors[$field])) {
        return '';
    }

    return '<p class="employment-field-error" id="error-' . e($field) . '">' . e((string)$errors[$field]) . '</p>';
}

function employmentPublicIniBytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float)$value;

    return match ($unit) {
        'g' => (int)($number * 1024 * 1024 * 1024),
        'm' => (int)($number * 1024 * 1024),
        'k' => (int)($number * 1024),
        default => (int)$number,
    };
}

function employmentPublicReference(PDO $pdo): string
{
    $check = $pdo->prepare('SELECT id FROM employment_applications WHERE reference_no = ? LIMIT 1');

    for ($attempt = 0; $attempt < 8; $attempt++) {
        $reference = 'APP-' . strtoupper(bin2hex(random_bytes(12)));
        $check->execute([$reference]);
        if (!$check->fetchColumn()) {
            return $reference;
        }
    }

    throw new RuntimeException('Unable to allocate an application reference.');
}
