-- ============================================================
--  نظام إحصاء المصيد وإدارة الموانئ - قاعدة البيانات الكاملة
--  MySQL 8+ / MariaDB 10.4+
-- ============================================================

CREATE DATABASE IF NOT EXISTS fisheries_system
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fisheries_system;

-- ------------------------------------------------------------
-- 1) النطاق الجغرافي: المناطق - المحافظات - الموانئ
-- ------------------------------------------------------------
CREATE TABLE regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE governorates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    governorate_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    location_name VARCHAR(190) NULL,
    location_url VARCHAR(500) NULL,
    is_active TINYINT(1) DEFAULT 1,
    latitude DECIMAL(10,6) NULL,
    longitude DECIMAL(10,6) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (governorate_id) REFERENCES governorates(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2) الصلاحيات والمستخدمون
-- ------------------------------------------------------------
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,   -- مفتاح ثابت يُستخدم بالكود
    name_ar VARCHAR(150) NOT NULL,      -- الاسم المعروض
    dashboard_route VARCHAR(100) NOT NULL -- ملف الداشبورد الافتراضي لهذا الدور
) ENGINE=InnoDB;

INSERT INTO roles (code, name_ar, dashboard_route) VALUES
('super_admin',        'الإدارة العليا',            'admin.php'),
('region_manager',     'مدير المنطقة',               'region.php'),
('gov_supervisor',     'مشرف المحافظة',              'governorate.php'),
('port_supervisor',    'مشرف الميناء',               'port.php'),
('stat_employee',      'موظف الإحصاء',                'employee.php'),
('hr_manager',         'مدير الموارد البشرية',        'hr.php'),
('finance_officer',    'مسؤول الرواتب والمالية',      'payroll.php'),
('quality_supervisor', 'مراقب الجودة',               'discrepancies.php'),
('employee_portal',    'بوابة الموظف',                'employment_profile.php');

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    -- نطاق صلاحية المستخدم (يُملأ حسب الدور: منطقة/محافظة/ميناء)
    region_id INT NULL,
    governorate_id INT NULL,
    port_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL,
    FOREIGN KEY (governorate_id) REFERENCES governorates(id) ON DELETE SET NULL,
    FOREIGN KEY (port_id) REFERENCES ports(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3) موظفو الإحصاء (تفاصيل إضافية + الموارد البشرية)
-- ------------------------------------------------------------
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    national_id VARCHAR(20) NULL,
    hire_date DATE NULL,
    contract_type ENUM('permanent','temporary') DEFAULT 'permanent',
    contract_end_date DATE NULL,
    base_salary DECIMAL(10,2) DEFAULT 0,
    status ENUM('active','on_leave','suspended','terminated') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name ENUM('morning','evening','night') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL
) ENGINE=InnoDB;

INSERT INTO shifts (name, start_time, end_time) VALUES
('morning','06:00:00','14:00:00'),
('evening','14:00:00','22:00:00'),
('night','22:00:00','06:00:00');

CREATE TABLE employee_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    port_id INT NOT NULL,
    shift_id INT NOT NULL,
    assignment_date DATE NOT NULL,
    is_temporary TINYINT(1) DEFAULT 0,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (port_id) REFERENCES ports(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES shifts(id),
    UNIQUE KEY uniq_employee_day (employee_id, assignment_date)
) ENGINE=InnoDB;

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    shift_id INT NOT NULL,
    check_in DATETIME NULL,
    check_out DATETIME NULL,
    status ENUM('present','absent','late','on_leave') DEFAULT 'present',
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES shifts(id)
) ENGINE=InnoDB;

CREATE TABLE leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason VARCHAR(255) NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    period_month TINYINT NOT NULL,
    period_year SMALLINT NOT NULL,
    base_salary DECIMAL(10,2) DEFAULT 0,
    allowances DECIMAL(10,2) DEFAULT 0,
    overtime_hours DECIMAL(6,2) DEFAULT 0,
    overtime_amount DECIMAL(10,2) DEFAULT 0,
    bonuses DECIMAL(10,2) DEFAULT 0,
    deductions DECIMAL(10,2) DEFAULT 0,
    net_salary DECIMAL(10,2) DEFAULT 0,
    paid_status ENUM('pending','paid') DEFAULT 'pending',
    paid_at DATETIME NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_period (employee_id, period_month, period_year)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4) القوارب والكباتن والرحلات والمصيد
