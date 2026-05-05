SET FOREIGN_KEY_CHECKS = 0;

-- ══════════════════════════════════════════
-- CLEAR ALL DATA (preserve tables)
-- ══════════════════════════════════════════
TRUNCATE TABLE audit_log;
TRUNCATE TABLE attendance;
TRUNCATE TABLE employee_benefit;
TRUNCATE TABLE benefit;
TRUNCATE TABLE candidate;
TRUNCATE TABLE job_posting;
TRUNCATE TABLE employee_doc_checklist;
TRUNCATE TABLE employee_document;
TRUNCATE TABLE feedback;
TRUNCATE TABLE holiday;
TRUNCATE TABLE leave_balance;
TRUNCATE TABLE leave_request;
TRUNCATE TABLE lms_enrollment;
TRUNCATE TABLE lms_module;
TRUNCATE TABLE onboarding_task;
TRUNCATE TABLE personnel_action;
TRUNCATE TABLE project_member;
TRUNCATE TABLE project_task;
TRUNCATE TABLE project;
TRUNCATE TABLE shift;
TRUNCATE TABLE system_notification;
TRUNCATE TABLE timelog_dispute;
TRUNCATE TABLE work_rule;
TRUNCATE TABLE announcement;
TRUNCATE TABLE position;
TRUNCATE TABLE employee;
TRUNCATE TABLE department;
TRUNCATE TABLE `user`;

SET FOREIGN_KEY_CHECKS = 1;

-- ══════════════════════════════════════════
-- COMPANY SETTINGS
-- ══════════════════════════════════════════
UPDATE system_setting SET value = 'Staffora IT Solutions' WHERE `key` = 'company_name';
UPDATE system_setting SET value = 'Makati City, Metro Manila' WHERE `key` = 'company_address';
UPDATE system_setting SET value = 'hr@stafforaitsoln.com' WHERE `key` = 'company_email';

-- ══════════════════════════════════════════
-- DEPARTMENTS (2)
-- ══════════════════════════════════════════
INSERT INTO department (id, name, description, location, budget, created_at) VALUES
(1, 'Software Development',
 'Responsible for designing, developing, testing, and maintaining software products and systems used by the organization and its clients.',
 'Makati City, Metro Manila', 2500000.00, UNIX_TIMESTAMP('2024-01-01')),
(2, 'IT Operations & Support',
 'Manages IT infrastructure, network systems, server environments, and provides technical helpdesk support across the organization.',
 'Makati City, Metro Manila', 1800000.00, UNIX_TIMESTAMP('2024-01-01'));

-- ══════════════════════════════════════════
-- POSITIONS
-- ══════════════════════════════════════════
INSERT INTO position (id, title, code, department_id, level, salary_min, salary_max, description, created_at) VALUES
(1, 'Software Developer',    'SWD',  1, 2, 38000, 55000, 'Develops and maintains software applications and systems.', UNIX_TIMESTAMP('2024-01-01')),
(2, 'Frontend Developer',    'FED',  1, 2, 36000, 50000, 'Designs and implements user-facing features and interfaces.', UNIX_TIMESTAMP('2024-01-01')),
(3, 'QA Engineer',           'QAE',  1, 2, 34000, 48000, 'Ensures software quality through systematic testing and QA processes.', UNIX_TIMESTAMP('2024-01-01')),
(4, 'IT Support Specialist', 'ITS',  2, 2, 30000, 42000, 'Provides technical support and troubleshooting for end users.', UNIX_TIMESTAMP('2024-01-01')),
(5, 'Systems Administrator', 'SYSAD',2, 3, 40000, 58000, 'Manages and maintains servers, networks, and IT infrastructure.', UNIX_TIMESTAMP('2024-01-01'));

-- ══════════════════════════════════════════
-- WORK RULE
-- ══════════════════════════════════════════
INSERT INTO work_rule (id, name, schedule_type, start_time, end_time, working_days, break_minutes, is_default, created_at) VALUES
(1, 'Standard Day Shift', 'Basic', '09:00:00', '18:00:00', '1,2,3,4,5', 60, 1, UNIX_TIMESTAMP('2024-01-01'));

