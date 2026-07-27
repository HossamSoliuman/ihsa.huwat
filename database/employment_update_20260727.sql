-- ============================================================
-- وحدة التوظيف والبوابة الذاتية للموظف
-- MariaDB 10.4+ / MySQL 8+
--
-- This is an additive, one-time migration for an existing installation.
-- Import it after selecting the project's existing database and back up first.
-- ============================================================

INSERT INTO roles (code, name_ar, dashboard_route)
VALUES ('employee_portal', 'بوابة الموظف', 'employment_profile.php')
ON DUPLICATE KEY UPDATE
    name_ar = VALUES(name_ar),
    dashboard_route = VALUES(dashboard_route);

CREATE TABLE IF NOT EXISTS employment_jobs (
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

CREATE TABLE IF NOT EXISTS employment_applications (
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

CREATE TABLE IF NOT EXISTS employment_application_attachments (
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

CREATE TABLE IF NOT EXISTS employment_application_events (
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