-- ------------------------------------------------------------
CREATE TABLE boats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    registration_no VARCHAR(50) NULL UNIQUE,
    boat_type ENUM('large','small','recreational','unclassified') NOT NULL DEFAULT 'unclassified',
    harbor_status ENUM('occupied','disabled','inactive','unclassified') NOT NULL DEFAULT 'unclassified',
    home_port_id INT NULL,
    FOREIGN KEY (home_port_id) REFERENCES ports(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE harbor_boat_capacities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    port_id INT NOT NULL,
    boat_type ENUM('large','small','recreational') NOT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('available','full','stopped') NOT NULL DEFAULT 'available',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_port_boat_type (port_id, boat_type),
    FOREIGN KEY (port_id) REFERENCES ports(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE harbor_workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    port_id INT NOT NULL,
    employee_name VARCHAR(150) NOT NULL,
    identity_number VARCHAR(255) NULL,
    nationality ENUM('saudi','non_saudi') NOT NULL DEFAULT 'saudi',
    worker_type ENUM('supervisor','contractor','fisherman','foreign_worker') NOT NULL,
    mobile_number VARCHAR(30) NULL,
    employment_status ENUM('active','suspended','expired') NOT NULL DEFAULT 'active',
    start_date DATE NULL,
    end_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (port_id) REFERENCES ports(id) ON DELETE CASCADE,
    INDEX idx_harbor_workers_port_type (port_id, worker_type, employment_status)
) ENGINE=InnoDB;

CREATE TABLE harbor_licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    port_id INT NOT NULL,
    license_number VARCHAR(80) NOT NULL,
    license_type ENUM('seasonal','operational') NOT NULL DEFAULT 'seasonal',
    license_holder_name VARCHAR(190) NOT NULL,
    boat_number VARCHAR(80) NULL,
    issue_date DATE NULL,
    expiry_date DATE NULL,
    license_status ENUM('valid','expired','suspended','cancelled') NOT NULL DEFAULT 'valid',
    attachment_path VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_harbor_license_number (license_number),
    FOREIGN KEY (port_id) REFERENCES ports(id) ON DELETE CASCADE,
    INDEX idx_harbor_licenses_port_type (port_id, license_type, license_status)
) ENGINE=InnoDB;

CREATE TABLE harbor_violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    port_id INT NOT NULL,
    violation_number VARCHAR(80) NOT NULL,
    violation_type VARCHAR(120) NOT NULL,
    violation_description TEXT NULL,
    violation_date DATETIME NOT NULL,
    boat_id INT NULL,
    boat_owner_name VARCHAR(190) NULL,
    fine_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    violation_status ENUM('open','paid','appealed','closed') NOT NULL DEFAULT 'open',
    created_by INT NULL,
    attachment_path VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_harbor_violation_number (violation_number),
    FOREIGN KEY (port_id) REFERENCES ports(id) ON DELETE CASCADE,
    FOREIGN KEY (boat_id) REFERENCES boats(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_harbor_violations_port_status (port_id, violation_status)
) ENGINE=InnoDB;

CREATE TABLE captains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    national_id VARCHAR(20) NULL,
    phone VARCHAR(20) NULL
) ENGINE=InnoDB;

