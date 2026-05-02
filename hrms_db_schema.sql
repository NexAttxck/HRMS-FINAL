-- ============================================================
--  Staffora HRMS v3 — Complete Database Schema
--  Database : hrms_db
--  Engine   : InnoDB  |  Charset : utf8mb4
--  Generated: 2026-04-29
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `hrms_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `hrms_db`;

-- ------------------------------------------------------------
-- 1. DEPARTMENT
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `department` (
  `id`          INT            AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100)   NOT NULL,
  `description` TEXT           NULL,
  `location`    VARCHAR(150)   NULL,
  `budget`      DECIMAL(15,2)  NULL DEFAULT 0,
  `created_at`  INT            NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. USER
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user` (
  `id`            INT           AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(80)   NOT NULL,
  `email`         VARCHAR(150)  NOT NULL UNIQUE,
  `password`      VARCHAR(255)  NOT NULL,
  `role`          ENUM('Super Admin','Manager','Employee') NOT NULL DEFAULT 'Employee',
  `status`        ENUM('Active','Inactive')                NOT NULL DEFAULT 'Active',
  `department_id` INT           NULL,
  `created_at`    INT           NOT NULL DEFAULT 0,
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. POSITION
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `position` (
  `id`            INT            AUTO_INCREMENT PRIMARY KEY,
  `title`         VARCHAR(100)   NOT NULL,
  `department_id` INT            NULL,
  `salary_min`    DECIMAL(12,2)  NULL DEFAULT 0,
  `salary_max`    DECIMAL(12,2)  NULL DEFAULT 0,
  `created_at`    INT            NOT NULL DEFAULT 0,
  FOREIGN KEY (`department_id`) REFERENCES `department`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. EMPLOYEE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `employee` (
  `id`                          INT            AUTO_INCREMENT PRIMARY KEY,
  `user_id`                     INT            NULL,
  `employee_no`                 VARCHAR(30)    NULL,
  `first_name`                  VARCHAR(80)    NOT NULL,
  `middle_name`                 VARCHAR(80)    NULL,
  `last_name`                   VARCHAR(80)    NOT NULL,
  `suffix`                      VARCHAR(10)    NULL,
  `gender`                      VARCHAR(20)    NULL,
  `email`                       VARCHAR(150)   NULL,
  `phone`                       VARCHAR(30)    NULL,
  `department_id`               INT            NULL,
  `position_id`                 INT            NULL,
  `job_title`                   VARCHAR(100)   NULL,
  `employment_type`             ENUM('Full-Time','Part-Time','Contractual','Intern') NOT NULL DEFAULT 'Full-Time',
  `status`                      ENUM('Regular','Probationary','Resigned','Terminated','On Leave') NOT NULL DEFAULT 'Probationary',
  `hire_date`                   DATE           NULL,
  `basic_salary`                DECIMAL(12,2)  NOT NULL DEFAULT 0,
  `civil_status`                ENUM('Single','Married','Widowed','Separated','Divorced') NULL,
  `date_of_birth`               DATE           NULL,
  `place_of_birth`              VARCHAR(150)   NULL,
  `nationality`                 VARCHAR(60)    NULL DEFAULT 'Filipino',
  `blood_type`                  VARCHAR(5)     NULL,
  `highest_education`           VARCHAR(80)    NULL,
  `address`                     TEXT           NULL,
  `sss_no`                      VARCHAR(30)    NULL,
  `philhealth_no`               VARCHAR(30)    NULL,
  `pagibig_no`                  VARCHAR(30)    NULL,
  `tin_no`                      VARCHAR(30)    NULL,
  `bank_name`                   VARCHAR(80)    NULL,
  `bank_account_number`         VARCHAR(40)    NULL,
  `emergency_contact_name`      VARCHAR(100)   NULL,
  `emergency_contact_relationship` VARCHAR(60) NULL,
  `emergency_contact_phone`     VARCHAR(30)    NULL,
  `created_at`                  INT            NOT NULL DEFAULT 0,
  `updated_at`                  INT            NOT NULL DEFAULT 0,
  FOREIGN KEY (`user_id`)       REFERENCES `user`(`id`)       ON DELETE SET NULL,
  FOREIGN KEY (`department_id`) REFERENCES `department`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`position_id`)   REFERENCES `position`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. SYSTEM_SETTING
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_setting` (
  `id`         INT           AUTO_INCREMENT PRIMARY KEY,
  `key`        VARCHAR(80)   NOT NULL UNIQUE,
  `value`      TEXT          NULL,
  `updated_at` INT           NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 6. ATTENDANCE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance` (
  `id`          INT           AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT           NOT NULL,
  `date`        DATE          NOT NULL,
  `clock_in`    TIME          NULL,
  `clock_out`   TIME          NULL,
  `status`      ENUM('On Time','Late','Absent','Half-Day') NOT NULL DEFAULT 'On Time',
  `total_hours` DECIMAL(5,2)  NULL DEFAULT 0,
  `notes`       TEXT          NULL,
  `created_at`  INT           NOT NULL DEFAULT 0,
  UNIQUE KEY `uq_att_emp_date` (`employee_id`,`date`),
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 7. SHIFT
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shift` (
  `id`          INT           AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT           NOT NULL,
  `date`        DATE          NOT NULL,
  `start_time`  TIME          NOT NULL,
  `end_time`    TIME          NOT NULL,
  `shift_name`  VARCHAR(80)   NULL,
  `type`        ENUM('Onsite','Remote','Hybrid') NOT NULL DEFAULT 'Onsite',
  `status`      ENUM('Scheduled','Completed','Missed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `published`   TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  INT           NOT NULL DEFAULT 0,
  UNIQUE KEY `uq_shift_emp_date` (`employee_id`,`date`),
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 8. LEAVE_REQUEST
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leave_request` (
  `id`          INT           AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT           NOT NULL,
  `leave_type`  VARCHAR(60)   NOT NULL,
  `start_date`  DATE          NOT NULL,
  `end_date`    DATE          NOT NULL,
  `days`        DECIMAL(4,1)  NOT NULL DEFAULT 1,
  `reason`      TEXT          NULL,
  `status`      ENUM('Pending','Approved','Denied') NOT NULL DEFAULT 'Pending',
  `approved_by` INT           NULL,
  `created_at`  INT           NOT NULL DEFAULT 0,
  `updated_at`  INT           NOT NULL DEFAULT 0,
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `user`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 9. ANNOUNCEMENT
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `announcement` (
  `id`              INT           AUTO_INCREMENT PRIMARY KEY,
  `title`           VARCHAR(200)  NOT NULL,
  `content`         TEXT          NOT NULL,
  `posted_by`       INT           NULL,
  `priority`        ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `pinned`          TINYINT(1)    NOT NULL DEFAULT 0,
  `target_audience` VARCHAR(50)   NOT NULL DEFAULT 'All',
  `department_id`   INT           NULL,
  `created_at`      INT           NOT NULL DEFAULT 0,
  `updated_at`      INT           NOT NULL DEFAULT 0,
  FOREIGN KEY (`posted_by`)     REFERENCES `user`(`id`)       ON DELETE SET NULL,
  FOREIGN KEY (`department_id`) REFERENCES `department`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 10. HOLIDAY
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `holiday` (
  `id`           INT           AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(150)  NOT NULL,
  `date`         DATE          NOT NULL,
  `type`         ENUM('Regular','Special Non-Working','Special Working') NOT NULL DEFAULT 'Regular',
  `is_recurring` TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at`   INT           NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 11. PAYROLL
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payroll` (
  `id`           INT           AUTO_INCREMENT PRIMARY KEY,
  `period_label` VARCHAR(100)  NOT NULL,
  `period_start` DATE          NOT NULL,
  `period_end`   DATE          NOT NULL,
  `pay_date`     DATE          NOT NULL,
  `status`       ENUM('Draft','Processed','Released') NOT NULL DEFAULT 'Draft',
  `created_by`   INT           NULL,
  `created_at`   INT           NOT NULL DEFAULT 0,
  `updated_at`   INT           NOT NULL DEFAULT 0,
  FOREIGN KEY (`created_by`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 12. PAYSLIP
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payslip` (
  `id`               INT            AUTO_INCREMENT PRIMARY KEY,
  `payroll_id`       INT            NOT NULL,
  `employee_id`      INT            NOT NULL,
  `basic_salary`     DECIMAL(12,2)  NOT NULL DEFAULT 0,
  `allowances`       DECIMAL(12,2)  NOT NULL DEFAULT 0,
  `overtime_pay`     DECIMAL(12,2)  NOT NULL DEFAULT 0,
  `gross_pay`        DECIMAL(12,2)  NOT NULL DEFAULT 0,
  `sss`              DECIMAL(10,2)  NOT NULL DEFAULT 0,
  `phil_health`      DECIMAL(10,2)  NOT NULL DEFAULT 0,
  `pag_ibig`         DECIMAL(10,2)  NOT NULL DEFAULT 0,
  `income_tax`       DECIMAL(10,2)  NOT NULL DEFAULT 0,
  `total_deductions` DECIMAL(12,2)  NOT NULL DEFAULT 0,
  `net_pay`          DECIMAL(12,2)  NOT NULL DEFAULT 0,
  `status`           ENUM('Processed','Released','Cancelled') NOT NULL DEFAULT 'Processed',
  `created_at`       INT            NOT NULL DEFAULT 0,
  UNIQUE KEY `uq_payslip` (`payroll_id`,`employee_id`),
  FOREIGN KEY (`payroll_id`)  REFERENCES `payroll`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 13. PERSONNEL_ACTION
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `personnel_action` (
  `id`             INT           AUTO_INCREMENT PRIMARY KEY,
  `employee_id`    INT           NOT NULL,
  `type`           ENUM('Promotion','Demotion','Salary Adjustment','Transfer','Resignation','Termination','Other') NOT NULL,
  `effective_date` DATE          NOT NULL,
  `old_value`      VARCHAR(200)  NULL,
  `new_value`      VARCHAR(200)  NULL,
  `remarks`        TEXT          NULL,
  `status`         ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Approved',
  `created_by`     INT           NULL,
  `created_at`     INT           NOT NULL DEFAULT 0,
  `updated_at`     INT           NOT NULL DEFAULT 0,
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`)  REFERENCES `user`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 14. FEEDBACK
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `feedback` (
  `id`           INT           AUTO_INCREMENT PRIMARY KEY,
  `sender_id`    INT           NULL,
  `receiver_id`  INT           NOT NULL,
  `message`      TEXT          NOT NULL,
  `category`     ENUM('positive','constructive','suggestion') NOT NULL DEFAULT 'positive',
  `is_anonymous` TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at`   INT           NOT NULL DEFAULT 0,
  FOREIGN KEY (`sender_id`)   REFERENCES `employee`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`receiver_id`) REFERENCES `employee`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 15. BENEFIT
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `benefit` (
  `id`          INT            AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100)   NOT NULL,
  `description` TEXT           NULL,
  `type`        VARCHAR(50)    NOT NULL DEFAULT 'Other',
  `value`       DECIMAL(12,2)  NOT NULL DEFAULT 0,
  `created_at`  INT            NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 16. EMPLOYEE_BENEFIT
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `employee_benefit` (
  `id`          INT   AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT   NOT NULL,
  `benefit_id`  INT   NOT NULL,
  `enrolled_at` DATE  NULL,
  UNIQUE KEY `uq_emp_benefit` (`employee_id`,`benefit_id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`benefit_id`)  REFERENCES `benefit`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 17. ONBOARDING_TASK (legacy — superseded by doc checklist)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `onboarding_task` (
  `id`          INT           AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT           NOT NULL,
  `task_name`   VARCHAR(200)  NOT NULL,
  `stage`       ENUM('Pre-boarding','Week 1','Week 2','Integration') NOT NULL DEFAULT 'Pre-boarding',
  `status`      ENUM('Not Started','In Progress','Completed','On Hold') NOT NULL DEFAULT 'Not Started',
  `due_date`    DATE          NULL,
  `notes`       TEXT          NULL,
  `created_at`  INT           NOT NULL DEFAULT 0,
  `updated_at`  INT           NOT NULL DEFAULT 0,
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 18. EMPLOYEE_DOCUMENT  (201 file uploads)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `employee_document` (
  `id`            INT           AUTO_INCREMENT PRIMARY KEY,
  `employee_id`   INT           NOT NULL,
  `document_type` VARCHAR(100)  NOT NULL,
  `file_name`     VARCHAR(255)  NOT NULL,
  `file_path`     VARCHAR(500)  NOT NULL,
  `uploaded_at`   INT           NOT NULL DEFAULT 0,
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 19. EMPLOYEE_DOC_CHECKLIST  (required document tracker)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `employee_doc_checklist` (
  `id`            INT           AUTO_INCREMENT PRIMARY KEY,
  `employee_id`   INT           NOT NULL,
  `document_type` VARCHAR(100)  NOT NULL,
  `status`        ENUM('Pending','Submitted','Approved','Waived') NOT NULL DEFAULT 'Pending',
  `document_id`   INT           NULL,
  `notes`         VARCHAR(255)  NULL,
  `created_at`    INT           NOT NULL DEFAULT 0,
  `updated_at`    INT           NOT NULL DEFAULT 0,
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`document_id`) REFERENCES `employee_document`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 20. AUDIT_LOG
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id`         INT           AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT           NULL,
  `action`     VARCHAR(100)  NOT NULL,
  `module`     VARCHAR(80)   NOT NULL,
  `model_id`   INT           NULL,
  `data`       TEXT          NULL,
  `ip`         VARCHAR(45)   NULL,
  `created_at` INT           NOT NULL DEFAULT 0,
  FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 21. SYSTEM_NOTIFICATION
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_notification` (
  `id`         INT           AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT           NOT NULL,
  `type`       ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `title`      VARCHAR(200)  NOT NULL,
  `message`    TEXT          NOT NULL,
  `link`       VARCHAR(500)  NULL,
  `is_read`    TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 22. LMS_MODULE  (disabled in UI — kept for data integrity)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_module` (
  `id`            INT           AUTO_INCREMENT PRIMARY KEY,
  `title`         VARCHAR(200)  NOT NULL,
  `description`   TEXT          NULL,
  `type`          ENUM('document','video','quiz') NOT NULL DEFAULT 'document',
  `duration_mins` INT           NOT NULL DEFAULT 0,
  `department_id` INT           NULL,
  `created_by`    INT           NULL,
  `created_at`    INT           NOT NULL DEFAULT 0,
  `updated_at`    INT           NOT NULL DEFAULT 0,
  FOREIGN KEY (`department_id`) REFERENCES `department`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `user`(`id`)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 23. LMS_ENROLLMENT  (disabled in UI — kept for data integrity)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_enrollment` (
  `id`           INT           AUTO_INCREMENT PRIMARY KEY,
  `module_id`    INT           NOT NULL,
  `employee_id`  INT           NOT NULL,
  `progress`     TINYINT       NOT NULL DEFAULT 0,
  `status`       ENUM('Not Started','In Progress','Completed') NOT NULL DEFAULT 'Not Started',
  `completed_at` INT           NULL,
  `created_at`   INT           NOT NULL DEFAULT 0,
  `updated_at`   INT           NOT NULL DEFAULT 0,
  UNIQUE KEY `uq_lms_enroll` (`module_id`,`employee_id`),
  FOREIGN KEY (`module_id`)   REFERENCES `lms_module`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 24. JOB_POSTING  (disabled in UI — kept for data integrity)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_posting` (
  `id`            INT            AUTO_INCREMENT PRIMARY KEY,
  `title`         VARCHAR(150)   NOT NULL,
  `department_id` INT            NULL,
  `description`   TEXT           NULL,
  `requirements`  TEXT           NULL,
  `salary_min`    DECIMAL(12,2)  NULL,
  `salary_max`    DECIMAL(12,2)  NULL,
  `location`      VARCHAR(150)   NULL,
  `type`          VARCHAR(40)    NULL,
  `status`        ENUM('Open','Closed','On Hold') NOT NULL DEFAULT 'Open',
  `posted_by`     INT            NULL,
  `created_at`    INT            NOT NULL DEFAULT 0,
  `updated_at`    INT            NOT NULL DEFAULT 0,
  FOREIGN KEY (`department_id`) REFERENCES `department`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`posted_by`)     REFERENCES `user`(`id`)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 25. CANDIDATE  (disabled in UI — kept for data integrity)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `candidate` (
  `id`               INT            AUTO_INCREMENT PRIMARY KEY,
  `job_id`           INT            NOT NULL,
  `name`             VARCHAR(150)   NOT NULL,
  `email`            VARCHAR(150)   NULL,
  `phone`            VARCHAR(30)    NULL,
  `stage`            ENUM('Applied','Screening','Interview','Offer','Hired','Rejected') NOT NULL DEFAULT 'Applied',
  `expected_salary`  DECIMAL(12,2)  NULL,
  `applied_at`       DATE           NULL,
  `created_at`       INT            NOT NULL DEFAULT 0,
  `updated_at`       INT            NOT NULL DEFAULT 0,
  FOREIGN KEY (`job_id`) REFERENCES `job_posting`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 26. PROJECT  (disabled in UI — kept for data integrity)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project` (
  `id`            INT           AUTO_INCREMENT PRIMARY KEY,
  `name`          VARCHAR(150)  NOT NULL,
  `owner_id`      INT           NULL,
  `department_id` INT           NULL,
  `description`   TEXT          NULL,
  `start_date`    DATE          NULL,
  `deadline`      DATE          NULL,
  `status`        ENUM('Active','On Hold','Completed','Cancelled') NOT NULL DEFAULT 'Active',
  `created_at`    INT           NOT NULL DEFAULT 0,
  `updated_at`    INT           NOT NULL DEFAULT 0,
  FOREIGN KEY (`owner_id`)      REFERENCES `employee`(`id`)   ON DELETE SET NULL,
  FOREIGN KEY (`department_id`) REFERENCES `department`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 27. PROJECT_MEMBER  (disabled in UI — kept for data integrity)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_member` (
  `id`          INT  AUTO_INCREMENT PRIMARY KEY,
  `project_id`  INT  NOT NULL,
  `employee_id` INT  NOT NULL,
  UNIQUE KEY `uq_proj_member` (`project_id`,`employee_id`),
  FOREIGN KEY (`project_id`)  REFERENCES `project`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 28. PROJECT_TASK  (disabled in UI — kept for data integrity)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_task` (
  `id`          INT           AUTO_INCREMENT PRIMARY KEY,
  `project_id`  INT           NOT NULL,
  `employee_id` INT           NULL,
  `title`       VARCHAR(200)  NOT NULL,
  `priority`    ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `status`      ENUM('To Do','In Progress','Completed','Cancelled')  NOT NULL DEFAULT 'To Do',
  `progress`    TINYINT       NOT NULL DEFAULT 0,
  `due_date`    DATE          NULL,
  `created_at`  INT           NOT NULL DEFAULT 0,
  FOREIGN KEY (`project_id`)  REFERENCES `project`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `employee`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF SCHEMA
-- Default credentials after running seed.php:
--   Super Admin : admin@cura.ph      / Password123
--   Manager     : hr@cura.ph         / Password123
--   Employee    : juan.delacruz@cura.ph / Password123
-- Run seed : http://localhost/HRMSV3/seed.php?force=1
-- ============================================================
