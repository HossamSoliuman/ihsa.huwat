-- Harbor details module (safe additive migration)
-- MySQL 8+ / MariaDB 10.4+

ALTER TABLE ports
    ADD COLUMN IF NOT EXISTS location_name VARCHAR(190) NULL AFTER name,
    ADD COLUMN IF NOT EXISTS location_url VARCHAR(500) NULL AFTER location_name;

ALTER TABLE boats
    ADD COLUMN IF NOT EXISTS boat_type ENUM('large','small','recreational','unclassified') NOT NULL DEFAULT 'unclassified' AFTER registration_no,
    ADD COLUMN IF NOT EXISTS harbor_status ENUM('occupied','disabled','inactive','unclassified') NOT NULL DEFAULT 'unclassified' AFTER boat_type;

-- Also correct the definition when an earlier draft of this migration was run.
-- Existing classifications are preserved because SQL cannot determine whether
-- a previously stored value was selected deliberately or supplied by a default.
ALTER TABLE boats
    MODIFY COLUMN boat_type ENUM('large','small','recreational','unclassified') NOT NULL DEFAULT 'unclassified',
    MODIFY COLUMN harbor_status ENUM('occupied','disabled','inactive','unclassified') NOT NULL DEFAULT 'unclassified';

CREATE TABLE IF NOT EXISTS harbor_boat_capacities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    port_id INT NOT NULL,
    boat_type ENUM('large','small','recreational') NOT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('available','full','stopped') NOT NULL DEFAULT 'available',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_port_boat_type (port_id, boat_type),
    FOREIGN KEY (port_id) REFERENCES ports(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS harbor_workers (
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

CREATE TABLE IF NOT EXISTS harbor_licenses (
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

CREATE TABLE IF NOT EXISTS harbor_violations (
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

-- Capacities are harbor data, not migration defaults. The dashboard shows zero
-- until an authorized user records the real capacities for each harbor.