-- ══════════════════════════════════════════
-- USERS
-- ══════════════════════════════════════════
INSERT INTO `user` (id, username, email, password, role, status, department_id, created_at) VALUES
(1, 'joaquin.celis',      'joaquin.celis@stafforaitsoln.com',      MD5('Password123'), 'Super Admin', 'Active', NULL, UNIX_TIMESTAMP('2024-01-15')),
(2, 'remus.marasigan',    'remus.marasigan@stafforaitsoln.com',    MD5('Password123'), 'Manager',     'Active', 1,    UNIX_TIMESTAMP('2024-02-01')),
(3, 'ralph.pinpin',       'ralph.pinpin@stafforaitsoln.com',       MD5('Password123'), 'Manager',     'Active', 2,    UNIX_TIMESTAMP('2024-02-01')),
(4, 'mon.lapid',          'mon.lapid@stafforaitsoln.com',          MD5('Password123'), 'Employee',    'Active', 1,    UNIX_TIMESTAMP('2024-03-01')),
(5, 'joshua.uchiyama',    'joshua.uchiyama@stafforaitsoln.com',    MD5('Password123'), 'Employee',    'Active', 2,    UNIX_TIMESTAMP('2024-03-15')),
(6, 'kyra.santos',        'kyra.santos@stafforaitsoln.com',        MD5('Password123'), 'Employee',    'Active', 1,    UNIX_TIMESTAMP('2024-04-01')),
(7, 'bea.uson',           'bea.uson@stafforaitsoln.com',           MD5('Password123'), 'Employee',    'Active', 1,    UNIX_TIMESTAMP('2024-04-01')),
(8, 'angela.tolentino',   'angela.tolentino@stafforaitsoln.com',   MD5('Password123'), 'Employee',    'Active', 2,    UNIX_TIMESTAMP('2024-06-01'));

-- ══════════════════════════════════════════
-- EMPLOYEES
-- ══════════════════════════════════════════
INSERT INTO employee (id,user_id,employee_no,first_name,middle_name,last_name,gender,email,phone,
  department_id,position_id,job_title,employment_type,status,hire_date,basic_salary,
  civil_status,date_of_birth,nationality,blood_type,highest_education,address,
  sss_no,philhealth_no,pagibig_no,tin_no,bank_name,bank_account_number,
  emergency_contact_name,emergency_contact_relationship,emergency_contact_phone,
  work_rule_id,created_at,updated_at) VALUES

-- 1. Joaquin Chaster A. Celis — HR Manager (Super Admin, no dept)
(1,1,'EMP-2024-0001','Joaquin','Chaster A.','Celis','Male',
 'joaquin.celis@stafforaitsoln.com','09171234501',
 NULL,NULL,'HR Manager','Full-Time','Regular','2024-01-15',75000.00,
 'Single','1990-03-22','Filipino','O+','Bachelor\'s Degree',
 '123 Ayala Ave, Legazpi Village, Makati City',
 '34-5678901-2','12-345678901-2','1234-5678-9012','123-456-789-000',
 'BDO','1234567890001',
 'Maria Celis','Mother','09179999001',1,
 UNIX_TIMESTAMP('2024-01-15'),UNIX_TIMESTAMP('2024-01-15')),

-- 2. Remus Aimiel Marasigan — Manager, Dept 1 (Software Dev)
(2,2,'EMP-2024-0002','Remus','Aimiel','Marasigan','Male',
 'remus.marasigan@stafforaitsoln.com','09171234502',
 1,1,'Software Development Manager','Full-Time','Regular','2024-02-01',65000.00,
 'Married','1988-07-10','Filipino','B+','Master\'s Degree',
 '456 Valero St, Salcedo Village, Makati City',
 '34-5678902-3','12-345678902-3','1234-5678-9013','123-456-789-001',
 'BPI','2345678900001',
 'Anna Marasigan','Spouse','09179999002',1,
 UNIX_TIMESTAMP('2024-02-01'),UNIX_TIMESTAMP('2024-02-01')),

