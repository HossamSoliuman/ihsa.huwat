<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/includes/employment_public_functions.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

const EMPLOYMENT_UPLOAD_MAX_BYTES = 10 * 1024 * 1024;
const EMPLOYMENT_CERTIFICATE_MAX_COUNT = 5;

function employmentApplicationPost(string $key, bool $multiline = false): string
{
    $value = $_POST[$key] ?? '';
    if (!is_scalar($value)) {
        return '';
    }

    $value = str_replace("\0", '', (string)$value);
    if ($multiline) {
        return trim(str_replace(["\r\n", "\r"], "\n", $value));
    }

    return trim((string)preg_replace('/\s+/u', ' ', $value));
}

function employmentApplicationDate(string $value): ?DateTimeImmutable
{
    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
        return null;
    }

    return $date->format('Y-m-d') === $value ? $date : null;
}

function employmentApplicationUploadedEntries(string $field): array
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return [];
    }

    $file = $_FILES[$field];
    if (is_array($file['name'] ?? null)) {
        $entries = [];
        foreach ($file['name'] as $index => $name) {
            $error = $file['error'][$index] ?? UPLOAD_ERR_NO_FILE;
            $malformed = is_array($name)
                || is_array($file['tmp_name'][$index] ?? null)
                || is_array($error)
                || is_array($file['size'][$index] ?? null);
            $entries[] = [
                'name'     => $malformed ? 'invalid-upload' : (string)$name,
                'type'     => $malformed ? '' : (string)($file['type'][$index] ?? ''),
                'tmp_name' => $malformed ? '' : (string)($file['tmp_name'][$index] ?? ''),
                'error'    => $malformed ? UPLOAD_ERR_EXTENSION : (int)$error,
                'size'     => $malformed ? 0 : (int)($file['size'][$index] ?? 0),
            ];
        }
        return $entries;
    }

    $malformed = is_array($file['name'] ?? null)
        || is_array($file['type'] ?? null)
        || is_array($file['tmp_name'] ?? null)
        || is_array($file['error'] ?? null)
        || is_array($file['size'] ?? null);

    return [[
        'name'     => $malformed ? 'invalid-upload' : (string)($file['name'] ?? ''),
        'type'     => $malformed ? '' : (string)($file['type'] ?? ''),
        'tmp_name' => $malformed ? '' : (string)($file['tmp_name'] ?? ''),
        'error'    => $malformed ? UPLOAD_ERR_EXTENSION : (int)($file['error'] ?? UPLOAD_ERR_NO_FILE),
        'size'     => $malformed ? 0 : (int)($file['size'] ?? 0),
    ]];
}

function employmentApplicationCollectUploads(
    string $field,
    string $attachmentType,
    string $label,
    bool $required,
    int $maximumCount,
    array &$errors
): array {
    $entries = employmentApplicationUploadedEntries($field);
    $provided = array_values(array_filter(
        $entries,
        static fn(array $entry): bool => (int)$entry['error'] !== UPLOAD_ERR_NO_FILE && trim((string)$entry['name']) !== ''
    ));

    if (!$provided) {
        if ($required) {
            $errors[$field] = 'يرجى إرفاق ' . $label . '.';
        }
        return [];
    }

    if (count($provided) > $maximumCount) {
        $errors[$field] = 'يمكن إرفاق ' . $maximumCount . ' ملفات كحد أقصى في هذا الحقل.';
        return [];
    }

    $allowedMimeTypes = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
    ];
    $uploadErrorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'يتجاوز الملف الحد المسموح به على الخادم.',
        UPLOAD_ERR_FORM_SIZE  => 'يتجاوز الملف الحد الأقصى وهو 10 ميجابايت.',
        UPLOAD_ERR_PARTIAL    => 'لم يكتمل رفع الملف. حاول مرة أخرى.',
        UPLOAD_ERR_NO_TMP_DIR => 'تعذر تجهيز الملف للرفع.',
        UPLOAD_ERR_CANT_WRITE => 'تعذر حفظ الملف على الخادم.',
        UPLOAD_ERR_EXTENSION  => 'تم إيقاف رفع الملف لأسباب أمنية.',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $validated = [];

    foreach ($provided as $entry) {
        $uploadError = (int)$entry['error'];
        if ($uploadError !== UPLOAD_ERR_OK) {
            $errors[$field] = $uploadErrorMessages[$uploadError] ?? 'تعذر رفع الملف. حاول مرة أخرى.';
            continue;
        }

        $temporaryPath = (string)$entry['tmp_name'];
        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            $errors[$field] = 'تعذر التحقق من الملف المرفوع.';
            continue;
        }

        $actualSize = filesize($temporaryPath);
        if ($actualSize === false || $actualSize < 1) {
            $errors[$field] = 'الملف المرفوع فارغ أو غير صالح.';
            continue;
        }
        if ($actualSize > EMPLOYMENT_UPLOAD_MAX_BYTES) {
            $errors[$field] = 'يجب ألا يتجاوز حجم كل ملف 10 ميجابايت.';
            continue;
        }

        $mimeType = $finfo->file($temporaryPath);
        if (!is_string($mimeType) || !isset($allowedMimeTypes[$mimeType])) {
            $errors[$field] = 'الصيغ المسموحة هي PDF وJPEG وPNG فقط.';
            continue;
        }

        if (str_starts_with($mimeType, 'image/')) {
            $imageInfo = @getimagesize($temporaryPath);
            if ($imageInfo === false || ($imageInfo['mime'] ?? '') !== $mimeType) {
                $errors[$field] = 'ملف الصورة غير صالح.';
                continue;
            }
        } elseif ($mimeType === 'application/pdf') {
            $handle = @fopen($temporaryPath, 'rb');
            $signature = $handle ? (string)fread($handle, 5) : '';
            if (is_resource($handle)) {
                fclose($handle);
            }
            if ($signature !== '%PDF-') {
                $errors[$field] = 'ملف PDF غير صالح.';
                continue;
            }
        }

        $originalName = str_replace('\\', '/', (string)$entry['name']);
        $originalName = basename($originalName);
        $originalName = trim((string)preg_replace('/[\x00-\x1F\x7F]/u', '', $originalName));
        $originalName = mb_substr($originalName !== '' ? $originalName : $label, 0, 250);

        $validated[] = [
            'attachment_type' => $attachmentType,
            'original_name'   => $originalName,
            'temporary_path'  => $temporaryPath,
            'mime_type'       => $mimeType,
            'file_size'       => (int)$actualSize,
            'extension'       => $allowedMimeTypes[$mimeType],
        ];
    }

    return $validated;
}

