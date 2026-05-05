-- ============================================================
-- Wi-Fi SSID Restriction Feature — Migration
-- Run once against hrms_db
-- ============================================================

-- TABLE: allowed_ssids
CREATE TABLE IF NOT EXISTS `allowed_ssids` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `ssid_name`      VARCHAR(100) NOT NULL,
  `location_label` VARCHAR(100) DEFAULT NULL,
  `is_active`      TINYINT(1)  NOT NULL DEFAULT 1,
  `created_by`     INT(11)     DEFAULT NULL,
  `created_at`     INT(11)     NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `allowed_ssids_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- TABLE: clock_in_attempts  (SSID-verified attempt log)
CREATE TABLE IF NOT EXISTS `clock_in_attempts` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `employee_id`   INT(11)      NOT NULL,
  `detected_ssid` VARCHAR(100) DEFAULT NULL,
  `status`        ENUM('success','failed') NOT NULL DEFAULT 'failed',
  `fail_reason`   ENUM('ssid_mismatch','no_wifi','permission_denied') DEFAULT NULL,
  `device_id`     VARCHAR(255) DEFAULT NULL,
  `ip_address`    VARCHAR(45)  DEFAULT NULL,
  `timestamp`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `idx_timestamp` (`timestamp`),
  CONSTRAINT `clock_in_attempts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed system settings for Wi-Fi restriction (safe INSERT IGNORE)
INSERT IGNORE INTO `system_setting` (`key`, `value`, `updated_at`) VALUES
  ('wifi_restriction_enabled', '0', UNIX_TIMESTAMP()),
  ('wifi_strict_mode',         '1', UNIX_TIMESTAMP());