-- 3. Ralph Luiz Pinpin — Manager, Dept 2 (IT Ops)
(3,3,'EMP-2024-0003','Ralph','Luiz','Pinpin','Male',
 'ralph.pinpin@stafforaitsoln.com','09171234503',
 2,5,'IT Operations Manager','Full-Time','Regular','2024-02-01',63000.00,
 'Single','1989-11-15','Filipino','A+','Bachelor\'s Degree',
 '789 Buendia Ave, Bangkal, Makati City',
 '34-5678903-4','12-345678903-4','1234-5678-9014','123-456-789-002',
 'Metrobank','3456789000001',
 'Jose Pinpin','Father','09179999003',1,
 UNIX_TIMESTAMP('2024-02-01'),UNIX_TIMESTAMP('2024-02-01')),

-- 4. Mon Karlo Don Lapid — Employee, Dept 1
(4,4,'EMP-2024-0004','Mon Karlo','Don','Lapid','Male',
 'mon.lapid@stafforaitsoln.com','09171234504',
 1,1,'Software Developer','Full-Time','Regular','2024-03-01',45000.00,
 'Single','1995-05-20','Filipino','O-','Bachelor\'s Degree',
 '321 Pasong Tamo Ext, Pio Del Pilar, Makati City',
 '34-5678904-5','12-345678904-5','1234-5678-9015','123-456-789-003',
 'BDO','4567890000001',
 'Linda Lapid','Mother','09179999004',1,
 UNIX_TIMESTAMP('2024-03-01'),UNIX_TIMESTAMP('2024-03-01')),

-- 5. Joshua Uchiyama — Employee, Dept 2
(5,5,'EMP-2024-0005','Joshua',NULL,'Uchiyama','Male',
 'joshua.uchiyama@stafforaitsoln.com','09171234505',
 2,4,'IT Support Specialist','Full-Time','Regular','2024-03-15',38000.00,
 'Single','1997-02-14','Filipino','AB+','Bachelor\'s Degree',
 '654 Gil Puyat Ave, Palanan, Makati City',
 '34-5678905-6','12-345678905-6','1234-5678-9016','123-456-789-004',
 'BPI','5678900000001',
 'Yuki Uchiyama','Father','09179999005',1,
 UNIX_TIMESTAMP('2024-03-15'),UNIX_TIMESTAMP('2024-03-15')),

-- 6. Kyra Lois Santos — Employee, Dept 1
(6,6,'EMP-2024-0006','Kyra','Lois','Santos','Female',
 'kyra.santos@stafforaitsoln.com','09171234506',
 1,2,'Frontend Developer','Full-Time','Regular','2024-04-01',42000.00,
 'Single','1998-09-30','Filipino','A-','Bachelor\'s Degree',
 '147 Dela Rosa St, Legaspi Village, Makati City',
 '34-5678906-7','12-345678906-7','1234-5678-9017','123-456-789-005',
 'UnionBank','6789000000001',
 'Carmen Santos','Mother','09179999006',1,
 UNIX_TIMESTAMP('2024-04-01'),UNIX_TIMESTAMP('2024-04-01')),

-- 7. Bea Louise Uson — Employee, Dept 1
(7,7,'EMP-2024-0007','Bea','Louise','Uson','Female',
 'bea.uson@stafforaitsoln.com','09171234507',
 1,3,'QA Engineer','Full-Time','Regular','2024-04-01',40000.00,
 'Single','1999-12-05','Filipino','B-','Bachelor\'s Degree',
 '258 Legaspi St, Legaspi Village, Makati City',
 '34-5678907-8','12-345678907-8','1234-5678-9018','123-456-789-006',
 'BDO','7890000000001',
 'Robert Uson','Father','09179999007',1,
 UNIX_TIMESTAMP('2024-04-01'),UNIX_TIMESTAMP('2024-04-01')),

-- 8. Angela Tolentino — Employee, Dept 2
(8,8,'EMP-2024-0008','Angela',NULL,'Tolentino','Female',
 'angela.tolentino@stafforaitsoln.com','09171234508',
 2,5,'Systems Administrator','Full-Time','Regular','2024-06-01',43000.00,
 'Single','1996-08-17','Filipino','O+','Bachelor\'s Degree',
 '369 Chino Roces Ave, Sta. Cruz, Makati City',
 '34-5678908-9','12-345678908-9','1234-5678-9019','123-456-789-007',
 'Metrobank','8900000000001',
 'Grace Tolentino','Mother','09179999008',1,
 UNIX_TIMESTAMP('2024-06-01'),UNIX_TIMESTAMP('2024-06-01'));