$rawJobId = $_POST['job_id'] ?? $_GET['job_id'] ?? null;
$jobId = is_scalar($rawJobId) && filter_var($rawJobId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    ? (int)$rawJobId
    : 0;
$job = null;
$ports = [];
$loadError = null;
$errors = [];
$movedFiles = [];
$uploadDirectory = null;

if ($jobId > 0) {
    try {
        $pdo = db();
        $jobStatement = $pdo->prepare(
            "SELECT j.*, p.name AS port_name
             FROM employment_jobs j
             LEFT JOIN ports p ON p.id = j.port_id
             WHERE j.id = :id
               AND j.status = 'open'
               AND (j.published_at IS NULL OR j.published_at <= NOW())
               AND (j.application_deadline IS NULL OR j.application_deadline >= CURDATE())
             LIMIT 1"
        );
        $jobStatement->execute(['id' => $jobId]);
        $job = $jobStatement->fetch() ?: null;

        if ($job) {
            $portStatement = $pdo->prepare(
                'SELECT id, name FROM ports WHERE is_active = 1 OR id = :job_port_id ORDER BY name'
            );
            $portStatement->execute(['job_port_id' => (int)($job['port_id'] ?? 0)]);
            $ports = $portStatement->fetchAll();
        }
    } catch (Throwable $exception) {
        error_log('Public employment application load error: ' . $exception->getMessage());
        $loadError = 'تعذر تجهيز نموذج التقديم في الوقت الحالي.';
        http_response_code(500);
    }
}

if ($job === null && $loadError === null) {
    http_response_code(404);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $job) {
    $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    $postMaximum = employmentPublicIniBytes((string)ini_get('post_max_size'));

    if ($contentLength > 0 && $postMaximum > 0 && $contentLength > $postMaximum && empty($_POST)) {
        $errors['attachments'] = 'يتجاوز إجمالي حجم الطلب الحد المسموح به. قلّل أحجام المرفقات وحاول مرة أخرى.';
    } elseif (!is_string($_POST['csrf_token'] ?? null) || !verifyCsrf()) {
        $errors['general'] = 'انتهت صلاحية جلسة التقديم. أعد تحميل الصفحة ثم حاول مرة أخرى.';
    }

    if (employmentApplicationPost('website') !== '') {
        $errors['general'] = 'تعذر إرسال الطلب. يرجى إعادة المحاولة.';
    }

    $fullName = employmentApplicationPost('full_name');
    $nationality = employmentApplicationPost('nationality');
    $identityType = employmentApplicationPost('identity_type');
    $identityNumber = strtoupper((string)preg_replace('/[\s-]+/u', '', employmentApplicationPost('identity_number')));
    $birthDateValue = employmentApplicationPost('birth_date');
    $birthDate = employmentApplicationDate($birthDateValue);
    $gender = employmentApplicationPost('gender');
    $maritalStatus = employmentApplicationPost('marital_status');
    $childrenCountValue = employmentApplicationPost('children_count');
    $mobile = (string)preg_replace('/[\s()\-]+/', '', employmentApplicationPost('mobile'));
    $phone = (string)preg_replace('/[\s()\-]+/', '', employmentApplicationPost('phone'));
    $email = employmentApplicationPost('email');
    $city = employmentApplicationPost('city');
    $address = employmentApplicationPost('address', true);
    $preferredPortValue = employmentApplicationPost('preferred_port_id');
    $workType = employmentApplicationPost('work_type');
    $source = employmentApplicationPost('source');
    $educationLevel = employmentApplicationPost('education_level');
    $specialization = employmentApplicationPost('specialization');
    $institution = employmentApplicationPost('institution');
    $graduationYearValue = employmentApplicationPost('graduation_year');
    $experienceYearsValue = employmentApplicationPost('experience_years');
    $currentEmployer = employmentApplicationPost('current_employer');
    $currentJobTitle = employmentApplicationPost('current_job_title');
    $professionalSummary = employmentApplicationPost('professional_summary', true);
    $skills = employmentApplicationPost('skills', true);
    $availabilityDateValue = employmentApplicationPost('availability_date');
    $availabilityDate = employmentApplicationDate($availabilityDateValue);
    $coverLetter = employmentApplicationPost('cover_letter', true);
    $consent = employmentApplicationPost('consent') === '1';

    if (mb_strlen($fullName) < 3 || mb_strlen($fullName) > 150) {
        $errors['full_name'] = 'أدخل الاسم الرباعي كما يظهر في الوثائق الرسمية (3 إلى 150 حرفاً).';
    }
    if (mb_strlen($nationality) < 2 || mb_strlen($nationality) > 100) {
        $errors['nationality'] = 'أدخل الجنسية بشكل صحيح.';
    }
    if (!array_key_exists($identityType, employmentPublicIdentityLabels())) {
        $errors['identity_type'] = 'اختر نوع هوية صحيحاً.';
    }
    if ($identityType === 'national_id' && !preg_match('/^1[0-9]{9}$/', $identityNumber)) {
        $errors['identity_number'] = 'رقم الهوية الوطنية يجب أن يتكون من 10 أرقام ويبدأ بالرقم 1.';
    } elseif ($identityType === 'residency' && !preg_match('/^2[0-9]{9}$/', $identityNumber)) {
        $errors['identity_number'] = 'رقم هوية المقيم يجب أن يتكون من 10 أرقام ويبدأ بالرقم 2.';
    } elseif ($identityType === 'passport' && !preg_match('/^[A-Z0-9]{5,30}$/', $identityNumber)) {
        $errors['identity_number'] = 'أدخل رقم جواز صالحاً من 5 إلى 30 حرفاً إنجليزياً أو رقماً.';
    } elseif (!array_key_exists($identityType, employmentPublicIdentityLabels()) && !preg_match('/^[A-Z0-9]{5,50}$/', $identityNumber)) {
        $errors['identity_number'] = 'أدخل رقم هوية أو إقامة أو جواز صالحاً باستخدام الحروف الإنجليزية والأرقام.';
    }
    $today = new DateTimeImmutable('today');
    if (!$birthDate || $birthDate > $today) {
        $errors['birth_date'] = 'أدخل تاريخ ميلاد صحيحاً.';
    } elseif ($birthDate->diff($today)->y < 16 || $birthDate->diff($today)->y > 80) {
        $errors['birth_date'] = 'يجب أن يكون عمر المتقدم بين 16 و80 عاماً.';
    }
    if (!in_array($gender, ['male', 'female'], true)) {
        $errors['gender'] = 'اختر الجنس.';
    }
    if (!in_array($maritalStatus, ['single', 'married', 'divorced', 'widowed'], true)) {
        $errors['marital_status'] = 'اختر الحالة الاجتماعية.';
    }
    if (filter_var($childrenCountValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 30]]) === false) {
        $errors['children_count'] = 'أدخل عدداً صحيحاً من 0 إلى 30.';
    }
    if (!preg_match('/^\+?[0-9]{8,15}$/', $mobile)) {
        $errors['mobile'] = 'أدخل رقم جوال صحيحاً من 8 إلى 15 رقماً.';
    }
    if ($phone !== '' && !preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $errors['phone'] = 'أدخل رقم هاتف صحيحاً أو اترك الحقل فارغاً.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errors['email'] = 'أدخل بريداً إلكترونياً صالحاً.';
    }
    if (mb_strlen($city) < 2 || mb_strlen($city) > 120) {
        $errors['city'] = 'أدخل اسم المدينة (2 إلى 120 حرفاً).';
    }
    if (mb_strlen($address) < 5 || mb_strlen($address) > 1000) {
        $errors['address'] = 'أدخل عنواناً تفصيلياً من 5 إلى 1000 حرف.';
    }

    $allowedPortIds = array_map(static fn(array $port): int => (int)$port['id'], $ports);
    $preferredPortId = null;
    if ($preferredPortValue !== '') {
        $validatedPortId = filter_var($preferredPortValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($validatedPortId === false || !in_array((int)$validatedPortId, $allowedPortIds, true)) {
            $errors['preferred_port_id'] = 'اختر ميناءً متاحاً من القائمة.';
        } else {
            $preferredPortId = (int)$validatedPortId;
        }
    } elseif ($ports) {
        $errors['preferred_port_id'] = 'اختر الموقع أو الميناء المفضل.';
    }

    $allowedWorkTypes = ['full_time', 'part_time', 'temporary', 'contract'];
    if (!in_array($workType, $allowedWorkTypes, true)) {
        $errors['work_type'] = 'اختر نوع دوام صحيحاً.';
    }
    if (!array_key_exists($source, employmentPublicSourceLabels())) {
        $errors['source'] = 'اختر مصدر معرفتك بالوظيفة.';
    }
    if (!array_key_exists($educationLevel, employmentPublicEducationLabels())) {
        $errors['education_level'] = 'اختر المستوى التعليمي.';
    }
    if (mb_strlen($specialization) < 2 || mb_strlen($specialization) > 190) {
        $errors['specialization'] = 'أدخل التخصص (2 إلى 190 حرفاً).';
    }
    if (mb_strlen($institution) < 2 || mb_strlen($institution) > 190) {
        $errors['institution'] = 'أدخل اسم الجهة التعليمية.';
    }

    $graduationYear = null;
    $maximumGraduationYear = (int)date('Y') + 1;
    if ($graduationYearValue !== '') {
        $validatedGraduationYear = filter_var($graduationYearValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1950, 'max_range' => $maximumGraduationYear]]);
        if ($validatedGraduationYear === false) {
            $errors['graduation_year'] = 'أدخل سنة تخرج صحيحة.';
        } else {
            $graduationYear = (int)$validatedGraduationYear;
        }
    }

    $experienceYears = filter_var($experienceYearsValue, FILTER_VALIDATE_FLOAT);
    if ($experienceYears === false || $experienceYears < 0 || $experienceYears > 60) {
        $errors['experience_years'] = 'أدخل سنوات خبرة من 0 إلى 60.';
    } else {
        $experienceYears = round((float)$experienceYears, 1);
    }
    if (mb_strlen($currentEmployer) > 190) {
        $errors['current_employer'] = 'يجب ألا يتجاوز اسم جهة العمل 190 حرفاً.';
    }
    if (mb_strlen($currentJobTitle) > 190) {
        $errors['current_job_title'] = 'يجب ألا يتجاوز المسمى الوظيفي 190 حرفاً.';
    }
    if (mb_strlen($professionalSummary) > 3000) {
        $errors['professional_summary'] = 'يجب ألا يتجاوز الملخص المهني 3000 حرف.';
    }
    if (mb_strlen($skills) < 2 || mb_strlen($skills) > 3000) {
        $errors['skills'] = 'أدخل مهاراتك في حقل لا يتجاوز 3000 حرف.';
    }
    if ($availabilityDateValue !== '' && !$availabilityDate) {
        $errors['availability_date'] = 'أدخل تاريخ مباشرة صحيحاً.';
    } elseif ($availabilityDate && $availabilityDate < $today) {
        $errors['availability_date'] = 'يجب ألا يسبق تاريخ الجاهزية تاريخ اليوم.';
    }
    if (mb_strlen($coverLetter) > 5000) {
        $errors['cover_letter'] = 'يجب ألا تتجاوز الرسالة التعريفية 5000 حرف.';
    }
    if (!$consent) {
        $errors['consent'] = 'يجب الموافقة على الإقرار قبل إرسال الطلب.';
    }

    $uploads = [];
    if (!isset($errors['attachments'])) {
        $uploads = array_merge(
            $uploads,
            employmentApplicationCollectUploads('cv_file', 'cv', 'السيرة الذاتية', true, 1, $errors),
            employmentApplicationCollectUploads('identity_file', 'identity', 'صورة الهوية', false, 1, $errors),
            employmentApplicationCollectUploads('certificate_files', 'certificate', 'الشهادات', false, EMPLOYMENT_CERTIFICATE_MAX_COUNT, $errors)
        );
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $lockJob = $pdo->prepare(
                "SELECT id, title_ar, reference_no
                 FROM employment_jobs
                 WHERE id = :id
                   AND status = 'open'
                   AND (published_at IS NULL OR published_at <= NOW())
                   AND (application_deadline IS NULL OR application_deadline >= CURDATE())
                 FOR UPDATE"
            );
            $lockJob->execute(['id' => $jobId]);
            $lockedJob = $lockJob->fetch();

            if (!$lockedJob) {
                $pdo->rollBack();
                $errors['general'] = 'انتهت فترة التقديم على هذه الوظيفة قبل إرسال الطلب.';
            } else {
                $duplicate = $pdo->prepare(
                    'SELECT id FROM employment_applications WHERE job_id = :job_id AND identity_number = :identity_number LIMIT 1 FOR UPDATE'
                );
                $duplicate->execute(['job_id' => $jobId, 'identity_number' => $identityNumber]);

                if ($duplicate->fetchColumn()) {
                    $pdo->rollBack();
                    $errors['identity_number'] = 'سبق تقديم طلب لهذه الوظيفة باستخدام رقم الهوية نفسه.';
                } else {
                    $reference = employmentPublicReference($pdo);
                    $insertApplication = $pdo->prepare(
                        "INSERT INTO employment_applications (
                            job_id, reference_no, status, full_name, nationality,
                            identity_type, identity_number, birth_date, gender, marital_status,
                            children_count, mobile, phone, email, city, address,
                            preferred_port_id, work_type, source, education_level,
                            specialization, institution, graduation_year, experience_years,
                            current_employer, current_job_title, professional_summary, skills,
                            availability_date, cover_letter, consent, submitted_at, created_at, updated_at
                        ) VALUES (
                            :job_id, :reference_no, 'submitted', :full_name, :nationality,
                            :identity_type, :identity_number, :birth_date, :gender, :marital_status,
                            :children_count, :mobile, :phone, :email, :city, :address,
                            :preferred_port_id, :work_type, :source, :education_level,
                            :specialization, :institution, :graduation_year, :experience_years,
                            :current_employer, :current_job_title, :professional_summary, :skills,
                            :availability_date, :cover_letter, 1, NOW(), NOW(), NOW()
                        )"
                    );
                    $insertApplication->execute([
                        'job_id'              => $jobId,
                        'reference_no'        => $reference,
                        'full_name'           => $fullName,
                        'nationality'          => $nationality,
                        'identity_type'        => $identityType,
                        'identity_number'      => $identityNumber,
                        'birth_date'           => $birthDateValue,
                        'gender'               => $gender,
                        'marital_status'       => $maritalStatus,
                        'children_count'       => (int)$childrenCountValue,
                        'mobile'               => $mobile,
                        'phone'                => $phone !== '' ? $phone : null,
                        'email'                => $email,
                        'city'                 => $city,
                        'address'              => $address,
                        'preferred_port_id'    => $preferredPortId,
                        'work_type'            => $workType,
                        'source'               => $source,
                        'education_level'      => $educationLevel,
                        'specialization'       => $specialization,
                        'institution'          => $institution,
                        'graduation_year'      => $graduationYear,
                        'experience_years'     => $experienceYears,
                        'current_employer'     => $currentEmployer !== '' ? $currentEmployer : null,
                        'current_job_title'    => $currentJobTitle !== '' ? $currentJobTitle : null,
                        'professional_summary' => $professionalSummary !== '' ? $professionalSummary : null,
                        'skills'               => $skills,
                        'availability_date'    => $availabilityDateValue !== '' ? $availabilityDateValue : null,
                        'cover_letter'         => $coverLetter !== '' ? $coverLetter : null,
                    ]);
                    $applicationId = (int)$pdo->lastInsertId();

                    $storageRoot = BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'employment_uploads';
                    $uploadDirectory = $storageRoot . DIRECTORY_SEPARATOR . $reference;
                    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
                        throw new RuntimeException('Unable to create employment upload directory.');
                    }

                    $insertAttachment = $pdo->prepare(
                        'INSERT INTO employment_application_attachments
                         (application_id, attachment_type, original_name, stored_path, mime_type, file_size, created_at)
                         VALUES (:application_id, :attachment_type, :original_name, :stored_path, :mime_type, :file_size, NOW())'
                    );

                    foreach ($uploads as $upload) {
                        $storedName = bin2hex(random_bytes(18)) . '.' . $upload['extension'];
                        $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $storedName;
                        if (!move_uploaded_file($upload['temporary_path'], $destination)) {
                            throw new RuntimeException('Unable to move an employment attachment.');
                        }
                        // Windows' chmod emulation can make Apache-created files
                        // impossible for maintenance processes to remove. The
                        // inherited ACL is used there; Unix hosts receive 0640.
                        if (DIRECTORY_SEPARATOR !== '\\') {
                            @chmod($destination, 0640);
                        }
                        $movedFiles[] = $destination;
                        $storedPath = 'storage/employment_uploads/' . $reference . '/' . $storedName;

                        $insertAttachment->execute([
                            'application_id'  => $applicationId,
                            'attachment_type' => $upload['attachment_type'],
                            'original_name'   => $upload['original_name'],
                            'stored_path'     => $storedPath,
                            'mime_type'       => $upload['mime_type'],
                            'file_size'       => $upload['file_size'],
                        ]);
                    }

                    $insertEvent = $pdo->prepare(
                        "INSERT INTO employment_application_events
                         (application_id, event_type, from_status, to_status, note, actor_user_id, created_at)
                         VALUES (:application_id, 'submitted', NULL, 'submitted', :note, NULL, NOW())"
                    );
                    $insertEvent->execute([
                        'application_id' => $applicationId,
                        'note' => 'تم إرسال الطلب عبر بوابة التوظيف العامة.',
                    ]);

                    $pdo->commit();

                    if (!isset($_SESSION['employment_receipts']) || !is_array($_SESSION['employment_receipts'])) {
                        $_SESSION['employment_receipts'] = [];
                    }
                    $_SESSION['employment_receipts'][$reference] = [
                        'reference' => $reference,
                        'job_title' => (string)$lockedJob['title_ar'],
                        'job_reference' => (string)$lockedJob['reference_no'],
                        'email' => $email,
                        'submitted_at' => time(),
                    ];
                    if (count($_SESSION['employment_receipts']) > 5) {
                        $_SESSION['employment_receipts'] = array_slice($_SESSION['employment_receipts'], -5, null, true);
                    }
                    header('Location: ' . BASE_URL . '/application-submitted.php?ref=' . rawurlencode($reference));
                    exit;
                }
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($movedFiles as $movedFile) {
                if (is_file($movedFile)) {
                    @unlink($movedFile);
                }
            }
            if (is_string($uploadDirectory) && is_dir($uploadDirectory)) {
                @rmdir($uploadDirectory);
            }

            $isDuplicate = $exception instanceof PDOException
                && $exception->getCode() === '23000'
                && str_contains($exception->getMessage(), 'uniq_employment_job_identity');
            if ($isDuplicate) {
                $errors['identity_number'] = 'سبق تقديم طلب لهذه الوظيفة باستخدام رقم الهوية نفسه.';
            } else {
                error_log('Public employment application submit error: ' . $exception->getMessage());
                $errors['general'] = 'لم نتمكن من إرسال الطلب بسبب خطأ مؤقت. لم يتم حفظ أي مرفق؛ حاول مرة أخرى.';
            }
        }
    }

    if ($errors) {
        http_response_code(422);
    }
}

