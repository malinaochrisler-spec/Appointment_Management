CREATE DATABASE IF NOT EXISTS appointment_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE appointment_management;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(30) NOT NULL UNIQUE,
    client_name VARCHAR(120) NOT NULL,
    client_email VARCHAR(190) NOT NULL,
    client_phone VARCHAR(30) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    status ENUM('Scheduled','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointments_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_appointments_date (appointment_date),
    INDEX idx_appointments_status (status),
    INDEX idx_appointments_client (client_name),
    INDEX idx_appointments_service (service_type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointment_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT UNSIGNED NOT NULL,
    reference_no VARCHAR(30) NOT NULL,
    action_type ENUM('Created','Updated','Deleted') NOT NULL,
    performed_by INT UNSIGNED NULL,
    details VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_user FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_transactions_appointment (appointment_id),
    INDEX idx_transactions_created (created_at)
) ENGINE=InnoDB;

-- Create the first administrator through setup.php, which uses password_hash().