CREATE TABLE fish_species (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_ar VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_code VARCHAR(30) NOT NULL UNIQUE,   -- مثل TR-1025
    boat_id INT NOT NULL,
    captain_id INT NOT NULL,
    port_id INT NOT NULL,
    assigned_employee_id INT NULL,
    expected_arrival DATETIME NULL,
    actual_arrival DATETIME NULL,
    captain_reported_weight DECIMAL(10,2) NULL, -- إدخال الكابتن
    verified_weight DECIMAL(10,2) NULL,         -- الوزن الفعلي المعتمد
    status ENUM(
        'expected','arrived','waiting_employee','counting',
        'pending_review','approved','closed'
    ) DEFAULT 'expected',
    counting_started_at DATETIME NULL,
    counting_ended_at DATETIME NULL,
    approved_by INT NULL,
    approved_at DATETIME NULL,
    edited_after_approval TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (boat_id) REFERENCES boats(id),
    FOREIGN KEY (captain_id) REFERENCES captains(id),
    FOREIGN KEY (port_id) REFERENCES ports(id),
    FOREIGN KEY (assigned_employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE catch_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    species_id INT NOT NULL,
    captain_reported_kg DECIMAL(10,2) DEFAULT 0,
    verified_kg DECIMAL(10,2) DEFAULT 0,
    boxes_count INT DEFAULT 0,
    is_unreported_by_captain TINYINT(1) DEFAULT 0, -- صنف غير مسجل من الكابتن
    scale_photo_path VARCHAR(255) NULL,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (species_id) REFERENCES fish_species(id)
) ENGINE=InnoDB;

CREATE TABLE trip_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    type ENUM('scale_photo','captain_signature','other') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE trip_discrepancies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    diff_kg DECIMAL(10,2) NOT NULL,
    diff_percent DECIMAL(5,2) NOT NULL,
    severity ENUM('minor','medium','major') NOT NULL, -- 3-5% / 5-10% / >10%
    reason VARCHAR(255) NULL,
    review_status ENUM('pending','reviewed','approved') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5) التنبيهات
