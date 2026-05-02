-- ============================================================
--  Staffora HRMS v3 — Oracle DDL (Part 1 of 2)
--  Compatible with: Oracle 12c+ / SQL Developer Data Modeler
--  Import via: File > Import > DDL File
--  Tables 1-14 of 28
-- ============================================================

-- Drop tables in reverse FK order (safe re-run)
BEGIN
  FOR t IN (
    SELECT table_name FROM user_tables WHERE table_name IN (
      'PERSONNEL_ACTION','PAYSLIP','PAYROLL','LEAVE_REQUEST','ATTENDANCE',
      'SHIFT','ANNOUNCEMENT','EMPLOYEE_BENEFIT','BENEFIT','ONBOARDING_TASK',
      'EMPLOYEE_DOCUMENT','FEEDBACK','HOLIDAY','SYSTEM_SETTING',
      'EMPLOYEE','POSITION','USER_ACCOUNT','DEPARTMENT'
    )
  ) LOOP
    EXECUTE IMMEDIATE 'DROP TABLE "' || t.table_name || '" CASCADE CONSTRAINTS';
  END LOOP;
END;
/

-- ============================================================
-- 1. DEPARTMENT
-- ============================================================
CREATE TABLE department (
  id          NUMBER(10)      GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  name        VARCHAR2(100)   NOT NULL,
  description CLOB            NULL,
  location    VARCHAR2(150)   NULL,
  budget      NUMBER(15,2)    DEFAULT 0,
  created_at  NUMBER(12)      DEFAULT 0 NOT NULL,
  CONSTRAINT uq_dept_name UNIQUE (name)
);

COMMENT ON TABLE  department        IS 'Organisational departments / cost centres';
COMMENT ON COLUMN department.budget IS 'Annual budget allocation in PHP';

-- ============================================================
-- 2. USER_ACCOUNT  (named user_account — USER is reserved in Oracle)
-- ============================================================
CREATE TABLE user_account (
  id            NUMBER(10)    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  username      VARCHAR2(80)  NOT NULL,
  email         VARCHAR2(150) NOT NULL,
  password      VARCHAR2(255) NOT NULL,
  role          VARCHAR2(20)  DEFAULT 'Employee' NOT NULL,
  status        VARCHAR2(10)  DEFAULT 'Active'   NOT NULL,
  department_id NUMBER(10)    NULL,
  created_at    NUMBER(12)    DEFAULT 0 NOT NULL,
  CONSTRAINT uq_user_email    UNIQUE (email),
  CONSTRAINT uq_user_username UNIQUE (username),
  CONSTRAINT chk_user_role    CHECK (role   IN ('Super Admin','Manager','Employee')),
  CONSTRAINT chk_user_status  CHECK (status IN ('Active','Inactive')),
  CONSTRAINT fk_user_dept     FOREIGN KEY (department_id)
    REFERENCES department(id) ON DELETE SET NULL
);

COMMENT ON TABLE user_account IS 'Authentication accounts. Maps 1-to-1 with employee.';

-- ============================================================
-- 3. POSITION
-- ============================================================
CREATE TABLE position (
  id            NUMBER(10)    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  title         VARCHAR2(100) NOT NULL,
  department_id NUMBER(10)    NULL,
  salary_min    NUMBER(12,2)  DEFAULT 0,
  salary_max    NUMBER(12,2)  DEFAULT 0,
  created_at    NUMBER(12)    DEFAULT 0 NOT NULL,
  CONSTRAINT fk_pos_dept FOREIGN KEY (department_id)
    REFERENCES department(id) ON DELETE SET NULL
);

COMMENT ON TABLE position IS 'Job positions / designations with salary bands';