-- ══════════════════════════════════════════
-- DOCUMENT CHECKLISTS (all 8 employees)
-- ══════════════════════════════════════════
INSERT INTO employee_doc_checklist (employee_id, document_type, status, created_at, updated_at)
SELECT e.id, d.doc, 
  CASE WHEN e.hire_date < '2024-06-01' THEN 'Approved' ELSE 'Submitted' END,
  UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM employee e
CROSS JOIN (
  SELECT 'Employment Contract' AS doc UNION ALL
  SELECT 'SSS E1 Form / SS Card' UNION ALL
  SELECT 'PhilHealth MDR (Member Data Record)' UNION ALL
  SELECT 'Pag-IBIG MDF (Membership Data Form)' UNION ALL
  SELECT 'BIR Form 2316 / TIN Card' UNION ALL
  SELECT 'NBI Clearance' UNION ALL
  SELECT 'Birth Certificate (PSA-authenticated)' UNION ALL
  SELECT 'Medical Certificate' UNION ALL
  SELECT 'Diploma / Transcript of Records' UNION ALL
  SELECT '2×2 ID Photo (white background)'
) d;

-- ══════════════════════════════════════════
-- LEAVE BALANCES (2026)
-- ══════════════════════════════════════════
INSERT INTO leave_balance (employee_id, year, sil_accrued, sil_used, sil_carried_over)
VALUES
(1,2026,5.00,1.00,0.00),(2,2026,5.00,0.00,0.00),(3,2026,5.00,1.00,0.00),
(4,2026,5.00,0.00,0.00),(5,2026,5.00,1.00,0.00),(6,2026,5.00,0.00,0.00),
(7,2026,5.00,0.00,0.00),(8,2026,5.00,0.00,0.00);

-- ══════════════════════════════════════════
-- HOLIDAY: May 1 Labor Day
-- ══════════════════════════════════════════
INSERT INTO holiday (name, date, type, created_at) VALUES
('Labor Day', '2026-05-01', 'Regular', UNIX_TIMESTAMP('2026-01-01'));

-- ══════════════════════════════════════════
-- SHIFTS: Apr 28–May 2, 2026 (Mon–Fri, skip May 1 holiday)
-- All employees, 09:00–18:00, Standard Day Shift
-- ══════════════════════════════════════════
INSERT INTO shift (employee_id,date,start_time,end_time,type,shift_name,work_rule_id,break_start_time,break_end_time,status,published,created_at)
SELECT e.id,d.dt,'09:00:00','18:00:00','Onsite','Standard Day Shift',1,'12:00:00','13:00:00','Completed',1,UNIX_TIMESTAMP()
FROM employee e
CROSS JOIN (
  SELECT '2026-04-28' AS dt UNION ALL SELECT '2026-04-29' UNION ALL
  SELECT '2026-04-30' UNION ALL SELECT '2026-05-02'
) d
WHERE e.id != 1; -- HR Manager has no fixed dept shift

-- May 1 Labor Day — mark as Cancelled for all
INSERT INTO shift (employee_id,date,start_time,end_time,type,shift_name,work_rule_id,status,published,created_at)
SELECT e.id,'2026-05-01','09:00:00','18:00:00','Onsite','Labor Day Holiday',1,'Cancelled',1,UNIX_TIMESTAMP()
FROM employee e WHERE e.id != 1;

-- ══════════════════════════════════════════
-- ATTENDANCE: Apr 28–May 2, 2026
-- Realistic clock-in/out times, skip May 1 (holiday)
-- ══════════════════════════════════════════
-- Apr 28 (Mon)
INSERT INTO attendance (employee_id,date,clock_in,clock_out,status,total_hours,created_at) VALUES
(1,'2026-04-28','08:52:00','18:05:00','On Time',8.22,UNIX_TIMESTAMP('2026-04-28')),
(2,'2026-04-28','08:55:00','18:10:00','On Time',8.25,UNIX_TIMESTAMP('2026-04-28')),
(3,'2026-04-28','09:03:00','18:00:00','On Time',8.00,UNIX_TIMESTAMP('2026-04-28')),
(4,'2026-04-28','08:48:00','18:02:00','On Time',8.23,UNIX_TIMESTAMP('2026-04-28')),
(5,'2026-04-28','09:12:00','18:15:00','Late',   8.05,UNIX_TIMESTAMP('2026-04-28')),
(6,'2026-04-28','08:59:00','18:00:00','On Time',8.02,UNIX_TIMESTAMP('2026-04-28')),
(7,'2026-04-28','09:01:00','18:05:00','On Time',8.07,UNIX_TIMESTAMP('2026-04-28')),
(8,'2026-04-28','08:45:00','18:00:00','On Time',8.25,UNIX_TIMESTAMP('2026-04-28'));