$stepFields = [
    1 => ['full_name', 'nationality', 'identity_type', 'identity_number', 'birth_date', 'gender', 'marital_status', 'children_count', 'mobile', 'phone', 'email', 'city', 'address', 'preferred_port_id', 'work_type', 'source'],
    2 => ['education_level', 'specialization', 'institution', 'graduation_year', 'experience_years', 'current_employer', 'current_job_title', 'professional_summary', 'skills', 'availability_date', 'cover_letter'],
    3 => ['attachments', 'cv_file', 'identity_file', 'certificate_files'],
    4 => ['consent'],
];
$initialStep = max(1, min(4, (int)($_POST['current_step'] ?? 1)));
foreach ($stepFields as $stepNumber => $fieldNames) {
    if (array_intersect(array_keys($errors), $fieldNames)) {
        $initialStep = $stepNumber;
        break;
    }
}

$defaultWorkType = $job && in_array((string)$job['employment_type'], ['full_time', 'part_time', 'temporary', 'contract'], true)
    ? (string)$job['employment_type']
    : 'full_time';
$defaultPortId = $job && !empty($job['port_id']) ? (int)$job['port_id'] : null;
$pageTitle = $job ? 'التقديم على ' . (string)$job['title_ar'] : 'التقديم على وظيفة';
$pageDescription = $job ? 'نموذج التقديم على وظيفة ' . (string)$job['title_ar'] : 'نموذج التقديم الإلكتروني.';
$activePublicRoute = 'jobs';
$bodyClass = 'employment-apply-page employment-hud-public';
$hidePublicHeader = true;
$forcePublicDarkTheme = true;