-- ============================================================
-- 4. EMPLOYEE
-- ============================================================
CREATE TABLE employee (
  id                             NUMBER(10)    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  user_id                        NUMBER(10)    NULL,
  employee_no                    VARCHAR2(30)  NULL,
  first_name                     VARCHAR2(80)  NOT NULL,
  middle_name                    VARCHAR2(80)  NULL,
  last_name                      VARCHAR2(80)  NOT NULL,
  suffix                         VARCHAR2(10)  NULL,
  gender                         VARCHAR2(20)  NULL,
  email                          VARCHAR2(150) NULL,
  phone                          VARCHAR2(30)  NULL,
  department_id                  NUMBER(10)    NULL,
  position_id                    NUMBER(10)    NULL,
  job_title                      VARCHAR2(100) NULL,
  employment_type                VARCHAR2(20)  DEFAULT 'Full-Time' NOT NULL,
  status                         VARCHAR2(20)  DEFAULT 'Probationary' NOT NULL,
  hire_date                      DATE          NULL,
  basic_salary                   NUMBER(12,2)  DEFAULT 0 NOT NULL,
  civil_status                   VARCHAR2(15)  NULL,
  date_of_birth                  DATE          NULL,
  place_of_birth                 VARCHAR2(150) NULL,
  nationality                    VARCHAR2(60)  DEFAULT 'Filipino',
  blood_type                     VARCHAR2(5)   NULL,
  highest_education              VARCHAR2(80)  NULL,
  address                        CLOB          NULL,
  sss_no                         VARCHAR2(30)  NULL,
  philhealth_no                  VARCHAR2(30)  NULL,
  pagibig_no                     VARCHAR2(30)  NULL,
  tin_no                         VARCHAR2(30)  NULL,
  bank_name                      VARCHAR2(80)  NULL,
  bank_account_number            VARCHAR2(40)  NULL,
  emergency_contact_name         VARCHAR2(100) NULL,
  emergency_contact_relationship VARCHAR2(60)  NULL,
  emergency_contact_phone        VARCHAR2(30)  NULL,
  created_at                     NUMBER(12)    DEFAULT 0 NOT NULL,
  updated_at                     NUMBER(12)    DEFAULT 0 NOT NULL,
  CONSTRAINT chk_emp_type   CHECK (employment_type IN ('Full-Time','Part-Time','Contractual','Intern')),
  CONSTRAINT chk_emp_status CHECK (status IN ('Regular','Probationary','Resigned','Terminated','On Leave')),
  CONSTRAINT chk_emp_civil  CHECK (civil_status IS NULL OR civil_status IN ('Single','Married','Widowed','Separated','Divorced')),
  CONSTRAINT fk_emp_user    FOREIGN KEY (user_id)       REFERENCES user_account(id) ON DELETE SET NULL,
  CONSTRAINT fk_emp_dept    FOREIGN KEY (department_id) REFERENCES department(id)   ON DELETE SET NULL,
  CONSTRAINT fk_emp_pos     FOREIGN KEY (position_id)   REFERENCES position(id)     ON DELETE SET NULL
);

COMMENT ON TABLE  employee            IS '201 file — master employee record';
COMMENT ON COLUMN employee.user_id    IS 'Linked login account; NULL for employees without portal access';
COMMENT ON COLUMN employee.tin_no     IS 'Bureau of Internal Revenue TIN';
COMMENT ON COLUMN employee.sss_no     IS 'Social Security System number';
COMMENT ON COLUMN employee.pagibig_no IS 'Pag-IBIG / HDMF number';

-- ============================================================
-- 5. SYSTEM_SETTING
-- ============================================================
CREATE TABLE system_setting (
  id         NUMBER(10)    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  key_name   VARCHAR2(80)  NOT NULL,
  value      CLOB          NULL,
  updated_at NUMBER(12)    DEFAULT 0 NOT NULL,
  CONSTRAINT uq_setting_key UNIQUE (key_name)
);

COMMENT ON TABLE system_setting IS 'Application-wide configuration key-value pairs';

-- ============================================================
-- 6. ATTENDANCE
-- ============================================================
CREATE TABLE attendance (
  id          NUMBER(10)   GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  employee_id NUMBER(10)   NOT NULL,
  att_date    DATE         NOT NULL,
  clock_in    VARCHAR2(8)  NULL,
  clock_out   VARCHAR2(8)  NULL,
  status      VARCHAR2(20) DEFAULT 'On Time' NOT NULL,
  total_hours NUMBER(5,2)  DEFAULT 0,
  notes       CLOB         NULL,
  created_at  NUMBER(12)   DEFAULT 0 NOT NULL,
  CONSTRAINT uq_att_emp_date  UNIQUE (employee_id, att_date),
  CONSTRAINT chk_att_status   CHECK (status IN ('On Time','Late','Absent','Half-Day')),
  CONSTRAINT fk_att_emp       FOREIGN KEY (employee_id) REFERENCES employee(id) ON DELETE CASCADE
);

COMMENT ON COLUMN attendance.clock_in  IS 'Stored as HH24:MI:SS string';
COMMENT ON COLUMN attendance.clock_out IS 'Stored as HH24:MI:SS string';