-- Apr 29 (Tue)
INSERT INTO attendance (employee_id,date,clock_in,clock_out,status,total_hours,created_at) VALUES
(1,'2026-04-29','08:58:00','18:00:00','On Time',8.03,UNIX_TIMESTAMP('2026-04-29')),
(2,'2026-04-29','09:00:00','18:30:00','On Time',8.50,UNIX_TIMESTAMP('2026-04-29')),
(3,'2026-04-29','09:05:00','18:10:00','On Time',8.08,UNIX_TIMESTAMP('2026-04-29')),
(4,'2026-04-29','09:18:00','18:20:00','Late',   8.03,UNIX_TIMESTAMP('2026-04-29')),
(5,'2026-04-29','08:50:00','18:00:00','On Time',8.17,UNIX_TIMESTAMP('2026-04-29')),
(6,'2026-04-29','09:02:00','18:05:00','On Time',8.05,UNIX_TIMESTAMP('2026-04-29')),
(7,'2026-04-29','08:55:00','17:45:00','On Time',7.83,UNIX_TIMESTAMP('2026-04-29')),
(8,'2026-04-29','09:00:00','18:00:00','On Time',8.00,UNIX_TIMESTAMP('2026-04-29'));

-- Apr 30 (Wed)
INSERT INTO attendance (employee_id,date,clock_in,clock_out,status,total_hours,created_at) VALUES
(1,'2026-04-30','08:50:00','18:15:00','On Time',8.42,UNIX_TIMESTAMP('2026-04-30')),
(2,'2026-04-30','09:00:00','18:00:00','On Time',8.00,UNIX_TIMESTAMP('2026-04-30')),
(3,'2026-04-30','08:47:00','18:00:00','On Time',8.22,UNIX_TIMESTAMP('2026-04-30')),
(4,'2026-04-30','09:00:00','18:00:00','On Time',8.00,UNIX_TIMESTAMP('2026-04-30')),
(5,'2026-04-30','09:00:00','18:05:00','On Time',8.08,UNIX_TIMESTAMP('2026-04-30')),
-- Kyra filed for leave Apr 30 (approved)
(7,'2026-04-30','09:05:00','18:00:00','On Time',7.92,UNIX_TIMESTAMP('2026-04-30')),
(8,'2026-04-30','09:20:00','18:10:00','Late',   7.83,UNIX_TIMESTAMP('2026-04-30'));

-- May 1 = Labor Day holiday — no attendance records

-- May 2 (Fri)
INSERT INTO attendance (employee_id,date,clock_in,clock_out,status,total_hours,created_at) VALUES
(1,'2026-05-02','08:55:00','17:58:00','On Time',8.05,UNIX_TIMESTAMP('2026-05-02')),
(2,'2026-05-02','09:00:00','19:00:00','On Time',9.00,UNIX_TIMESTAMP('2026-05-02')),
(3,'2026-05-02','08:52:00','18:05:00','On Time',8.22,UNIX_TIMESTAMP('2026-05-02')),
(4,'2026-05-02','09:10:00','18:00:00','Late',   7.83,UNIX_TIMESTAMP('2026-05-02')),
(5,'2026-05-02','09:00:00','18:00:00','On Time',8.00,UNIX_TIMESTAMP('2026-05-02')),
(6,'2026-05-02','08:58:00','18:10:00','On Time',8.20,UNIX_TIMESTAMP('2026-05-02')),
(7,'2026-05-02','09:03:00','18:00:00','On Time',7.95,UNIX_TIMESTAMP('2026-05-02')),
(8,'2026-05-02','08:45:00','18:00:00','On Time',8.25,UNIX_TIMESTAMP('2026-05-02'));