-- ------------------------------------------------------------
CREATE TABLE alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,   -- boat_not_started, waiting_timeout, diff_exceeded, ...
    message VARCHAR(255) NOT NULL,
    related_trip_id INT NULL,
    related_port_id INT NULL,
    related_employee_id INT NULL,
    severity ENUM('info','warning','critical') DEFAULT 'warning',
    is_resolved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    FOREIGN KEY (related_trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (related_port_id) REFERENCES ports(id) ON DELETE CASCADE,
    FOREIGN KEY (related_employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6) التوظيف العام وربط المقبولين ببوابة الموظف
-- ------------------------------------------------------------
CREATE TABLE employment_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(30) NOT NULL UNIQUE,
    title_ar VARCHAR(190) NOT NULL,
    department VARCHAR(190) NULL,
    summary TEXT NOT NULL,
    description TEXT NOT NULL,
    responsibilities TEXT NULL,
    requirements TEXT NOT NULL,
    employment_type ENUM('full_time','part_time','temporary','contract') NOT NULL DEFAULT 'full_time',
    vacancies SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    port_id INT NULL,
    city VARCHAR(120) NULL,
    salary_min DECIMAL(10,2) NULL,
    salary_max DECIMAL(10,2) NULL,
    application_deadline DATE NULL,
    status ENUM('draft','open','closed','archived') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    created_by INT NOT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_employment_jobs_port
        FOREIGN KEY (port_id) REFERENCES ports(id) ON DELETE SET NULL,
    CONSTRAINT fk_employment_jobs_created_by
        FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_employment_jobs_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employment_jobs_public (status, application_deadline, published_at),
    INDEX idx_employment_jobs_port (port_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE employment_applications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    reference_no VARCHAR(40) NOT NULL UNIQUE,
    status ENUM(
        'submitted','under_review','shortlisted','interview',
        'accepted','rejected','account_created','withdrawn'
    ) NOT NULL DEFAULT 'submitted',
    full_name VARCHAR(190) NOT NULL,
    nationality VARCHAR(100) NOT NULL,
    identity_type ENUM('national_id','residency','passport') NOT NULL,
    identity_number VARCHAR(50) NOT NULL,
    birth_date DATE NOT NULL,
    gender ENUM('male','female') NOT NULL,
    marital_status ENUM('single','married','divorced','widowed') NOT NULL,
    children_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    mobile VARCHAR(30) NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(190) NOT NULL,
    city VARCHAR(120) NOT NULL,
    address TEXT NOT NULL,
    preferred_port_id INT NULL,
    work_type ENUM('full_time','part_time','temporary','contract') NOT NULL DEFAULT 'full_time',
    source ENUM('website','social_media','referral','job_fair','other') NOT NULL DEFAULT 'website',
    education_level ENUM('high_school','diploma','bachelor','master','doctorate','other') NOT NULL,
    specialization VARCHAR(190) NOT NULL,
    institution VARCHAR(190) NOT NULL,
    graduation_year SMALLINT UNSIGNED NULL,
    experience_years DECIMAL(4,1) NOT NULL DEFAULT 0,
    current_employer VARCHAR(190) NULL,
    current_job_title VARCHAR(190) NULL,
    professional_summary TEXT NULL,
    skills TEXT NOT NULL,
    availability_date DATE NULL,
    cover_letter TEXT NULL,
    consent TINYINT(1) NOT NULL DEFAULT 0,
    admin_note TEXT NULL,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    accepted_at DATETIME NULL,
    employee_user_id INT NULL UNIQUE,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_employment_applications_job
        FOREIGN KEY (job_id) REFERENCES employment_jobs(id),
    CONSTRAINT fk_employment_applications_port
        FOREIGN KEY (preferred_port_id) REFERENCES ports(id) ON DELETE SET NULL,
    CONSTRAINT fk_employment_applications_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_employment_applications_user
        FOREIGN KEY (employee_user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_employment_job_identity (job_id, identity_number),
    INDEX idx_employment_applications_queue (status, submitted_at),
    INDEX idx_employment_applications_job_status (job_id, status),
    INDEX idx_employment_applications_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE employment_application_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id BIGINT UNSIGNED NOT NULL,
    attachment_type ENUM('cv','identity','certificate','other') NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_employment_attachments_application
        FOREIGN KEY (application_id) REFERENCES employment_applications(id) ON DELETE CASCADE,
    INDEX idx_employment_attachments_application (application_id, attachment_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE employment_application_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(40) NOT NULL,
    from_status VARCHAR(30) NULL,
    to_status VARCHAR(30) NULL,
    note TEXT NULL,
    actor_user_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_employment_events_application
        FOREIGN KEY (application_id) REFERENCES employment_applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_employment_events_actor
        FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employment_events_application (application_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE employees
    ADD COLUMN employment_application_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD COLUMN employee_number VARCHAR(40) NULL AFTER employment_application_id,
    ADD COLUMN job_title VARCHAR(190) NULL AFTER national_id,
    ADD COLUMN department VARCHAR(190) NULL AFTER job_title,
    ADD COLUMN job_grade VARCHAR(80) NULL AFTER department,
    ADD COLUMN supervisor_name VARCHAR(190) NULL AFTER job_grade,
    ADD COLUMN supervisor_phone VARCHAR(30) NULL AFTER supervisor_name,
    ADD UNIQUE KEY uniq_employees_employment_application (employment_application_id),
    ADD UNIQUE KEY uniq_employees_employee_number (employee_number),
    ADD CONSTRAINT fk_employees_employment_application
        FOREIGN KEY (employment_application_id)
        REFERENCES employment_applications(id) ON DELETE SET NULL;

-- ------------------------------------------------------------
-- بيانات أولية: أنواع الأسماك الشائعة (يمكن التعديل/الإضافة لاحقًا)
-- ------------------------------------------------------------
INSERT INTO fish_species (name_ar) VALUES
('هامور'), ('الشعري'), ('النقرور'), ('الكنعد'),
('الصافي'), ('الزبيدي'), ('البياح'), ('السبيطي'),
('الروبيان'), ('السردين');

-- ------------------------------------------------------------
-- ملاحظة: لا تُدرج هنا مستخدم admin بهاش ثابت (غير آمن وغير صالح).
-- بعد استيراد هذا الملف، شغّل مرة واحدة فقط:
--     https://your-domain.com/fisheries-system/public/setup.php
-- هذا السكربت ينشئ حساب "admin" بكلمة مرور تختارها أنت،
-- ثم يحذف نفسه تلقائيًا لأسباب أمنية.
-- ------------------------------------------------------------
