-- Adds the audit table used by the three appointment database transactions.
-- Existing installations only need to run this file once.
USE appointment_management;

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