-- ============================================================
-- 7. SHIFT
-- ============================================================
CREATE TABLE shift (
  id          NUMBER(10)   GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  employee_id NUMBER(10)   NOT NULL,
  shift_date  DATE         NOT NULL,
  start_time  VARCHAR2(8)  NOT NULL,
  end_time    VARCHAR2(8)  NOT NULL,
  shift_name  VARCHAR2(80) NULL,
  type        VARCHAR2(10) DEFAULT 'Onsite' NOT NULL,
  status      VARCHAR2(15) DEFAULT 'Scheduled' NOT NULL,
  published   NUMBER(1)    DEFAULT 1 NOT NULL,
  created_at  NUMBER(12)   DEFAULT 0 NOT NULL,
  CONSTRAINT uq_shift_emp_date UNIQUE (employee_id, shift_date),
  CONSTRAINT chk_shift_type    CHECK (type   IN ('Onsite','Remote','Hybrid')),
  CONSTRAINT chk_shift_status  CHECK (status IN ('Scheduled','Completed','Missed','Cancelled')),
  CONSTRAINT fk_shift_emp      FOREIGN KEY (employee_id) REFERENCES employee(id) ON DELETE CASCADE
);

-- ============================================================
-- 8. LEAVE_REQUEST
-- ============================================================
CREATE TABLE leave_request (
  id          NUMBER(10)   GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  employee_id NUMBER(10)   NOT NULL,
  leave_type  VARCHAR2(60) NOT NULL,
  start_date  DATE         NOT NULL,
  end_date    DATE         NOT NULL,
  days        NUMBER(4,1)  DEFAULT 1 NOT NULL,
  reason      CLOB         NULL,
  status      VARCHAR2(10) DEFAULT 'Pending' NOT NULL,
  approved_by NUMBER(10)   NULL,
  created_at  NUMBER(12)   DEFAULT 0 NOT NULL,
  updated_at  NUMBER(12)   DEFAULT 0 NOT NULL,
  CONSTRAINT chk_leave_status CHECK (status IN ('Pending','Approved','Denied')),
  CONSTRAINT fk_leave_emp     FOREIGN KEY (employee_id) REFERENCES employee(id)     ON DELETE CASCADE,
  CONSTRAINT fk_leave_approver FOREIGN KEY (approved_by) REFERENCES user_account(id) ON DELETE SET NULL
);

COMMENT ON TABLE leave_request IS 'Employee leave applications with approval workflow';

-- ============================================================
-- 9. ANNOUNCEMENT
-- ============================================================
CREATE TABLE announcement (
  id              NUMBER(10)    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  title           VARCHAR2(200) NOT NULL,
  content         CLOB          NOT NULL,
  posted_by       NUMBER(10)    NULL,
  priority        VARCHAR2(10)  DEFAULT 'Medium' NOT NULL,
  pinned          NUMBER(1)     DEFAULT 0 NOT NULL,
  target_audience VARCHAR2(50)  DEFAULT 'All' NOT NULL,
  department_id   NUMBER(10)    NULL,
  created_at      NUMBER(12)    DEFAULT 0 NOT NULL,
  updated_at      NUMBER(12)    DEFAULT 0 NOT NULL,
  CONSTRAINT chk_ann_priority CHECK (priority IN ('Low','Medium','High','Urgent')),
  CONSTRAINT fk_ann_user      FOREIGN KEY (posted_by)     REFERENCES user_account(id) ON DELETE SET NULL,
  CONSTRAINT fk_ann_dept      FOREIGN KEY (department_id) REFERENCES department(id)   ON DELETE SET NULL
);

-- ============================================================
-- 10. HOLIDAY
-- ============================================================
CREATE TABLE holiday (
  id           NUMBER(10)    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  name         VARCHAR2(150) NOT NULL,
  holiday_date DATE          NOT NULL,
  type         VARCHAR2(30)  DEFAULT 'Regular' NOT NULL,
  is_recurring NUMBER(1)     DEFAULT 0 NOT NULL,
  created_at   NUMBER(12)    DEFAULT 0 NOT NULL,
  CONSTRAINT chk_holiday_type CHECK (type IN ('Regular','Special Non-Working','Special Working'))
);

-- ============================================================
-- 11. PAYROLL
-- ============================================================
CREATE TABLE payroll (
  id           NUMBER(10)    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  period_label VARCHAR2(100) NOT NULL,
  period_start DATE          NOT NULL,
  period_end   DATE          NOT NULL,
  pay_date     DATE          NOT NULL,
  status       VARCHAR2(15)  DEFAULT 'Draft' NOT NULL,
  created_by   NUMBER(10)    NULL,
  created_at   NUMBER(12)    DEFAULT 0 NOT NULL,
  updated_at   NUMBER(12)    DEFAULT 0 NOT NULL,
  CONSTRAINT chk_payroll_status CHECK (status IN ('Draft','Processed','Released')),
  CONSTRAINT fk_payroll_user    FOREIGN KEY (created_by) REFERENCES user_account(id) ON DELETE SET NULL
);