-- ══════════════════════════════════════════
-- LEAVE REQUESTS
-- ══════════════════════════════════════════
INSERT INTO leave_request (employee_id,leave_type,start_date,end_date,days,reason,status,approved_by,created_at,updated_at) VALUES
-- Kyra: Apr 30 SIL approved by Remus
(6,'SIL','2026-04-30','2026-04-30',1.0,'Personal errand — DMV appointment',
 'Approved',2,UNIX_TIMESTAMP('2026-04-25'),UNIX_TIMESTAMP('2026-04-26')),
-- Joshua: May 1 already holiday, filed VL on Apr 29 (pending)
(5,'SIL','2026-04-29','2026-04-29',1.0,'Medical check-up',
 'Pending',NULL,UNIX_TIMESTAMP('2026-04-28'),UNIX_TIMESTAMP('2026-04-28')),
-- Angela: timelog correction leave (denied)
(8,'SIL','2026-04-28','2026-04-28',1.0,'Family matter',
 'Denied',3,UNIX_TIMESTAMP('2026-04-27'),UNIX_TIMESTAMP('2026-04-27'));

-- ══════════════════════════════════════════
-- ANNOUNCEMENTS
-- ══════════════════════════════════════════
INSERT INTO announcement (title,content,posted_by,priority,pinned,target_audience,is_company_wide,department_id,created_at,updated_at) VALUES
('🎉 Welcome to Staffora IT Solutions!',
 'We are excited to officially launch our HR Management System — Staffora. This platform will streamline attendance tracking, leave management, onboarding, and more. Please complete your 201 file and document checklist at your earliest convenience. For questions, reach out to HR.',
 1,'High',1,'All',1,NULL,UNIX_TIMESTAMP('2026-04-28'),UNIX_TIMESTAMP('2026-04-28')),

('📅 Labor Day Holiday — May 1, 2026',
 'Please be advised that May 1, 2026 (Thursday) is a Regular Holiday in observance of Labor Day. The office will be closed. Enjoy your long weekend! Work resumes on Friday, May 2, 2026.',
 1,'High',1,'All',1,NULL,UNIX_TIMESTAMP('2026-04-29'),UNIX_TIMESTAMP('2026-04-29')),

('🔧 Software Dev Team — Sprint Review this Friday',
 'Reminder to all Software Development team members: Sprint 12 review and retrospective is scheduled for May 2, 2026 at 3:00 PM in the main conference room. Please prepare your deliverables and progress updates.',
 2,'Medium',0,'Department',0,1,UNIX_TIMESTAMP('2026-04-30'),UNIX_TIMESTAMP('2026-04-30')),

('🖥️ Scheduled Network Maintenance — Apr 30, 9 PM',
 'The IT Operations team will be performing scheduled maintenance on the company network and servers on April 30 starting at 9:00 PM. Expect brief service interruptions. All systems should be fully restored by midnight. Please save all work before leaving.',
 3,'Urgent',0,'All',1,NULL,UNIX_TIMESTAMP('2026-04-29'),UNIX_TIMESTAMP('2026-04-29'));

-- ══════════════════════════════════════════
-- TIMELOG DISPUTE (sample)
-- ══════════════════════════════════════════
INSERT INTO timelog_dispute
  (employee_id,dispute_date,original_clock_in,original_clock_out,corrected_clock_in,corrected_clock_out,break_minutes,dispute_type,reason,status,created_at)
VALUES
(4,'2026-04-29','09:18:00','18:20:00','09:05:00','18:20:00',60,'Timelog Correction',
 'I was actually on time but the biometric scanner was slow to register. I have a colleague who can attest.',
 'Pending',UNIX_TIMESTAMP('2026-04-30'));

-- ══════════════════════════════════════════
-- FEEDBACK (sample)
-- ══════════════════════════════════════════
INSERT INTO feedback (sender_id, receiver_id, message, category, is_anonymous, created_at)
SELECT u1.id, u2.id,
  'Great job on the sprint demo last week! The new dashboard feature looks really clean and intuitive.',
  'positive', 0, UNIX_TIMESTAMP('2026-04-30')
FROM `user` u1, `user` u2
WHERE u1.username='mon.lapid' AND u2.username='remus.marasigan'
LIMIT 1;

SELECT 'Seed complete!' AS status;
