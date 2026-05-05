-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: hrms_db
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `announcement`
--

DROP TABLE IF EXISTS `announcement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `pinned` tinyint(1) NOT NULL DEFAULT 0,
  `target_audience` varchar(50) NOT NULL DEFAULT 'All',
  `is_company_wide` tinyint(1) NOT NULL DEFAULT 1,
  `department_id` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `posted_by` (`posted_by`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `announcement_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `announcement_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement`
--

LOCK TABLES `announcement` WRITE;
/*!40000 ALTER TABLE `announcement` DISABLE KEYS */;
INSERT INTO `announcement` VALUES (1,'🎉 Welcome to Staffora IT Solutions!','We are excited to officially launch our HR Management System — Staffora. This platform will streamline attendance tracking, leave management, onboarding, and more. Please complete your 201 file and document checklist at your earliest convenience. For questions, reach out to HR.',1,'High',1,'All',1,NULL,1777305600,1777305600),(2,'📅 Labor Day Holiday — May 1, 2026','Please be advised that May 1, 2026 (Thursday) is a Regular Holiday in observance of Labor Day. The office will be closed. Enjoy your long weekend! Work resumes on Friday, May 2, 2026.',1,'High',1,'All',1,NULL,1777392000,1777392000),(3,'🔧 Software Dev Team — Sprint Review this Friday','Reminder to all Software Development team members: Sprint 12 review and retrospective is scheduled for May 2, 2026 at 3:00 PM in the main conference room. Please prepare your deliverables and progress updates.',2,'Medium',0,'Department',0,1,1777478400,1777478400),(4,'🖥️ Scheduled Network Maintenance — Apr 30, 9 PM','The IT Operations team will be performing scheduled maintenance on the company network and servers on April 30 starting at 9:00 PM. Expect brief service interruptions. All systems should be fully restored by midnight. Please save all work before leaving.',3,'Urgent',0,'All',1,NULL,1777392000,1777392000);
/*!40000 ALTER TABLE `announcement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `clock_out` time DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'On Time',
  `total_hours` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_emp_id` (`employee_id`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (1,1,'2026-04-28','08:52:00','18:05:00','On Time',8.22,NULL,1777305600,NULL),(2,2,'2026-04-28','08:55:00','18:10:00','On Time',8.25,NULL,1777305600,NULL),(3,3,'2026-04-28','09:03:00','18:00:00','On Time',8.00,NULL,1777305600,NULL),(4,4,'2026-04-28','08:48:00','18:02:00','On Time',8.23,NULL,1777305600,NULL),(5,5,'2026-04-28','09:12:00','18:15:00','Late',8.05,NULL,1777305600,NULL),(6,6,'2026-04-28','08:59:00','18:00:00','On Time',8.02,NULL,1777305600,NULL),(7,7,'2026-04-28','09:01:00','18:05:00','On Time',8.07,NULL,1777305600,NULL),(8,8,'2026-04-28','08:45:00','18:00:00','On Time',8.25,NULL,1777305600,NULL),(9,1,'2026-04-29','08:58:00','18:00:00','On Time',8.03,NULL,1777392000,NULL),(10,2,'2026-04-29','09:00:00','18:30:00','On Time',8.50,NULL,1777392000,NULL),(11,3,'2026-04-29','09:05:00','18:10:00','On Time',8.08,NULL,1777392000,NULL),(12,4,'2026-04-29','09:18:00','18:20:00','Late',8.03,NULL,1777392000,NULL),(13,5,'2026-04-29','08:50:00','18:00:00','On Time',8.17,NULL,1777392000,NULL),(14,6,'2026-04-29','09:02:00','18:05:00','On Time',8.05,NULL,1777392000,NULL),(15,7,'2026-04-29','08:55:00','17:45:00','On Time',7.83,NULL,1777392000,NULL),(16,8,'2026-04-29','09:00:00','18:00:00','On Time',8.00,NULL,1777392000,NULL),(17,1,'2026-04-30','08:50:00','18:15:00','On Time',8.42,NULL,1777478400,NULL),(18,2,'2026-04-30','09:00:00','18:00:00','On Time',8.00,NULL,1777478400,NULL),(19,3,'2026-04-30','08:47:00','18:00:00','On Time',8.22,NULL,1777478400,NULL),(20,4,'2026-04-30','09:00:00','18:00:00','On Time',8.00,NULL,1777478400,NULL),(21,5,'2026-04-30','09:00:00','18:05:00','On Time',8.08,NULL,1777478400,NULL),(22,7,'2026-04-30','09:05:00','18:00:00','On Time',7.92,NULL,1777478400,NULL),(23,8,'2026-04-30','09:20:00','18:10:00','Late',7.83,NULL,1777478400,NULL),(24,1,'2026-05-02','08:55:00','17:58:00','On Time',8.05,NULL,1777651200,NULL),(25,2,'2026-05-02','09:00:00','19:00:00','On Time',9.00,NULL,1777651200,NULL),(26,3,'2026-05-02','08:52:00','18:05:00','On Time',8.22,NULL,1777651200,NULL),(27,4,'2026-05-02','09:10:00','18:00:00','Late',7.83,NULL,1777651200,NULL),(28,5,'2026-05-02','09:00:00','18:00:00','On Time',8.00,NULL,1777651200,NULL),(29,6,'2026-05-02','08:58:00','18:10:00','On Time',8.20,NULL,1777651200,NULL),(30,7,'2026-05-02','09:03:00','18:00:00','On Time',7.95,NULL,1777651200,NULL),(31,8,'2026-05-02','08:45:00','18:00:00','On Time',8.25,NULL,1777651200,NULL);
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(80) NOT NULL,
  `model_id` int(11) DEFAULT NULL,
  `data` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `benefit`
--

DROP TABLE IF EXISTS `benefit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `benefit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'Other',
  `value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `benefit`
--

LOCK TABLES `benefit` WRITE;
/*!40000 ALTER TABLE `benefit` DISABLE KEYS */;
/*!40000 ALTER TABLE `benefit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `candidate`
--

DROP TABLE IF EXISTS `candidate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `candidate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `stage` enum('Applied','Screening','Interview','Offer','Hired','Rejected') NOT NULL DEFAULT 'Applied',
  `expected_salary` decimal(12,2) DEFAULT NULL,
  `applied_at` date DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  CONSTRAINT `candidate_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_posting` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidate`
--

LOCK TABLES `candidate` WRITE;
/*!40000 ALTER TABLE `candidate` DISABLE KEYS */;
/*!40000 ALTER TABLE `candidate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department`
--

DROP TABLE IF EXISTS `department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT 0.00,
  `created_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department`
--

LOCK TABLES `department` WRITE;
/*!40000 ALTER TABLE `department` DISABLE KEYS */;
INSERT INTO `department` VALUES (1,'Software Development','Responsible for designing, developing, testing, and maintaining software products and systems used by the organization and its clients.','Makati City, Metro Manila',2500000.00,1704038400),(2,'IT Operations & Support','Manages IT infrastructure, network systems, server environments, and provides technical helpdesk support across the organization.','Makati City, Metro Manila',1800000.00,1704038400);
/*!40000 ALTER TABLE `department` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee`
--

DROP TABLE IF EXISTS `employee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `employee_no` varchar(30) DEFAULT NULL,
  `first_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `position_id` int(11) DEFAULT NULL,
  `work_rule_id` int(11) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `employment_type` enum('Full-Time','Part-Time','Contractual','Intern') NOT NULL DEFAULT 'Full-Time',
  `status` enum('Regular','Probationary','Resigned','Terminated','On Leave') NOT NULL DEFAULT 'Probationary',
  `hire_date` date DEFAULT NULL,
  `date_regularized` date DEFAULT NULL,
  `basic_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `civil_status` enum('Single','Married','Widowed','Separated','Divorced') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `place_of_birth` varchar(150) DEFAULT NULL,
  `nationality` varchar(60) DEFAULT 'Filipino',
  `solo_parent_id` varchar(50) DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `highest_education` varchar(80) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `sss_no` varchar(30) DEFAULT NULL,
  `philhealth_no` varchar(30) DEFAULT NULL,
  `pagibig_no` varchar(30) DEFAULT NULL,
  `tin_no` varchar(30) DEFAULT NULL,
  `bank_name` varchar(80) DEFAULT NULL,
  `bank_account_number` varchar(40) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_relationship` varchar(60) DEFAULT NULL,
  `emergency_contact_phone` varchar(30) DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `department_id` (`department_id`),
  KEY `position_id` (`position_id`),
  CONSTRAINT `employee_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_ibfk_3` FOREIGN KEY (`position_id`) REFERENCES `position` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee`
--

LOCK TABLES `employee` WRITE;
/*!40000 ALTER TABLE `employee` DISABLE KEYS */;
INSERT INTO `employee` VALUES (1,1,'EMP-2024-0001','Joaquin','Chaster A.','Celis',NULL,'Male','joaquin.celis@stafforaitsoln.com','09171234501',NULL,NULL,1,'HR Manager','Full-Time','Regular','2024-01-15',NULL,75000.00,'Single','1990-03-22',NULL,'Filipino',NULL,'O+','Bachelor\'s Degree','123 Ayala Ave, Legazpi Village, Makati City','34-5678901-2','12-345678901-2','1234-5678-9012','123-456-789-000','BDO','1234567890001','Maria Celis','Mother','09179999001',1705248000,1705248000),(2,2,'EMP-2024-0002','Remus','Aimiel','Marasigan',NULL,'Male','remus.marasigan@stafforaitsoln.com','09171234502',1,1,1,'Software Development Manager','Full-Time','Regular','2024-02-01',NULL,65000.00,'Married','1988-07-10',NULL,'Filipino',NULL,'B+','Master\'s Degree','456 Valero St, Salcedo Village, Makati City','34-5678902-3','12-345678902-3','1234-5678-9013','123-456-789-001','BPI','2345678900001','Anna Marasigan','Spouse','09179999002',1706716800,1706716800),(3,3,'EMP-2024-0003','Ralph','Luiz','Pinpin',NULL,'Male','ralph.pinpin@stafforaitsoln.com','09171234503',2,5,1,'IT Operations Manager','Full-Time','Regular','2024-02-01',NULL,63000.00,'Single','1989-11-15',NULL,'Filipino',NULL,'A+','Bachelor\'s Degree','789 Buendia Ave, Bangkal, Makati City','34-5678903-4','12-345678903-4','1234-5678-9014','123-456-789-002','Metrobank','3456789000001','Jose Pinpin','Father','09179999003',1706716800,1706716800),(4,4,'EMP-2024-0004','Mon Karlo','Don','Lapid',NULL,'Male','mon.lapid@stafforaitsoln.com','09171234504',1,1,1,'Software Developer','Full-Time','Regular','2024-03-01',NULL,45000.00,'Single','1995-05-20',NULL,'Filipino',NULL,'O-','Bachelor\'s Degree','321 Pasong Tamo Ext, Pio Del Pilar, Makati City','34-5678904-5','12-345678904-5','1234-5678-9015','123-456-789-003','BDO','4567890000001','Linda Lapid','Mother','09179999004',1709222400,1709222400),(5,5,'EMP-2024-0005','Joshua',NULL,'Uchiyama',NULL,'Male','joshua.uchiyama@stafforaitsoln.com','09171234505',2,4,1,'IT Support Specialist','Full-Time','Regular','2024-03-15',NULL,38000.00,'Single','1997-02-14',NULL,'Filipino',NULL,'AB+','Bachelor\'s Degree','654 Gil Puyat Ave, Palanan, Makati City','34-5678905-6','12-345678905-6','1234-5678-9016','123-456-789-004','BPI','5678900000001','Yuki Uchiyama','Father','09179999005',1710432000,1710432000),(6,6,'EMP-2024-0006','Kyra','Lois','Santos',NULL,'Female','kyra.santos@stafforaitsoln.com','09171234506',1,2,1,'Frontend Developer','Full-Time','Regular','2024-04-01',NULL,42000.00,'Single','1998-09-30',NULL,'Filipino',NULL,'A-','Bachelor\'s Degree','147 Dela Rosa St, Legaspi Village, Makati City','34-5678906-7','12-345678906-7','1234-5678-9017','123-456-789-005','UnionBank','6789000000001','Carmen Santos','Mother','09179999006',1711900800,1711900800),(7,7,'EMP-2024-0007','Bea','Louise','Uson',NULL,'Female','bea.uson@stafforaitsoln.com','09171234507',1,3,1,'QA Engineer','Full-Time','Regular','2024-04-01',NULL,40000.00,'Single','1999-12-05',NULL,'Filipino',NULL,'B-','Bachelor\'s Degree','258 Legaspi St, Legaspi Village, Makati City','34-5678907-8','12-345678907-8','1234-5678-9018','123-456-789-006','BDO','7890000000001','Robert Uson','Father','09179999007',1711900800,1711900800),(8,8,'EMP-2024-0008','Angela',NULL,'Tolentino',NULL,'Female','angela.tolentino@stafforaitsoln.com','09171234508',2,5,1,'Systems Administrator','Full-Time','Regular','2024-06-01',NULL,43000.00,'Single','1996-08-17',NULL,'Filipino',NULL,'O+','Bachelor\'s Degree','369 Chino Roces Ave, Sta. Cruz, Makati City','34-5678908-9','12-345678908-9','1234-5678-9019','123-456-789-007','Metrobank','8900000000001','Grace Tolentino','Mother','09179999008',1717171200,1717171200);
/*!40000 ALTER TABLE `employee` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_benefit`
--

DROP TABLE IF EXISTS `employee_benefit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_benefit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `benefit_id` int(11) NOT NULL,
  `enrolled_at` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_benefit` (`employee_id`,`benefit_id`),
  KEY `benefit_id` (`benefit_id`),
  CONSTRAINT `employee_benefit_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_benefit_ibfk_2` FOREIGN KEY (`benefit_id`) REFERENCES `benefit` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_benefit`
--

LOCK TABLES `employee_benefit` WRITE;
/*!40000 ALTER TABLE `employee_benefit` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_benefit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_doc_checklist`
--

DROP TABLE IF EXISTS `employee_doc_checklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_doc_checklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `status` enum('Pending','Submitted','Approved','Waived') NOT NULL DEFAULT 'Pending',
  `document_id` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `document_id` (`document_id`),
  CONSTRAINT `employee_doc_checklist_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_doc_checklist_ibfk_2` FOREIGN KEY (`document_id`) REFERENCES `employee_document` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_doc_checklist`
--

LOCK TABLES `employee_doc_checklist` WRITE;
/*!40000 ALTER TABLE `employee_doc_checklist` DISABLE KEYS */;
INSERT INTO `employee_doc_checklist` VALUES (1,1,'Employment Contract','Approved',NULL,NULL,1777986151,1777986151),(2,2,'Employment Contract','Approved',NULL,NULL,1777986151,1777986151),(3,3,'Employment Contract','Approved',NULL,NULL,1777986151,1777986151),(4,4,'Employment Contract','Approved',NULL,NULL,1777986151,1777986151),(5,5,'Employment Contract','Approved',NULL,NULL,1777986151,1777986151),(6,6,'Employment Contract','Approved',NULL,NULL,1777986151,1777986151),(7,7,'Employment Contract','Approved',NULL,NULL,1777986151,1777986151),(8,8,'Employment Contract','Submitted',NULL,NULL,1777986151,1777986151),(9,1,'SSS E1 Form / SS Card','Approved',NULL,NULL,1777986151,1777986151),(10,2,'SSS E1 Form / SS Card','Approved',NULL,NULL,1777986151,1777986151),(11,3,'SSS E1 Form / SS Card','Approved',NULL,NULL,1777986151,1777986151),(12,4,'SSS E1 Form / SS Card','Approved',NULL,NULL,1777986151,1777986151),(13,5,'SSS E1 Form / SS Card','Approved',NULL,NULL,1777986151,1777986151),(14,6,'SSS E1 Form / SS Card','Approved',NULL,NULL,1777986151,1777986151),(15,7,'SSS E1 Form / SS Card','Approved',NULL,NULL,1777986151,1777986151),(16,8,'SSS E1 Form / SS Card','Submitted',NULL,NULL,1777986151,1777986151),(17,1,'PhilHealth MDR (Member Data Record)','Approved',NULL,NULL,1777986151,1777986151),(18,2,'PhilHealth MDR (Member Data Record)','Approved',NULL,NULL,1777986151,1777986151),(19,3,'PhilHealth MDR (Member Data Record)','Approved',NULL,NULL,1777986151,1777986151),(20,4,'PhilHealth MDR (Member Data Record)','Approved',NULL,NULL,1777986151,1777986151),(21,5,'PhilHealth MDR (Member Data Record)','Approved',NULL,NULL,1777986151,1777986151),(22,6,'PhilHealth MDR (Member Data Record)','Approved',NULL,NULL,1777986151,1777986151),(23,7,'PhilHealth MDR (Member Data Record)','Approved',NULL,NULL,1777986151,1777986151),(24,8,'PhilHealth MDR (Member Data Record)','Submitted',NULL,NULL,1777986151,1777986151),(25,1,'Pag-IBIG MDF (Membership Data Form)','Approved',NULL,NULL,1777986151,1777986151),(26,2,'Pag-IBIG MDF (Membership Data Form)','Approved',NULL,NULL,1777986151,1777986151),(27,3,'Pag-IBIG MDF (Membership Data Form)','Approved',NULL,NULL,1777986151,1777986151),(28,4,'Pag-IBIG MDF (Membership Data Form)','Approved',NULL,NULL,1777986151,1777986151),(29,5,'Pag-IBIG MDF (Membership Data Form)','Approved',NULL,NULL,1777986151,1777986151),(30,6,'Pag-IBIG MDF (Membership Data Form)','Approved',NULL,NULL,1777986151,1777986151),(31,7,'Pag-IBIG MDF (Membership Data Form)','Approved',NULL,NULL,1777986151,1777986151),(32,8,'Pag-IBIG MDF (Membership Data Form)','Submitted',NULL,NULL,1777986151,1777986151),(33,1,'BIR Form 2316 / TIN Card','Approved',NULL,NULL,1777986151,1777986151),(34,2,'BIR Form 2316 / TIN Card','Approved',NULL,NULL,1777986151,1777986151),(35,3,'BIR Form 2316 / TIN Card','Approved',NULL,NULL,1777986151,1777986151),(36,4,'BIR Form 2316 / TIN Card','Approved',NULL,NULL,1777986151,1777986151),(37,5,'BIR Form 2316 / TIN Card','Approved',NULL,NULL,1777986151,1777986151),(38,6,'BIR Form 2316 / TIN Card','Approved',NULL,NULL,1777986151,1777986151),(39,7,'BIR Form 2316 / TIN Card','Approved',NULL,NULL,1777986151,1777986151),(40,8,'BIR Form 2316 / TIN Card','Submitted',NULL,NULL,1777986151,1777986151),(41,1,'NBI Clearance','Approved',NULL,NULL,1777986151,1777986151),(42,2,'NBI Clearance','Approved',NULL,NULL,1777986151,1777986151),(43,3,'NBI Clearance','Approved',NULL,NULL,1777986151,1777986151),(44,4,'NBI Clearance','Approved',NULL,NULL,1777986151,1777986151),(45,5,'NBI Clearance','Approved',NULL,NULL,1777986151,1777986151),(46,6,'NBI Clearance','Approved',NULL,NULL,1777986151,1777986151),(47,7,'NBI Clearance','Approved',NULL,NULL,1777986151,1777986151),(48,8,'NBI Clearance','Submitted',NULL,NULL,1777986151,1777986151),(49,1,'Birth Certificate (PSA-authenticated)','Approved',NULL,NULL,1777986151,1777986151),(50,2,'Birth Certificate (PSA-authenticated)','Approved',NULL,NULL,1777986151,1777986151),(51,3,'Birth Certificate (PSA-authenticated)','Approved',NULL,NULL,1777986151,1777986151),(52,4,'Birth Certificate (PSA-authenticated)','Approved',NULL,NULL,1777986151,1777986151),(53,5,'Birth Certificate (PSA-authenticated)','Approved',NULL,NULL,1777986151,1777986151),(54,6,'Birth Certificate (PSA-authenticated)','Approved',NULL,NULL,1777986151,1777986151),(55,7,'Birth Certificate (PSA-authenticated)','Approved',NULL,NULL,1777986151,1777986151),(56,8,'Birth Certificate (PSA-authenticated)','Submitted',NULL,NULL,1777986151,1777986151),(57,1,'Medical Certificate','Approved',NULL,NULL,1777986151,1777986151),(58,2,'Medical Certificate','Approved',NULL,NULL,1777986151,1777986151),(59,3,'Medical Certificate','Approved',NULL,NULL,1777986151,1777986151),(60,4,'Medical Certificate','Approved',NULL,NULL,1777986151,1777986151),(61,5,'Medical Certificate','Approved',NULL,NULL,1777986151,1777986151),(62,6,'Medical Certificate','Approved',NULL,NULL,1777986151,1777986151),(63,7,'Medical Certificate','Approved',NULL,NULL,1777986151,1777986151),(64,8,'Medical Certificate','Submitted',NULL,NULL,1777986151,1777986151),(65,1,'Diploma / Transcript of Records','Approved',NULL,NULL,1777986151,1777986151),(66,2,'Diploma / Transcript of Records','Approved',NULL,NULL,1777986151,1777986151),(67,3,'Diploma / Transcript of Records','Approved',NULL,NULL,1777986151,1777986151),(68,4,'Diploma / Transcript of Records','Approved',NULL,NULL,1777986151,1777986151),(69,5,'Diploma / Transcript of Records','Approved',NULL,NULL,1777986151,1777986151),(70,6,'Diploma / Transcript of Records','Approved',NULL,NULL,1777986151,1777986151),(71,7,'Diploma / Transcript of Records','Approved',NULL,NULL,1777986151,1777986151),(72,8,'Diploma / Transcript of Records','Submitted',NULL,NULL,1777986151,1777986151),(73,1,'2×2 ID Photo (white background)','Approved',NULL,NULL,1777986151,1777986151),(74,2,'2×2 ID Photo (white background)','Approved',NULL,NULL,1777986151,1777986151),(75,3,'2×2 ID Photo (white background)','Approved',NULL,NULL,1777986151,1777986151),(76,4,'2×2 ID Photo (white background)','Approved',NULL,NULL,1777986151,1777986151),(77,5,'2×2 ID Photo (white background)','Approved',NULL,NULL,1777986151,1777986151),(78,6,'2×2 ID Photo (white background)','Approved',NULL,NULL,1777986151,1777986151),(79,7,'2×2 ID Photo (white background)','Approved',NULL,NULL,1777986151,1777986151),(80,8,'2×2 ID Photo (white background)','Submitted',NULL,NULL,1777986151,1777986151);
/*!40000 ALTER TABLE `employee_doc_checklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_document`
--

DROP TABLE IF EXISTS `employee_document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_at` int(11) NOT NULL DEFAULT 0,
  `file_data` mediumblob DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_document_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_document`
--

LOCK TABLES `employee_document` WRITE;
/*!40000 ALTER TABLE `employee_document` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_document` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `category` enum('positive','constructive','suggestion') NOT NULL DEFAULT 'positive',
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `employee` (`id`) ON DELETE SET NULL,
  CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES (1,4,2,'Great job on the sprint demo last week! The new dashboard feature looks really clean and intuitive.','positive',0,1777478400);
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holiday`
--

DROP TABLE IF EXISTS `holiday`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `holiday` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `date` date NOT NULL,
  `type` enum('Regular','Special Non-Working','Special Working') NOT NULL DEFAULT 'Regular',
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holiday`
--

LOCK TABLES `holiday` WRITE;
/*!40000 ALTER TABLE `holiday` DISABLE KEYS */;
INSERT INTO `holiday` VALUES (1,'Labor Day','2026-05-01','Regular',0,1767196800);
/*!40000 ALTER TABLE `holiday` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_posting`
--

DROP TABLE IF EXISTS `job_posting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_posting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `salary_min` decimal(12,2) DEFAULT NULL,
  `salary_max` decimal(12,2) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `type` varchar(40) DEFAULT NULL,
  `status` enum('Open','Closed','On Hold') NOT NULL DEFAULT 'Open',
  `posted_by` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  KEY `posted_by` (`posted_by`),
  CONSTRAINT `job_posting_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`) ON DELETE SET NULL,
  CONSTRAINT `job_posting_ibfk_2` FOREIGN KEY (`posted_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_posting`
--

LOCK TABLES `job_posting` WRITE;
/*!40000 ALTER TABLE `job_posting` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_posting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_balance`
--

DROP TABLE IF EXISTS `leave_balance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `sil_accrued` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sil_manual_override` tinyint(1) NOT NULL DEFAULT 0,
  `sil_used` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sil_carried_over` decimal(5,2) NOT NULL DEFAULT 0.00,
  `birthday_used` tinyint(1) NOT NULL DEFAULT 0,
  `bereavement_used` decimal(3,1) NOT NULL DEFAULT 0.0,
  `paternity_used` decimal(3,1) NOT NULL DEFAULT 0.0,
  `maternity_used` int(11) NOT NULL DEFAULT 0,
  `solo_parent_used` decimal(3,1) NOT NULL DEFAULT 0.0,
  `special_women_used` int(11) NOT NULL DEFAULT 0,
  `vawc_used` decimal(3,1) NOT NULL DEFAULT 0.0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_emp_year` (`employee_id`,`year`),
  CONSTRAINT `leave_balance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balance`
--

LOCK TABLES `leave_balance` WRITE;
/*!40000 ALTER TABLE `leave_balance` DISABLE KEYS */;
INSERT INTO `leave_balance` VALUES (1,1,2026,6.25,0,1.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777986239),(2,2,2026,6.25,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777986239),(3,3,2026,6.25,0,1.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777986239),(4,4,2026,6.25,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777986239),(5,5,2026,6.25,0,1.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777986239),(6,6,2026,6.25,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777986239),(7,7,2026,6.25,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777986239),(8,8,2026,6.25,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777986239);
/*!40000 ALTER TABLE `leave_balance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_request`
--

DROP TABLE IF EXISTS `leave_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type` varchar(60) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days` decimal(4,1) NOT NULL DEFAULT 1.0,
  `reason` text DEFAULT NULL,
  `doc_path` varchar(255) DEFAULT NULL,
  `deducted_from` varchar(30) DEFAULT NULL,
  `status` enum('Pending','Approved','Denied') NOT NULL DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `leave_request_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_request_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_request`
--

LOCK TABLES `leave_request` WRITE;
/*!40000 ALTER TABLE `leave_request` DISABLE KEYS */;
INSERT INTO `leave_request` VALUES (1,6,'SIL','2026-04-30','2026-04-30',1.0,'Personal errand — DMV appointment',NULL,NULL,'Approved',2,1777046400,1777132800),(2,5,'SIL','2026-04-29','2026-04-29',1.0,'Medical check-up',NULL,NULL,'Pending',NULL,1777305600,1777305600),(3,8,'SIL','2026-04-28','2026-04-28',1.0,'Family matter',NULL,NULL,'Denied',3,1777219200,1777219200);
/*!40000 ALTER TABLE `leave_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lms_enrollment`
--

DROP TABLE IF EXISTS `lms_enrollment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_enrollment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `progress` tinyint(4) NOT NULL DEFAULT 0,
  `status` enum('Not Started','In Progress','Completed') NOT NULL DEFAULT 'Not Started',
  `completed_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lms_enroll` (`module_id`,`employee_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `lms_enrollment_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `lms_module` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lms_enrollment_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lms_enrollment`
--

LOCK TABLES `lms_enrollment` WRITE;
/*!40000 ALTER TABLE `lms_enrollment` DISABLE KEYS */;
/*!40000 ALTER TABLE `lms_enrollment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lms_module`
--

DROP TABLE IF EXISTS `lms_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('document','video','quiz') NOT NULL DEFAULT 'document',
  `duration_mins` int(11) NOT NULL DEFAULT 0,
  `department_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `lms_module_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lms_module_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lms_module`
--

LOCK TABLES `lms_module` WRITE;
/*!40000 ALTER TABLE `lms_module` DISABLE KEYS */;
/*!40000 ALTER TABLE `lms_module` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `onboarding_task`
--

DROP TABLE IF EXISTS `onboarding_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `onboarding_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `task_name` varchar(200) NOT NULL,
  `stage` enum('Pre-boarding','Week 1','Week 2','Integration') NOT NULL DEFAULT 'Pre-boarding',
  `status` enum('Not Started','In Progress','Completed','On Hold') NOT NULL DEFAULT 'Not Started',
  `due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `onboarding_task_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onboarding_task`
--

LOCK TABLES `onboarding_task` WRITE;
/*!40000 ALTER TABLE `onboarding_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `onboarding_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personnel_action`
--

DROP TABLE IF EXISTS `personnel_action`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personnel_action` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `type` enum('Promotion','Demotion','Salary Adjustment','Transfer','Resignation','Termination','Other') NOT NULL,
  `effective_date` date NOT NULL,
  `old_value` varchar(200) DEFAULT NULL,
  `new_value` varchar(200) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Approved',
  `created_by` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `personnel_action_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE,
  CONSTRAINT `personnel_action_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personnel_action`
--

LOCK TABLES `personnel_action` WRITE;
/*!40000 ALTER TABLE `personnel_action` DISABLE KEYS */;
/*!40000 ALTER TABLE `personnel_action` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `position`
--

DROP TABLE IF EXISTS `position`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `position` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT 1,
  `salary_min` decimal(12,2) DEFAULT 0.00,
  `salary_max` decimal(12,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `position_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `position`
--

LOCK TABLES `position` WRITE;
/*!40000 ALTER TABLE `position` DISABLE KEYS */;
INSERT INTO `position` VALUES (1,'Software Developer','SWD',1,2,38000.00,55000.00,'Develops and maintains software applications and systems.',1704038400),(2,'Frontend Developer','FED',1,2,36000.00,50000.00,'Designs and implements user-facing features and interfaces.',1704038400),(3,'QA Engineer','QAE',1,2,34000.00,48000.00,'Ensures software quality through systematic testing and QA processes.',1704038400),(4,'IT Support Specialist','ITS',2,2,30000.00,42000.00,'Provides technical support and troubleshooting for end users.',1704038400),(5,'Systems Administrator','SYSAD',2,3,40000.00,58000.00,'Manages and maintains servers, networks, and IT infrastructure.',1704038400);
/*!40000 ALTER TABLE `position` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project`
--

DROP TABLE IF EXISTS `project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('Active','On Hold','Completed','Cancelled') NOT NULL DEFAULT 'Active',
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `project_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `employee` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project`
--

LOCK TABLES `project` WRITE;
/*!40000 ALTER TABLE `project` DISABLE KEYS */;
/*!40000 ALTER TABLE `project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_member`
--

DROP TABLE IF EXISTS `project_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_proj_member` (`project_id`,`employee_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `project_member_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_member_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_member`
--

LOCK TABLES `project_member` WRITE;
/*!40000 ALTER TABLE `project_member` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_task`
--

DROP TABLE IF EXISTS `project_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `priority` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `status` enum('To Do','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'To Do',
  `progress` tinyint(4) NOT NULL DEFAULT 0,
  `due_date` date DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `project_task_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_task_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_task`
--

LOCK TABLES `project_task` WRITE;
/*!40000 ALTER TABLE `project_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift`
--

DROP TABLE IF EXISTS `shift`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shift` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `shift_name` varchar(80) DEFAULT NULL,
  `work_rule_id` int(11) DEFAULT NULL,
  `break_minutes` int(11) DEFAULT 60,
  `break_start_time` time DEFAULT NULL,
  `break_end_time` time DEFAULT NULL,
  `type` enum('Onsite','Remote','Hybrid') NOT NULL DEFAULT 'Onsite',
  `status` enum('Scheduled','Completed','Missed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shift_emp_date` (`employee_id`,`date`),
  CONSTRAINT `shift_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift`
--

LOCK TABLES `shift` WRITE;
/*!40000 ALTER TABLE `shift` DISABLE KEYS */;
INSERT INTO `shift` VALUES (1,2,'2026-04-28','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(2,2,'2026-04-29','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(3,2,'2026-04-30','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(4,2,'2026-05-02','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(5,3,'2026-04-28','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(6,3,'2026-04-29','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(7,3,'2026-04-30','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(8,3,'2026-05-02','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(9,4,'2026-04-28','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(10,4,'2026-04-29','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(11,4,'2026-04-30','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(12,4,'2026-05-02','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(13,5,'2026-04-28','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(14,5,'2026-04-29','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(15,5,'2026-04-30','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(16,5,'2026-05-02','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(17,6,'2026-04-28','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(18,6,'2026-04-29','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(19,6,'2026-04-30','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(20,6,'2026-05-02','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(21,7,'2026-04-28','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(22,7,'2026-04-29','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(23,7,'2026-04-30','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(24,7,'2026-05-02','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(25,8,'2026-04-28','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(26,8,'2026-04-29','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(27,8,'2026-04-30','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(28,8,'2026-05-02','09:00:00','18:00:00','Standard Day Shift',1,60,'12:00:00','13:00:00','Onsite','Completed',1,1777986151),(32,2,'2026-05-01','09:00:00','18:00:00','Labor Day Holiday',1,60,NULL,NULL,'Onsite','Cancelled',1,1777986151),(33,3,'2026-05-01','09:00:00','18:00:00','Labor Day Holiday',1,60,NULL,NULL,'Onsite','Cancelled',1,1777986151),(34,4,'2026-05-01','09:00:00','18:00:00','Labor Day Holiday',1,60,NULL,NULL,'Onsite','Cancelled',1,1777986151),(35,5,'2026-05-01','09:00:00','18:00:00','Labor Day Holiday',1,60,NULL,NULL,'Onsite','Cancelled',1,1777986151),(36,6,'2026-05-01','09:00:00','18:00:00','Labor Day Holiday',1,60,NULL,NULL,'Onsite','Cancelled',1,1777986151),(37,7,'2026-05-01','09:00:00','18:00:00','Labor Day Holiday',1,60,NULL,NULL,'Onsite','Cancelled',1,1777986151),(38,8,'2026-05-01','09:00:00','18:00:00','Labor Day Holiday',1,60,NULL,NULL,'Onsite','Cancelled',1,1777986151);
/*!40000 ALTER TABLE `shift` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_notification`
--

DROP TABLE IF EXISTS `system_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `system_notification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_notification`
--

LOCK TABLES `system_notification` WRITE;
/*!40000 ALTER TABLE `system_notification` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_notification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_setting`
--

DROP TABLE IF EXISTS `system_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(80) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_setting`
--

LOCK TABLES `system_setting` WRITE;
/*!40000 ALTER TABLE `system_setting` DISABLE KEYS */;
INSERT INTO `system_setting` VALUES (1,'company_name','Staffora IT Solutions',1777430555),(2,'company_email','hr@stafforaitsoln.com',1777430555),(3,'leave_vacation','15',1777430555),(4,'leave_sick','10',1777430555),(5,'leave_personal','3',1777430555),(6,'leave_emergency','3',1777430555),(7,'payroll_cutoff','15',1777430555),(8,'work_hours_per_day','8',1777430555),(9,'wifi_restriction_enabled','0',1777984870),(10,'wifi_strict_mode','0',1777984870);
/*!40000 ALTER TABLE `system_setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timelog_dispute`
--

DROP TABLE IF EXISTS `timelog_dispute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timelog_dispute` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `dispute_date` date NOT NULL,
  `original_clock_in` time DEFAULT NULL,
  `original_clock_out` time DEFAULT NULL,
  `corrected_clock_in` time DEFAULT NULL,
  `corrected_clock_out` time DEFAULT NULL,
  `break_minutes` int(11) DEFAULT 60,
  `dispute_type` enum('Timelog Correction','Schedule Issue','Generic') NOT NULL DEFAULT 'Timelog Correction',
  `reason` text DEFAULT NULL,
  `doc_path` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Denied') NOT NULL DEFAULT 'Pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `reviewed_by` (`reviewed_by`),
  CONSTRAINT `timelog_dispute_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timelog_dispute_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timelog_dispute`
--

LOCK TABLES `timelog_dispute` WRITE;
/*!40000 ALTER TABLE `timelog_dispute` DISABLE KEYS */;
INSERT INTO `timelog_dispute` VALUES (1,4,'2026-04-29','09:18:00','18:20:00','09:05:00','18:20:00',60,'Timelog Correction','I was actually on time but the biometric scanner was slow to register. I have a colleague who can attest.',NULL,'Pending',NULL,NULL,1777478400);
/*!40000 ALTER TABLE `timelog_dispute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(80) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Super Admin','Manager','Employee') NOT NULL DEFAULT 'Employee',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `department_id` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `user_ibfk_1` (`department_id`),
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'joaquin.celis','joaquin.celis@stafforaitsoln.com','42f749ade7f9e195bf475f37a44cafcb','Super Admin','Active',NULL,1705248000),(2,'remus.marasigan','remus.marasigan@stafforaitsoln.com','42f749ade7f9e195bf475f37a44cafcb','Manager','Active',1,1706716800),(3,'ralph.pinpin','ralph.pinpin@stafforaitsoln.com','42f749ade7f9e195bf475f37a44cafcb','Manager','Active',2,1706716800),(4,'mon.lapid','mon.lapid@stafforaitsoln.com','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',1,1709222400),(5,'joshua.uchiyama','joshua.uchiyama@stafforaitsoln.com','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',2,1710432000),(6,'kyra.santos','kyra.santos@stafforaitsoln.com','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',1,1711900800),(7,'bea.uson','bea.uson@stafforaitsoln.com','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',1,1711900800),(8,'angela.tolentino','angela.tolentino@stafforaitsoln.com','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',2,1717171200);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_rule`
--

DROP TABLE IF EXISTS `work_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `work_rule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `schedule_type` enum('Basic','Strict Flexi','Relax Flexi','Custom') NOT NULL DEFAULT 'Basic',
  `work_hours` decimal(4,2) NOT NULL DEFAULT 8.00,
  `break_minutes` int(11) NOT NULL DEFAULT 60,
  `break_count` int(11) NOT NULL DEFAULT 4,
  `start_time` time DEFAULT '09:00:00',
  `end_time` time DEFAULT '18:00:00',
  `buffer_before` int(11) DEFAULT 0,
  `buffer_after` int(11) DEFAULT 0,
  `core_start` time DEFAULT NULL,
  `core_duration` decimal(4,2) DEFAULT NULL,
  `working_days` varchar(20) DEFAULT '1,2,3,4,5',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `work_rule_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_rule`
--

LOCK TABLES `work_rule` WRITE;
/*!40000 ALTER TABLE `work_rule` DISABLE KEYS */;
INSERT INTO `work_rule` VALUES (1,'Standard Day Shift','Basic',8.00,60,4,'09:00:00','18:00:00',0,0,NULL,NULL,'1,2,3,4,5',1,NULL,1704038400);
/*!40000 ALTER TABLE `work_rule` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-05 21:26:11