-- ============================================================
-- 12. PAYSLIP
-- ============================================================
CREATE TABLE payslip (
  id               NUMBER(10)    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  payroll_id       NUMBER(10)    NOT NULL,
  employee_id      NUMBER(10)    NOT NULL,
  basic_salary     NUMBER(12,2)  DEFAULT 0 NOT NULL,
  allowances       NUMBER(12,2)  DEFAULT 0 NOT NULL,
  overtime_pay     NUMBER(12,2)  DEFAULT 0 NOT NULL,
  gross_pay        NUMBER(12,2)  DEFAULT 0 NOT NULL,
  sss              NUMBER(10,2)  DEFAULT 0 NOT NULL,
  phil_health      NUMBER(10,2)  DEFAULT 0 NOT NULL,
  pag_ibig         NUMBER(10,2)  DEFAULT 0 NOT NULL,
  income_tax       NUMBER(10,2)  DEFAULT 0 NOT NULL,
  total_deductions NUMBER(12,2)  DEFAULT 0 NOT NULL,
  net_pay          NUMBER(12,2)  DEFAULT 0 NOT NULL,
  status           VARCHAR2(15)  DEFAULT 'Processed' NOT NULL,
  created_at       NUMBER(12)    DEFAULT 0 NOT NULL,
  CONSTRAINT uq_payslip         UNIQUE (payroll_id, employee_id),
  CONSTRAINT chk_payslip_status CHECK (status IN ('Processed','Released','Cancelled')),
  CONSTRAINT fk_payslip_payroll FOREIGN KEY (payroll_id)  REFERENCES payroll(id)  ON DELETE CASCADE,
  CONSTRAINT fk_payslip_emp     FOREIGN KEY (employee_id) REFERENCES employee(id) ON DELETE CASCADE
);

COMMENT ON TABLE payslip IS 'Per-employee payslip linked to a payroll period';

-- ============================================================
-- 13. FEEDBACK
-- ============================================================
CREATE TABLE feedback (
  id           NUMBER(10)    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  sender_id    NUMBER(10)    NULL,
  receiver_id  NUMBER(10)    NOT NULL,
  message      CLOB          NOT NULL,
  category     VARCHAR2(20)  DEFAULT 'positive' NOT NULL,
  is_anonymous NUMBER(1)     DEFAULT 0 NOT NULL,
  created_at   NUMBER(12)    DEFAULT 0 NOT NULL,
  CONSTRAINT chk_feedback_cat CHECK (category IN ('positive','constructive','suggestion')),
  CONSTRAINT fk_feedback_sender   FOREIGN KEY (sender_id)   REFERENCES employee(id) ON DELETE SET NULL,
  CONSTRAINT fk_feedback_receiver FOREIGN KEY (receiver_id) REFERENCES employee(id) ON DELETE CASCADE
);

-- ============================================================
-- 14. PERSONNEL_ACTION
-- ============================================================
CREATE TABLE personnel_action (
  id             NUMBER(10)    GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  employee_id    NUMBER(10)    NOT NULL,
  action_type    VARCHAR2(25)  NOT NULL,
  effective_date DATE          NOT NULL,
  old_value      VARCHAR2(200) NULL,
  new_value      VARCHAR2(200) NULL,
  remarks        CLOB          NULL,
  status         VARCHAR2(10)  DEFAULT 'Approved' NOT NULL,
  created_by     NUMBER(10)    NULL,
  created_at     NUMBER(12)    DEFAULT 0 NOT NULL,
  updated_at     NUMBER(12)    DEFAULT 0 NOT NULL,
  CONSTRAINT chk_pa_type   CHECK (action_type IN ('Promotion','Demotion','Salary Adjustment','Transfer','Resignation','Termination','Other')),
  CONSTRAINT chk_pa_status CHECK (status IN ('Pending','Approved','Rejected')),
  CONSTRAINT fk_pa_emp     FOREIGN KEY (employee_id) REFERENCES employee(id)     ON DELETE CASCADE,
  CONSTRAINT fk_pa_user    FOREIGN KEY (created_by)  REFERENCES user_account(id) ON DELETE SET NULL
);

COMMENT ON TABLE personnel_action IS 'HR actions: promotions, transfers, salary changes, etc.';

-- ============================================================
-- INDEXES (Part 1)
-- ============================================================
CREATE INDEX idx_emp_user_id    ON employee(user_id);
CREATE INDEX idx_emp_dept       ON employee(department_id);
CREATE INDEX idx_att_date       ON attendance(att_date);
CREATE INDEX idx_shift_date     ON shift(shift_date);
CREATE INDEX idx_leave_emp      ON leave_request(employee_id);
CREATE INDEX idx_leave_status   ON leave_request(status);
CREATE INDEX idx_payslip_emp    ON payslip(employee_id);
CREATE INDEX idx_pa_emp         ON personnel_action(employee_id);

-- End of Part 1 — run hrms_db_oracle_part2.sql next