require BASE_PATH . '/includes/public_employment_header.php';
?>

<?php if (!$job): ?>
    <section class="employment-state-page">
        <div class="employment-container employment-state-card">
            <span class="employment-state-icon" aria-hidden="true"><svg viewBox="0 0 72 72"><path d="M17 23h38v34H17zM26 23v-7h20v7M17 35h38M31 35v5h10v-5"></path><path d="m25 49 22-22"></path></svg></span>
            <span class="employment-eyebrow"><?= $loadError ? 'خطأ مؤقت' : 'التقديم غير متاح' ?></span>
            <h1><?= $loadError ? 'تعذر تجهيز النموذج' : 'انتهى التقديم على هذه الوظيفة' ?></h1>
            <p><?= e($loadError ?? 'اختر وظيفة أخرى من قائمة الفرص المفتوحة وقدّم طلبك من صفحتها.') ?></p>
            <a class="employment-button employment-button-primary" href="<?= e(BASE_URL . '/#available-jobs') ?>">عرض الوظائف المتاحة</a>
        </div>
    </section>
<?php else: ?>
    <section class="employment-apply-heading">
        <div class="employment-container">
            <nav class="employment-breadcrumb" aria-label="مسار التنقل">
                <a href="<?= e(BASE_URL . '/#available-jobs') ?>">الوظائف المتاحة</a>
                <span aria-hidden="true">/</span>
                <a href="<?= e(BASE_URL . '/job.php?id=' . (int)$job['id']) ?>"><?= e((string)$job['title_ar']) ?></a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">التقديم</span>
            </nav>
            <span class="employment-eyebrow">طلب توظيف جديد</span>
            <h1>التقديم على وظيفة <em><?= e((string)$job['title_ar']) ?></em></h1>
            <p>أكمل البيانات التالية بدقة. الحقول المميزة بعلامة <span class="employment-required-mark">*</span> إلزامية.</p>
        </div>
    </section>

    <section class="employment-application-section">
        <div class="employment-container">
            <ol class="employment-stepper" aria-label="مراحل طلب التوظيف" data-employment-stepper>
                <?php foreach ([1 => 'البيانات الشخصية', 2 => 'المؤهلات والخبرات', 3 => 'المرفقات', 4 => 'مراجعة الطلب'] as $stepNumber => $stepLabel): ?>
                    <li class="<?= $stepNumber === $initialStep ? 'is-active' : ($stepNumber < $initialStep ? 'is-complete' : '') ?>" data-step-item="<?= $stepNumber ?>">
                        <button type="button" data-step-target="<?= $stepNumber ?>"<?= $stepNumber === $initialStep ? ' aria-current="step"' : '' ?> aria-label="الخطوة <?= $stepNumber ?>: <?= e($stepLabel) ?>">
                            <span><?= $stepNumber ?></span>
                            <strong><?= e($stepLabel) ?></strong>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ol>

            <?php if ($errors): ?>
                <div class="employment-error-summary" role="alert" tabindex="-1" data-error-summary>
                    <div>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4M12 17h.01"></path><path d="m10.3 3.8-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3l-8-14a2 2 0 0 0-3.4 0Z"></path></svg>
                        <h2>راجع البيانات قبل إعادة الإرسال</h2>
                    </div>
                    <ul>
                        <?php foreach ($errors as $errorField => $errorMessage): ?>
                            <li>
                                <?php if (!in_array($errorField, ['general', 'attachments'], true)): ?>
                                    <a href="#<?= e($errorField) ?>"><?= e((string)$errorMessage) ?></a>
                                <?php else: ?>
                                    <?= e((string)$errorMessage) ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="employment-application-layout">
                <form class="employment-application-form" method="post" action="<?= e(BASE_URL . '/apply.php?job_id=' . (int)$job['id']) ?>" enctype="multipart/form-data" novalidate data-employment-application data-initial-step="<?= $initialStep ?>" data-job-id="<?= (int)$job['id'] ?>" data-has-server-values="<?= $_SERVER['REQUEST_METHOD'] === 'POST' ? '1' : '0' ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
                    <input type="hidden" name="current_step" value="<?= $initialStep ?>" data-current-step>
                    <input type="hidden" name="MAX_FILE_SIZE" value="<?= EMPLOYMENT_UPLOAD_MAX_BYTES ?>">
                    <div class="employment-honeypot" aria-hidden="true">
                        <label for="website">اترك هذا الحقل فارغاً</label>
                        <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <section class="employment-form-panel<?= $initialStep === 1 ? ' is-active' : '' ?>" data-step-panel="1" aria-labelledby="personal-step-heading">
                        <div class="employment-panel-heading">
                            <span class="employment-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c.8-5 3.5-7 8-7s7.2 2 8 7"></path></svg></span>
                            <div><span>الخطوة الأولى</span><h2 id="personal-step-heading">البيانات الشخصية</h2><p>أدخل بياناتك كما تظهر في الوثائق الرسمية.</p></div>
                        </div>

                        <div class="employment-form-grid employment-form-grid-four">
                            <div class="employment-field employment-field-wide">
                                <label for="full_name">الاسم الرباعي <span aria-hidden="true">*</span></label>
                                <input id="full_name" name="full_name" type="text" value="<?= e(employmentPublicOld('full_name')) ?>" required minlength="3" maxlength="150" autocomplete="name"<?= employmentPublicErrorAttributes($errors, 'full_name') ?>>
                                <?= employmentPublicError($errors, 'full_name') ?>
                            </div>
                            <div class="employment-field">
                                <label for="nationality">الجنسية <span aria-hidden="true">*</span></label>
                                <input id="nationality" name="nationality" type="text" list="nationality-options" value="<?= e(employmentPublicOld('nationality')) ?>" required minlength="2" maxlength="100" autocomplete="country-name"<?= employmentPublicErrorAttributes($errors, 'nationality') ?>>
                                <datalist id="nationality-options"><option value="سعودي"><option value="مصري"><option value="أردني"><option value="سوداني"><option value="يمني"><option value="سوري"><option value="فلسطيني"><option value="باكستاني"><option value="هندي"><option value="بنغلاديشي"><option value="فلبيني"></datalist>
                                <?= employmentPublicError($errors, 'nationality') ?>
                            </div>
                            <div class="employment-field">
                                <label for="identity_type">نوع الهوية <span aria-hidden="true">*</span></label>
                                <select id="identity_type" name="identity_type" required<?= employmentPublicErrorAttributes($errors, 'identity_type') ?>><option value="">اختر نوع الهوية</option><?php foreach (employmentPublicIdentityLabels() as $value => $label): ?><option value="<?= e($value) ?>"<?= employmentPublicSelected('identity_type', $value) ?>><?= e($label) ?></option><?php endforeach; ?></select>
                                <?= employmentPublicError($errors, 'identity_type') ?>
                            </div>
                            <div class="employment-field">
                                <label for="identity_number">رقم الهوية / الإقامة <span aria-hidden="true">*</span></label>
                                <input id="identity_number" name="identity_number" type="text" value="<?= e(employmentPublicOld('identity_number')) ?>" required minlength="5" maxlength="50" inputmode="text" autocomplete="off" dir="ltr"<?= employmentPublicErrorAttributes($errors, 'identity_number') ?>>
                                <?= employmentPublicError($errors, 'identity_number') ?>
                            </div>
                            <div class="employment-field">
                                <label for="birth_date">تاريخ الميلاد <span aria-hidden="true">*</span></label>
                                <input id="birth_date" name="birth_date" type="date" value="<?= e(employmentPublicOld('birth_date')) ?>" required max="<?= e((new DateTimeImmutable('today'))->modify('-16 years')->format('Y-m-d')) ?>"<?= employmentPublicErrorAttributes($errors, 'birth_date') ?>>
                                <?= employmentPublicError($errors, 'birth_date') ?>
                            </div>
                            <fieldset class="employment-field employment-choice-field">
                                <legend>الجنس <span aria-hidden="true">*</span></legend>
                                <div class="employment-radio-group">
                                    <label><input id="gender" type="radio" name="gender" value="male" required<?= employmentPublicChecked('gender', 'male') ?>> <span>ذكر</span></label>
                                    <label><input type="radio" name="gender" value="female" required<?= employmentPublicChecked('gender', 'female') ?>> <span>أنثى</span></label>
                                </div>
                                <?= employmentPublicError($errors, 'gender') ?>
                            </fieldset>
                            <div class="employment-field">
                                <label for="marital_status">الحالة الاجتماعية <span aria-hidden="true">*</span></label>
                                <select id="marital_status" name="marital_status" required<?= employmentPublicErrorAttributes($errors, 'marital_status') ?>><option value="">اختر الحالة</option><option value="single"<?= employmentPublicSelected('marital_status', 'single') ?>>أعزب / عزباء</option><option value="married"<?= employmentPublicSelected('marital_status', 'married') ?>>متزوج / متزوجة</option><option value="divorced"<?= employmentPublicSelected('marital_status', 'divorced') ?>>مطلق / مطلقة</option><option value="widowed"<?= employmentPublicSelected('marital_status', 'widowed') ?>>أرمل / أرملة</option></select>
                                <?= employmentPublicError($errors, 'marital_status') ?>
                            </div>
                            <div class="employment-field">
                                <label for="children_count">عدد الأبناء <span aria-hidden="true">*</span></label>
                                <input id="children_count" name="children_count" type="number" value="<?= e(employmentPublicOld('children_count', '0')) ?>" required min="0" max="30" step="1" inputmode="numeric"<?= employmentPublicErrorAttributes($errors, 'children_count') ?>>
                                <?= employmentPublicError($errors, 'children_count') ?>
                            </div>
                            <div class="employment-field">
                                <label for="mobile">رقم الجوال <span aria-hidden="true">*</span></label>
                                <input id="mobile" name="mobile" type="tel" value="<?= e(employmentPublicOld('mobile')) ?>" required maxlength="20" autocomplete="tel" inputmode="tel" dir="ltr" placeholder="05XXXXXXXX"<?= employmentPublicErrorAttributes($errors, 'mobile') ?>>
                                <?= employmentPublicError($errors, 'mobile') ?>
                            </div>
                            <div class="employment-field">
                                <label for="phone">رقم الهاتف الثابت</label>
                                <input id="phone" name="phone" type="tel" value="<?= e(employmentPublicOld('phone')) ?>" maxlength="20" autocomplete="tel" inputmode="tel" dir="ltr" placeholder="01XXXXXXXX"<?= employmentPublicErrorAttributes($errors, 'phone') ?>>
                                <?= employmentPublicError($errors, 'phone') ?>
                            </div>
                            <div class="employment-field">
                                <label for="email">البريد الإلكتروني <span aria-hidden="true">*</span></label>
                                <input id="email" name="email" type="email" value="<?= e(employmentPublicOld('email')) ?>" required maxlength="150" autocomplete="email" dir="ltr" placeholder="example@domain.com"<?= employmentPublicErrorAttributes($errors, 'email') ?>>
                                <?= employmentPublicError($errors, 'email') ?>
                            </div>
                            <div class="employment-field employment-field-wide">
                                <label for="city">المدينة <span aria-hidden="true">*</span></label>
                                <input id="city" name="city" type="text" value="<?= e(employmentPublicOld('city')) ?>" required minlength="2" maxlength="120" autocomplete="address-level2"<?= employmentPublicErrorAttributes($errors, 'city') ?>>
                                <?= employmentPublicError($errors, 'city') ?>
                            </div>
                            <div class="employment-field employment-field-full">
                                <label for="address">العنوان التفصيلي <span aria-hidden="true">*</span></label>
                                <textarea id="address" name="address" rows="3" required minlength="5" maxlength="1000" autocomplete="street-address"<?= employmentPublicErrorAttributes($errors, 'address') ?>><?= e(employmentPublicOld('address')) ?></textarea>
                                <?= employmentPublicError($errors, 'address') ?>
                            </div>
                        </div>

                        <div class="employment-form-subsection">
                            <div class="employment-subsection-heading"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7h16v13H4zM8 7V4h8v3M4 12h16"></path></svg></span><div><h3>معلومات الوظيفة</h3><p>حدد تفضيلاتك المتعلقة بموقع ونوع العمل.</p></div></div>
                            <div class="employment-form-grid employment-form-grid-four">
                                <div class="employment-field employment-field-wide"><label for="job_title_display">الوظيفة المتقدم لها</label><input id="job_title_display" type="text" value="<?= e((string)$job['title_ar']) ?>" readonly data-review-static="job_title"></div>
                                <?php if ($ports): ?>
                                    <div class="employment-field"><label for="preferred_port_id">الموقع / الميناء <span aria-hidden="true">*</span></label><select id="preferred_port_id" name="preferred_port_id" required<?= employmentPublicErrorAttributes($errors, 'preferred_port_id') ?>><option value="">اختر الموقع</option><?php foreach ($ports as $port): ?><option value="<?= (int)$port['id'] ?>"<?= employmentPublicSelected('preferred_port_id', (int)$port['id'], $defaultPortId) ?>><?= e((string)$port['name']) ?></option><?php endforeach; ?></select><?= employmentPublicError($errors, 'preferred_port_id') ?></div>
                                <?php else: ?>
                                    <input type="hidden" name="preferred_port_id" value="">
                                <?php endif; ?>
                                <div class="employment-field"><label for="work_type">نوع الدوام <span aria-hidden="true">*</span></label><select id="work_type" name="work_type" required<?= employmentPublicErrorAttributes($errors, 'work_type') ?>><?php foreach (employmentPublicTypeLabels() as $value => $label): ?><option value="<?= e($value) ?>"<?= employmentPublicSelected('work_type', $value, $defaultWorkType) ?>><?= e($label) ?></option><?php endforeach; ?></select><?= employmentPublicError($errors, 'work_type') ?></div>
                                <div class="employment-field employment-field-wide"><label for="source">مصدر معرفتك بالوظيفة <span aria-hidden="true">*</span></label><select id="source" name="source" required<?= employmentPublicErrorAttributes($errors, 'source') ?>><option value="">اختر المصدر</option><?php foreach (employmentPublicSourceLabels() as $value => $label): ?><option value="<?= e($value) ?>"<?= employmentPublicSelected('source', $value) ?>><?= e($label) ?></option><?php endforeach; ?></select><?= employmentPublicError($errors, 'source') ?></div>
                            </div>
                        </div>

                        <div class="employment-form-actions"><button class="employment-button employment-button-outline" type="button" data-save-draft>حفظ مؤقت</button><button class="employment-button employment-button-primary" type="button" data-wizard-next>التالي <span aria-hidden="true">←</span></button></div>
                    </section>

                    <section class="employment-form-panel<?= $initialStep === 2 ? ' is-active' : '' ?>" data-step-panel="2" aria-labelledby="qualifications-step-heading">
                        <div class="employment-panel-heading"><span class="employment-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m3 9 9-5 9 5-9 5-9-5Z"></path><path d="M7 12v5c3 2 7 2 10 0v-5M21 9v6"></path></svg></span><div><span>الخطوة الثانية</span><h2 id="qualifications-step-heading">المؤهلات والخبرات</h2><p>عرّفنا بخلفيتك الأكاديمية وخبرتك العملية.</p></div></div>
                        <div class="employment-form-grid employment-form-grid-three">
                            <div class="employment-field"><label for="education_level">المستوى التعليمي <span aria-hidden="true">*</span></label><select id="education_level" name="education_level" required<?= employmentPublicErrorAttributes($errors, 'education_level') ?>><option value="">اختر المستوى</option><?php foreach (employmentPublicEducationLabels() as $value => $label): ?><option value="<?= e($value) ?>"<?= employmentPublicSelected('education_level', $value) ?>><?= e($label) ?></option><?php endforeach; ?></select><?= employmentPublicError($errors, 'education_level') ?></div>
                            <div class="employment-field"><label for="specialization">التخصص <span aria-hidden="true">*</span></label><input id="specialization" name="specialization" type="text" value="<?= e(employmentPublicOld('specialization')) ?>" required minlength="2" maxlength="190"<?= employmentPublicErrorAttributes($errors, 'specialization') ?>><?= employmentPublicError($errors, 'specialization') ?></div>
                            <div class="employment-field"><label for="institution">الجامعة / الجهة التعليمية <span aria-hidden="true">*</span></label><input id="institution" name="institution" type="text" value="<?= e(employmentPublicOld('institution')) ?>" required minlength="2" maxlength="190" autocomplete="organization"<?= employmentPublicErrorAttributes($errors, 'institution') ?>><?= employmentPublicError($errors, 'institution') ?></div>
                            <div class="employment-field"><label for="graduation_year">سنة التخرج</label><input id="graduation_year" name="graduation_year" type="number" value="<?= e(employmentPublicOld('graduation_year')) ?>" min="1950" max="<?= (int)date('Y') + 1 ?>" inputmode="numeric"<?= employmentPublicErrorAttributes($errors, 'graduation_year') ?>><?= employmentPublicError($errors, 'graduation_year') ?></div>
                            <div class="employment-field"><label for="experience_years">سنوات الخبرة <span aria-hidden="true">*</span></label><input id="experience_years" name="experience_years" type="number" value="<?= e(employmentPublicOld('experience_years', '0')) ?>" required min="0" max="60" step="0.5" inputmode="decimal"<?= employmentPublicErrorAttributes($errors, 'experience_years') ?>><?= employmentPublicError($errors, 'experience_years') ?></div>
                            <div class="employment-field"><label for="availability_date">تاريخ الجاهزية للمباشرة</label><input id="availability_date" name="availability_date" type="date" value="<?= e(employmentPublicOld('availability_date')) ?>" min="<?= e((new DateTimeImmutable('today'))->format('Y-m-d')) ?>"<?= employmentPublicErrorAttributes($errors, 'availability_date') ?>><?= employmentPublicError($errors, 'availability_date') ?></div>
                            <div class="employment-field"><label for="current_employer">جهة العمل الحالية</label><input id="current_employer" name="current_employer" type="text" value="<?= e(employmentPublicOld('current_employer')) ?>" maxlength="190" autocomplete="organization"<?= employmentPublicErrorAttributes($errors, 'current_employer') ?>><?= employmentPublicError($errors, 'current_employer') ?></div>
                            <div class="employment-field"><label for="current_job_title">المسمى الوظيفي الحالي</label><input id="current_job_title" name="current_job_title" type="text" value="<?= e(employmentPublicOld('current_job_title')) ?>" maxlength="190" autocomplete="organization-title"<?= employmentPublicErrorAttributes($errors, 'current_job_title') ?>><?= employmentPublicError($errors, 'current_job_title') ?></div>
                            <div class="employment-field employment-field-full"><label for="professional_summary">ملخص الخبرة المهنية</label><textarea id="professional_summary" name="professional_summary" rows="5" maxlength="3000" placeholder="اذكر أبرز مسؤولياتك وإنجازاتك العملية"<?= employmentPublicErrorAttributes($errors, 'professional_summary') ?>><?= e(employmentPublicOld('professional_summary')) ?></textarea><small data-character-count="professional_summary">الحد الأقصى 3000 حرف</small><?= employmentPublicError($errors, 'professional_summary') ?></div>
                            <div class="employment-field employment-field-full"><label for="skills">المهارات <span aria-hidden="true">*</span></label><textarea id="skills" name="skills" rows="4" required minlength="2" maxlength="3000" placeholder="مثال: تحليل البيانات، إعداد التقارير، التواصل..."<?= employmentPublicErrorAttributes($errors, 'skills') ?>><?= e(employmentPublicOld('skills')) ?></textarea><small data-character-count="skills">افصل بين المهارات بفاصلة</small><?= employmentPublicError($errors, 'skills') ?></div>
                            <div class="employment-field employment-field-full"><label for="cover_letter">رسالة تعريفية</label><textarea id="cover_letter" name="cover_letter" rows="5" maxlength="5000" placeholder="لماذا ترى أنك مناسب لهذه الوظيفة؟"<?= employmentPublicErrorAttributes($errors, 'cover_letter') ?>><?= e(employmentPublicOld('cover_letter')) ?></textarea><small data-character-count="cover_letter">اختياري — الحد الأقصى 5000 حرف</small><?= employmentPublicError($errors, 'cover_letter') ?></div>
                        </div>
                        <div class="employment-form-actions"><button class="employment-button employment-button-quiet" type="button" data-wizard-previous><span aria-hidden="true">→</span> السابق</button><button class="employment-button employment-button-outline" type="button" data-save-draft>حفظ مؤقت</button><button class="employment-button employment-button-primary" type="button" data-wizard-next>التالي <span aria-hidden="true">←</span></button></div>
                    </section>

                    <section class="employment-form-panel<?= $initialStep === 3 ? ' is-active' : '' ?>" data-step-panel="3" aria-labelledby="attachments-step-heading">
                        <div class="employment-panel-heading"><span class="employment-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 12.5 14.5 6a3 3 0 0 1 4.2 4.2l-8.5 8.5a5 5 0 0 1-7.1-7.1l8-8"></path></svg></span><div><span>الخطوة الثالثة</span><h2 id="attachments-step-heading">المرفقات</h2><p>PDF أو JPEG أو PNG، وبحد أقصى 10 ميجابايت لكل ملف.</p></div></div>
                        <?php if (isset($errors['attachments'])): ?><div class="employment-alert employment-alert-error" role="alert"><?= e((string)$errors['attachments']) ?></div><?php endif; ?>
                        <div class="employment-upload-grid">
                            <div class="employment-field employment-upload-field employment-upload-required" data-upload-zone>
                                <label for="cv_file"><span class="employment-upload-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 3h8l4 4v14H6zM14 3v5h5M9 13h6M9 17h6"></path></svg></span><strong>السيرة الذاتية <span aria-hidden="true">*</span></strong><small>ملف واحد مطلوب</small><span class="employment-upload-action">اختر ملفاً أو اسحبه هنا</span></label>
                                <input id="cv_file" name="cv_file" type="file" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"<?= employmentPublicErrorAttributes($errors, 'cv_file') ?>>
                                <ul class="employment-file-list" data-file-list="cv_file" aria-live="polite"></ul>
                                <?= employmentPublicError($errors, 'cv_file') ?>
                            </div>
                            <div class="employment-field employment-upload-field" data-upload-zone>
                                <label for="identity_file"><span class="employment-upload-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="8" cy="11" r="2"></circle><path d="M5 16c.5-2 1.5-3 3-3s2.5 1 3 3M14 9h4M14 13h4"></path></svg></span><strong>صورة الهوية</strong><small>اختياري — ملف واحد</small><span class="employment-upload-action">اختر ملفاً أو اسحبه هنا</span></label>
                                <input id="identity_file" name="identity_file" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"<?= employmentPublicErrorAttributes($errors, 'identity_file') ?>>
                                <ul class="employment-file-list" data-file-list="identity_file" aria-live="polite"></ul>
                                <?= employmentPublicError($errors, 'identity_file') ?>
                            </div>
                            <div class="employment-field employment-upload-field employment-upload-wide" data-upload-zone>
                                <label for="certificate_files"><span class="employment-upload-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 3h12v18H6zM9 8h6M9 12h6"></path><circle cx="12" cy="17" r="2"></circle></svg></span><strong>الشهادات والمؤهلات</strong><small>اختياري — حتى <?= EMPLOYMENT_CERTIFICATE_MAX_COUNT ?> ملفات</small><span class="employment-upload-action">اختر الملفات أو اسحبها هنا</span></label>
                                <input id="certificate_files" name="certificate_files[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"<?= employmentPublicErrorAttributes($errors, 'certificate_files') ?>>
                                <ul class="employment-file-list" data-file-list="certificate_files" aria-live="polite"></ul>
                                <?= employmentPublicError($errors, 'certificate_files') ?>
                            </div>
                        </div>
                        <div class="employment-security-note"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6l-8-3Z"></path><path d="m9 12 2 2 4-5"></path></svg><div><strong>مرفقاتك محمية</strong><p>تُحفظ الملفات خارج المسار العام، ولا يستطيع الاطلاع عليها إلا فريق التوظيف المخوّل.</p></div></div>
                        <div class="employment-form-actions"><button class="employment-button employment-button-quiet" type="button" data-wizard-previous><span aria-hidden="true">→</span> السابق</button><button class="employment-button employment-button-primary" type="button" data-wizard-next>المراجعة <span aria-hidden="true">←</span></button></div>
                    </section>

                    <section class="employment-form-panel<?= $initialStep === 4 ? ' is-active' : '' ?>" data-step-panel="4" aria-labelledby="review-step-heading">
                        <div class="employment-panel-heading"><span class="employment-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 3h12v18H6zM9 8h6M9 12h6M9 16h3"></path><path d="m15 16 1.5 1.5L20 14"></path></svg></span><div><span>الخطوة الرابعة</span><h2 id="review-step-heading">مراجعة الطلب</h2><p>تحقق من ملخص البيانات قبل الإرسال النهائي.</p></div></div>
                        <div class="employment-review-grid">
                            <section><div class="employment-review-heading"><h3>البيانات الشخصية</h3><button type="button" data-edit-step="1">تعديل</button></div><dl><div><dt>الاسم</dt><dd data-review-value="full_name">—</dd></div><div><dt>الجنسية</dt><dd data-review-value="nationality">—</dd></div><div><dt>نوع الهوية</dt><dd data-review-value="identity_type">—</dd></div><div><dt>رقم الهوية</dt><dd data-review-value="identity_number" dir="ltr">—</dd></div><div><dt>الجوال</dt><dd data-review-value="mobile" dir="ltr">—</dd></div><div><dt>البريد</dt><dd data-review-value="email" dir="ltr">—</dd></div><div><dt>المدينة</dt><dd data-review-value="city">—</dd></div></dl></section>
                            <section><div class="employment-review-heading"><h3>الوظيفة والتفضيلات</h3><button type="button" data-edit-step="1">تعديل</button></div><dl><div><dt>الوظيفة</dt><dd><?= e((string)$job['title_ar']) ?></dd></div><div><dt>الموقع المفضل</dt><dd data-review-value="preferred_port_id">—</dd></div><div><dt>نوع الدوام</dt><dd data-review-value="work_type">—</dd></div><div><dt>مصدر المعرفة</dt><dd data-review-value="source">—</dd></div></dl></section>
                            <section><div class="employment-review-heading"><h3>المؤهلات والخبرة</h3><button type="button" data-edit-step="2">تعديل</button></div><dl><div><dt>المؤهل</dt><dd data-review-value="education_level">—</dd></div><div><dt>التخصص</dt><dd data-review-value="specialization">—</dd></div><div><dt>الجهة التعليمية</dt><dd data-review-value="institution">—</dd></div><div><dt>سنوات الخبرة</dt><dd data-review-value="experience_years">—</dd></div><div><dt>المهارات</dt><dd data-review-value="skills">—</dd></div></dl></section>
                            <section><div class="employment-review-heading"><h3>المرفقات</h3><button type="button" data-edit-step="3">تعديل</button></div><dl><div><dt>السيرة الذاتية</dt><dd data-review-files="cv_file">لم يُحدد ملف</dd></div><div><dt>الهوية</dt><dd data-review-files="identity_file">لا يوجد</dd></div><div><dt>الشهادات</dt><dd data-review-files="certificate_files">لا يوجد</dd></div></dl></section>
                        </div>
                        <label class="employment-consent-box" for="consent"><input id="consent" name="consent" type="checkbox" value="1" required<?= employmentPublicChecked('consent', '1') ?><?= employmentPublicErrorAttributes($errors, 'consent') ?>><span><strong>إقرار بصحة البيانات <span aria-hidden="true">*</span></strong><small>أقر بأن جميع البيانات والمرفقات الواردة في هذا الطلب صحيحة، وأوافق على استخدامها لأغراض التوظيف والتحقق منها.</small></span></label>
                        <?= employmentPublicError($errors, 'consent') ?>
                        <div class="employment-submit-note" aria-live="polite" data-wizard-status></div>
                        <div class="employment-form-actions"><button class="employment-button employment-button-quiet" type="button" data-wizard-previous><span aria-hidden="true">→</span> السابق</button><button class="employment-button employment-button-primary employment-submit-button" type="submit" data-application-submit><span>إرسال الطلب</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 12 16-8-6 16-2-6-8-2Z"></path></svg></button></div>
                    </section>
                </form>

                <aside class="employment-application-sidebar" aria-label="ملخص طلب التوظيف">
                    <div class="employment-application-summary">
                        <span class="employment-summary-illustration" aria-hidden="true"><svg viewBox="0 0 96 96"><path d="M25 21h46v59H25zM36 21v-7h24v7M34 42h28M34 52h20M34 62h14"></path><circle cx="70" cy="70" r="15"></circle><path d="m63 70 5 5 9-11"></path></svg></span>
                        <small>أنت تتقدم على وظيفة</small>
                        <h2><?= e((string)$job['title_ar']) ?></h2>
                        <p><?= e(trim(implode('، ', array_filter([(string)($job['port_name'] ?? ''), (string)($job['city'] ?? '')])))) ?></p>
                        <dl><div><dt>الرقم المرجعي</dt><dd class="mono"><?= e((string)$job['reference_no']) ?></dd></div><div><dt>نوع الدوام</dt><dd><?= e(employmentPublicTypeLabel((string)$job['employment_type'])) ?></dd></div><div><dt>آخر موعد</dt><dd><?= e(employmentPublicDate($job['application_deadline'] ? (string)$job['application_deadline'] : null)) ?></dd></div></dl>
                    </div>
                    <div class="employment-help-card"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 13v-2a8 8 0 0 1 16 0v2M4 13h3v6H5a1 1 0 0 1-1-1v-5ZM20 13h-3v6h2a1 1 0 0 0 1-1v-5ZM17 19c0 2-2 2-4 2"></path></svg></span><div><h2>هل تحتاج مساعدة؟</h2><p>تأكد من تعبئة الحقول المطلوبة وإرفاق سيرة ذاتية واضحة.</p><a href="<?= e(BASE_URL . '/#application-process') ?>">راجع خطوات التقديم</a></div></div>
                </aside>
            </div>
            <p class="employment-draft-status" data-draft-status aria-live="polite"></p>
        </div>
    </section>
<?php endif; ?>

<?php require BASE_PATH . '/includes/public_employment_footer.php'; ?>
