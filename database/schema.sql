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
('quality_supervisor', 'مراقب الجودة',               'discrepancies.php');

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
    home_port_id INT NULL,
    FOREIGN KEY (home_port_id) REFERENCES ports(id) ON DELETE SET NULL
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
