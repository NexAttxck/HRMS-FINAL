-- =============================================================
--  HRMS V3 — Full Database Export
--  Database : hrms_db
--  Exported : 2026-05-04
--  Engine   : MariaDB 10.4 / MySQL 5.7+ compatible
-- =============================================================
--
--  TABLES (31 total):
--    Core        : user, employee, department, position
--    Attendance  : attendance, shift, work_rule, timelog_dispute
--    Payroll     : payroll, payslip, benefit, employee_benefit
--    Leave       : leave_request, leave_balance
--    Onboarding  : onboarding_task, employee_document,
--                  employee_doc_checklist
--    Recruitment : job_posting, candidate
--    Projects    : project, project_member, project_task
--    LMS         : lms_module, lms_enrollment
--    HR Tools    : announcement, holiday, personnel_action,
--                  feedback, audit_log
--    System      : system_setting, system_notification
--
--  HOW TO IMPORT:
--    Option A — phpMyAdmin:
--      1. Open phpMyAdmin → "Import" tab
--      2. Choose this file → Click "Go"
--
--    Option B — Command Line:
--      mysql -u root -p < hrms_db_full.sql
--
--    Option C — MySQL Workbench:
--      Server → Data Import → Import from Self-Contained File
--
--  NOTES:
--    • Default admin login  →  admin@hrms.com  /  password: admin123
--    • Timezone set to Asia/Manila in config.php
--    • Run on XAMPP (Apache + MySQL/MariaDB)
-- =============================================================

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
-- Current Database: `hrms_db`
--

/*!40000 DROP DATABASE IF EXISTS `hrms_db`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `hrms_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `hrms_db`;

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement`
--

LOCK TABLES `announcement` WRITE;
/*!40000 ALTER TABLE `announcement` DISABLE KEYS */;
INSERT INTO `announcement` VALUES (1,'Welcome to CURA Corporation!','balagbag',1,'Urgent',1,'All',1,NULL,1777430555,1777835547),(2,'Company Onboarding Week','All new hires must complete their onboarding checklist by end of Week 2. Please coordinate with HR.',2,'High',1,'All',1,NULL,1777430555,1777430555),(3,'Office Wi-Fi Credentials','SSID: CURA-Office-5G | Password: CuraConnect2026! Do not share outside.',1,'Medium',0,'All',1,NULL,1777430555,1777430555),(4,'Health Card Enrollment','PhilHealth and HMO enrollment forms are due this Friday. Submit to HR.',2,'High',1,'All',1,NULL,1777430555,1777430555),(5,'First Sprint Kickoff','Software Development team: our first sprint starts Monday. Jira boards are live.',12,'Medium',0,'Managers',1,NULL,1777430555,1777430555),(6,'balagbag','rahhhhh',1,'Urgent',0,'All',1,NULL,1777835572,1777835578),(7,'jdjsidajdsosd','balagbag pakak',1,'Urgent',0,'All',1,NULL,1777835811,1777835811);
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
) ENGINE=InnoDB AUTO_INCREMENT=222 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (26,3,'2026-04-22','08:01:00','17:26:00','On Time',9.00,NULL,1777430555,NULL),(27,3,'2026-04-21','08:21:00','17:27:00','Late',9.00,NULL,1777430555,NULL),(28,3,'2026-04-20','08:10:00','17:16:00','On Time',9.00,NULL,1777430555,NULL),(29,3,'2026-04-17','08:06:00','17:13:00','On Time',9.00,NULL,1777430555,NULL),(30,3,'2026-04-16','07:57:00','17:14:00','On Time',8.28,' [Corrected via dispute #1]',1777430555,NULL),(31,4,'2026-04-29','08:01:00','17:10:00','On Time',9.00,NULL,1777430555,NULL),(32,4,'2026-04-28','08:05:00','17:04:00','On Time',9.00,NULL,1777430555,NULL),(33,4,'2026-04-27','08:06:00','17:08:00','On Time',9.00,NULL,1777430555,NULL),(34,4,'2026-04-24','08:04:00','17:20:00','On Time',9.00,NULL,1777430555,NULL),(35,4,'2026-04-23','08:09:00','17:04:00','On Time',9.00,NULL,1777430555,NULL),(36,4,'2026-04-22','08:07:00','17:00:00','On Time',9.00,NULL,1777430555,NULL),(37,4,'2026-04-21','08:05:00','17:02:00','On Time',9.00,NULL,1777430555,NULL),(38,4,'2026-04-20','08:01:00','17:15:00','On Time',9.00,NULL,1777430555,NULL),(39,4,'2026-04-17','08:04:00','17:07:00','On Time',9.00,NULL,1777430555,NULL),(40,4,'2026-04-16','08:36:00','17:10:00','Late',9.00,NULL,1777430555,NULL),(41,5,'2026-04-29','08:00:00','17:02:00','On Time',9.00,NULL,1777430555,NULL),(42,5,'2026-04-28','08:09:00','17:05:00','On Time',9.00,NULL,1777430555,NULL),(43,5,'2026-04-27','08:01:00','17:14:00','On Time',9.00,NULL,1777430555,NULL),(44,5,'2026-04-24','08:28:00','17:13:00','Late',9.00,NULL,1777430555,NULL),(45,5,'2026-04-23','08:08:00','17:16:00','On Time',9.00,NULL,1777430555,NULL),(46,5,'2026-04-22','08:01:00','17:00:00','On Time',9.00,NULL,1777430555,NULL),(47,5,'2026-04-21','08:03:00','17:20:00','On Time',9.00,NULL,1777430555,NULL),(48,5,'2026-04-20','08:10:00','17:12:00','On Time',9.00,NULL,1777430555,NULL),(49,5,'2026-04-17','08:08:00','17:23:00','On Time',9.00,NULL,1777430555,NULL),(50,5,'2026-04-16','08:01:00','17:27:00','On Time',9.00,NULL,1777430555,NULL),(51,6,'2026-04-29','08:00:00','17:26:00','On Time',9.00,NULL,1777430555,NULL),(52,6,'2026-04-28','08:06:00','17:18:00','On Time',9.00,NULL,1777430555,NULL),(53,6,'2026-04-27','08:09:00','17:03:00','On Time',9.00,NULL,1777430555,NULL),(54,6,'2026-04-24','08:18:00','17:30:00','Late',9.00,NULL,1777430555,NULL),(55,6,'2026-04-23','08:02:00','17:03:00','On Time',9.00,NULL,1777430555,NULL),(56,6,'2026-04-22','08:08:00','17:08:00','On Time',9.00,NULL,1777430555,NULL),(57,6,'2026-04-21','08:44:00','17:06:00','Late',9.00,NULL,1777430555,NULL),(58,6,'2026-04-20','08:03:00','17:15:00','On Time',9.00,NULL,1777430555,NULL),(59,6,'2026-04-17','08:38:00','17:08:00','Late',9.00,NULL,1777430555,NULL),(60,6,'2026-04-16','08:32:00','17:25:00','Late',9.00,NULL,1777430555,NULL),(61,7,'2026-04-29','08:09:00','17:17:00','On Time',9.00,NULL,1777430555,NULL),(62,7,'2026-04-28','08:05:00','17:14:00','On Time',9.00,NULL,1777430555,NULL),(63,7,'2026-04-27','08:08:00','17:02:00','On Time',9.00,NULL,1777430555,NULL),(64,7,'2026-04-24','08:09:00','17:11:00','On Time',9.00,NULL,1777430555,NULL),(65,7,'2026-04-23','08:10:00','17:29:00','On Time',9.00,NULL,1777430555,NULL),(66,7,'2026-04-22','08:05:00','17:06:00','On Time',9.00,NULL,1777430555,NULL),(67,7,'2026-04-21','08:05:00','17:22:00','On Time',9.00,NULL,1777430555,NULL),(68,7,'2026-04-20','08:07:00','17:23:00','On Time',9.00,NULL,1777430555,NULL),(69,7,'2026-04-17','08:04:00','17:20:00','On Time',9.00,NULL,1777430555,NULL),(70,7,'2026-04-16','08:21:00','17:17:00','Late',9.00,NULL,1777430555,NULL),(71,8,'2026-04-29','08:07:00','17:06:00','On Time',9.00,NULL,1777430555,NULL),(72,8,'2026-04-27','08:06:00','17:15:00','On Time',9.00,NULL,1777430555,NULL),(73,8,'2026-04-24','08:02:00','17:16:00','On Time',9.00,NULL,1777430555,NULL),(74,8,'2026-04-23','08:07:00','17:13:00','On Time',9.00,NULL,1777430555,NULL),(75,8,'2026-04-22','08:07:00','17:27:00','On Time',9.00,NULL,1777430555,NULL),(76,8,'2026-04-21','08:04:00','17:28:00','On Time',9.00,NULL,1777430555,NULL),(77,8,'2026-04-20','08:00:00','17:07:00','On Time',9.00,NULL,1777430555,NULL),(78,8,'2026-04-17','08:03:00','17:11:00','On Time',9.00,NULL,1777430555,NULL),(79,8,'2026-04-16','08:06:00','17:23:00','On Time',9.00,NULL,1777430555,NULL),(80,9,'2026-04-29','08:04:00','17:15:00','On Time',9.00,NULL,1777430555,NULL),(81,9,'2026-04-28','08:09:00','17:16:00','On Time',9.00,NULL,1777430555,NULL),(82,9,'2026-04-27','08:05:00','17:12:00','On Time',9.00,NULL,1777430555,NULL),(83,9,'2026-04-24','08:18:00','17:20:00','Late',9.00,NULL,1777430555,NULL),(84,9,'2026-04-23','08:30:00','17:24:00','Late',9.00,NULL,1777430555,NULL),(85,9,'2026-04-22','08:26:00','17:26:00','Late',9.00,NULL,1777430555,NULL),(86,9,'2026-04-21','08:04:00','17:26:00','On Time',9.00,NULL,1777430555,NULL),(87,9,'2026-04-20','08:01:00','17:01:00','On Time',9.00,NULL,1777430555,NULL),(88,9,'2026-04-17','08:09:00','17:00:00','On Time',9.00,NULL,1777430555,NULL),(89,9,'2026-04-16','08:05:00','17:04:00','On Time',9.00,NULL,1777430555,NULL),(90,10,'2026-04-29','08:06:00','17:18:00','On Time',9.00,NULL,1777430555,NULL),(91,10,'2026-04-28','08:06:00','17:02:00','On Time',9.00,NULL,1777430555,NULL),(92,10,'2026-04-27','08:03:00','17:01:00','On Time',9.00,NULL,1777430555,NULL),(93,10,'2026-04-24','08:07:00','17:22:00','On Time',9.00,NULL,1777430555,NULL),(94,10,'2026-04-23','08:01:00','17:04:00','On Time',9.00,NULL,1777430555,NULL),(95,10,'2026-04-22','08:01:00','17:30:00','On Time',9.00,NULL,1777430555,NULL),(96,10,'2026-04-21','08:09:00','17:07:00','On Time',9.00,NULL,1777430555,NULL),(97,10,'2026-04-20','08:05:00','17:06:00','On Time',9.00,NULL,1777430555,NULL),(98,10,'2026-04-17','08:09:00','17:12:00','On Time',9.00,NULL,1777430555,NULL),(99,10,'2026-04-16','08:23:00','17:17:00','Late',9.00,NULL,1777430555,NULL),(100,11,'2026-04-29','08:03:00','17:04:00','On Time',9.00,NULL,1777430555,NULL),(101,11,'2026-04-28','08:01:00','17:19:00','On Time',9.00,NULL,1777430555,NULL),(102,11,'2026-04-27','08:05:00','17:01:00','On Time',9.00,NULL,1777430555,NULL),(103,11,'2026-04-24','08:02:00','17:15:00','On Time',9.00,NULL,1777430555,NULL),(104,11,'2026-04-23','08:06:00','17:04:00','On Time',9.00,NULL,1777430555,NULL),(105,11,'2026-04-22','08:10:00','17:13:00','On Time',9.00,NULL,1777430555,NULL),(106,11,'2026-04-21','08:00:00','17:18:00','On Time',9.00,NULL,1777430555,NULL),(107,11,'2026-04-20','08:09:00','17:26:00','On Time',9.00,NULL,1777430555,NULL),(108,11,'2026-04-17','08:03:00','17:11:00','On Time',9.00,NULL,1777430555,NULL),(109,11,'2026-04-16','08:05:00','17:05:00','On Time',9.00,NULL,1777430555,NULL),(110,12,'2026-04-29','08:00:00','17:12:00','On Time',9.00,NULL,1777430555,NULL),(111,12,'2026-04-28','08:07:00','17:04:00','On Time',9.00,NULL,1777430555,NULL),(112,12,'2026-04-27','08:03:00','17:22:00','On Time',9.00,NULL,1777430555,NULL),(113,12,'2026-04-24','08:09:00','17:03:00','On Time',9.00,NULL,1777430555,NULL),(114,12,'2026-04-23','08:06:00','17:21:00','On Time',9.00,NULL,1777430555,NULL),(115,12,'2026-04-22','08:09:00','17:21:00','On Time',9.00,NULL,1777430555,NULL),(116,12,'2026-04-21','08:09:00','17:02:00','On Time',9.00,NULL,1777430555,NULL),(117,12,'2026-04-20','08:04:00','17:01:00','On Time',9.00,NULL,1777430555,NULL),(118,12,'2026-04-17','08:02:00','17:01:00','On Time',9.00,NULL,1777430555,NULL),(119,12,'2026-04-16','08:08:00','17:11:00','On Time',9.00,NULL,1777430555,NULL),(120,13,'2026-04-29','08:08:00','17:24:00','On Time',9.00,NULL,1777430555,NULL),(121,13,'2026-04-28','08:07:00','17:06:00','On Time',9.00,NULL,1777430555,NULL),(122,13,'2026-04-27','08:06:00','17:13:00','On Time',9.00,NULL,1777430555,NULL),(123,13,'2026-04-24','08:05:00','17:08:00','On Time',9.00,NULL,1777430555,NULL),(124,13,'2026-04-23','08:08:00','17:29:00','On Time',9.00,NULL,1777430555,NULL),(125,13,'2026-04-22','08:09:00','17:23:00','On Time',9.00,NULL,1777430555,NULL),(126,13,'2026-04-21','08:06:00','17:28:00','On Time',9.00,NULL,1777430555,NULL),(127,13,'2026-04-20','08:00:00','17:24:00','On Time',9.00,NULL,1777430555,NULL),(128,13,'2026-04-17','08:02:00','17:13:00','On Time',9.00,NULL,1777430555,NULL),(129,13,'2026-04-16','08:06:00','17:11:00','On Time',9.00,NULL,1777430555,NULL),(130,14,'2026-04-29','08:00:00','17:25:00','On Time',9.00,NULL,1777430555,NULL),(131,14,'2026-04-28','08:06:00','17:14:00','On Time',9.00,NULL,1777430555,NULL),(132,14,'2026-04-27','08:03:00','17:25:00','On Time',9.00,NULL,1777430555,NULL),(133,14,'2026-04-24','08:00:00','17:26:00','On Time',9.00,NULL,1777430555,NULL),(134,14,'2026-04-23','08:09:00','17:21:00','On Time',9.00,NULL,1777430555,NULL),(135,14,'2026-04-22','08:09:00','17:24:00','On Time',9.00,NULL,1777430555,NULL),(136,14,'2026-04-21','08:31:00','17:12:00','Late',9.00,NULL,1777430555,NULL),(137,14,'2026-04-20','08:08:00','17:10:00','On Time',9.00,NULL,1777430555,NULL),(138,14,'2026-04-17','08:02:00','17:16:00','On Time',9.00,NULL,1777430555,NULL),(139,14,'2026-04-16','08:05:00','17:21:00','On Time',9.00,NULL,1777430555,NULL),(140,15,'2026-04-29','08:04:00','17:27:00','On Time',9.00,NULL,1777430555,NULL),(141,15,'2026-04-28','08:05:00','17:13:00','On Time',9.00,NULL,1777430555,NULL),(142,15,'2026-04-27','08:00:00','17:16:00','On Time',9.00,NULL,1777430555,NULL),(143,15,'2026-04-24','08:00:00','17:07:00','On Time',9.00,NULL,1777430555,NULL),(144,15,'2026-04-23','08:03:00','17:06:00','On Time',9.00,NULL,1777430555,NULL),(145,15,'2026-04-22','08:03:00','17:17:00','On Time',9.00,NULL,1777430555,NULL),(146,15,'2026-04-21','08:04:00','17:30:00','On Time',9.00,NULL,1777430555,NULL),(147,15,'2026-04-20','08:03:00','17:28:00','On Time',9.00,NULL,1777430555,NULL),(148,15,'2026-04-17','08:06:00','17:19:00','On Time',9.00,NULL,1777430555,NULL),(149,15,'2026-04-16','08:06:00','17:24:00','On Time',9.00,NULL,1777430555,NULL),(150,16,'2026-04-29','08:07:00','17:26:00','On Time',9.00,NULL,1777430555,NULL),(151,16,'2026-04-28','08:03:00','17:12:00','On Time',9.00,NULL,1777430555,NULL),(152,16,'2026-04-27','08:37:00','17:27:00','Late',9.00,NULL,1777430555,NULL),(153,16,'2026-04-24','08:03:00','17:21:00','On Time',9.00,NULL,1777430555,NULL),(154,16,'2026-04-23','08:10:00','17:26:00','On Time',9.00,NULL,1777430555,NULL),(155,16,'2026-04-22','08:38:00','17:03:00','Late',9.00,NULL,1777430555,NULL),(156,16,'2026-04-21','08:04:00','17:15:00','On Time',9.00,NULL,1777430555,NULL),(157,16,'2026-04-20','08:03:00','17:18:00','On Time',9.00,NULL,1777430555,NULL),(158,16,'2026-04-17','08:03:00','17:15:00','On Time',9.00,NULL,1777430555,NULL),(159,16,'2026-04-16','08:31:00','17:28:00','Late',9.00,NULL,1777430555,NULL),(160,17,'2026-04-29','08:08:00','17:17:00','On Time',9.00,NULL,1777430555,NULL),(161,17,'2026-04-28','08:01:00','17:18:00','On Time',9.00,NULL,1777430555,NULL),(162,17,'2026-04-27','08:04:00','17:21:00','On Time',9.00,NULL,1777430555,NULL),(163,17,'2026-04-24','08:02:00','17:29:00','On Time',9.00,NULL,1777430555,NULL),(164,17,'2026-04-23','08:01:00','17:05:00','On Time',9.00,NULL,1777430555,NULL),(165,17,'2026-04-22','08:07:00','17:09:00','On Time',9.00,NULL,1777430555,NULL),(166,17,'2026-04-21','08:04:00','17:25:00','On Time',9.00,NULL,1777430555,NULL),(167,17,'2026-04-20','08:07:00','17:04:00','On Time',9.00,NULL,1777430555,NULL),(168,17,'2026-04-17','08:08:00','17:29:00','On Time',9.00,NULL,1777430555,NULL),(169,17,'2026-04-16','08:07:00','17:04:00','On Time',9.00,NULL,1777430555,NULL),(180,3,'2026-05-04','02:34:38','02:34:40','Undertime',0.00,NULL,0,'::1'),(182,3,'2026-05-04','02:45:30',NULL,'On Time',0.00,NULL,0,'::1'),(183,3,'2026-05-04','02:45:31',NULL,'On Time',0.00,NULL,0,'::1'),(184,3,'2026-05-04','02:45:32',NULL,'On Time',0.00,NULL,0,'::1'),(185,3,'2026-05-04','02:45:32',NULL,'On Time',0.00,NULL,0,'::1'),(186,3,'2026-05-04','02:45:32',NULL,'On Time',0.00,NULL,0,'::1'),(187,3,'2026-05-04','02:45:32',NULL,'On Time',0.00,NULL,0,'::1'),(188,3,'2026-05-04','02:45:32',NULL,'On Time',0.00,NULL,0,'::1'),(189,3,'2026-05-04','02:45:32',NULL,'On Time',0.00,NULL,0,'::1'),(190,3,'2026-05-04','02:45:32',NULL,'On Time',0.00,NULL,0,'::1'),(191,3,'2026-05-04','02:45:32',NULL,'On Time',0.00,NULL,0,'::1'),(192,3,'2026-05-04','02:45:33',NULL,'On Time',0.00,NULL,0,'::1'),(193,3,'2026-05-04','02:45:33',NULL,'On Time',0.00,NULL,0,'::1'),(194,3,'2026-05-04','02:45:33',NULL,'On Time',0.00,NULL,0,'::1'),(195,3,'2026-05-04','02:45:33','02:46:35','On Time',0.02,NULL,0,'::1'),(196,3,'2026-05-04','02:46:37','02:46:39','On Time',0.00,NULL,0,'::1'),(197,3,'2026-05-04','02:51:42','02:51:44','On Time',0.00,NULL,0,'::1'),(198,3,'2026-05-04','03:15:44','03:15:49','On Time',0.00,NULL,0,'::1'),(199,3,'2026-05-04','03:32:54','03:33:09','On Time',0.00,NULL,0,'::1'),(200,3,'2026-05-04','03:33:15','03:33:20','On Time',0.00,NULL,0,'::1'),(201,3,'2026-05-04','03:34:47','03:36:51','On Time',0.03,NULL,1777836887,'::1'),(202,3,'2026-05-04','03:36:53','03:36:54','On Time',0.00,NULL,0,'::1'),(203,3,'2026-05-04','03:36:59','03:41:44','On Time',0.07,NULL,0,'::1'),(204,3,'2026-05-04','03:41:46','03:41:51','On Time',0.00,NULL,0,'::1'),(205,3,'2026-05-04','03:45:17','03:45:19','Undertime',0.00,NULL,0,'::1'),(206,3,'2026-05-04','03:46:52','10:46:58','Late',7.00,NULL,0,'::1'),(207,3,'2026-05-04','03:47:19','03:48:08','Undertime',0.00,NULL,0,'::1'),(208,3,'2026-05-04','03:48:15','12:48:22','Late',9.00,NULL,0,'::1'),(209,3,'2026-05-04','14:48:35','14:48:43','Late',0.00,NULL,0,'::1'),(210,3,'2026-05-04','02:49:03','14:49:08','On Time',12.00,NULL,0,'::1'),(211,3,'2026-05-04','02:52:43','14:52:48','On Time (Overtime)',12.00,NULL,0,'::1'),(212,3,'2026-05-05','02:04:34','02:04:38','Undertime',0.00,NULL,0,'::1'),(213,7,'2026-05-05','02:21:02','02:21:07','Undertime',0.00,NULL,0,'::1'),(214,3,'2026-05-05','02:29:59','02:30:04','Undertime',0.00,NULL,0,'::1'),(215,3,'2026-05-05','02:46:30','02:46:33','Undertime',0.00,NULL,0,'::1'),(216,3,'2026-05-04','16:57:03','16:57:05','Late (Overtime)',0.00,NULL,0,'::1'),(217,3,'2026-05-04','17:02:42','17:02:44','Late (Overtime)',0.00,NULL,0,'::1'),(218,3,'2026-05-04','03:03:17','03:03:20','Undertime',0.00,NULL,0,'::1'),(219,3,'2026-05-04','03:03:31','09:55:45','Undertime',6.87,NULL,0,'::1'),(220,3,'2026-05-04','02:55:56','10:10:05','On Time (Overtime)',7.23,NULL,0,'::1'),(221,3,'2026-05-04','10:13:19','10:13:21','Late (Overtime)',0.00,NULL,0,'::1');
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
) ENGINE=InnoDB AUTO_INCREMENT=267 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,1,'Login','Auth',NULL,'Super Admin logged in','127.0.0.1',1777430555),(2,1,'Created Payroll','payroll',NULL,'Created April 2026 payroll period','127.0.0.1',1777430555),(3,2,'Login','Auth',NULL,'HR Manager logged in','127.0.0.1',1777430555),(4,2,'Approved Leave','leave',NULL,'Approved leave for Claire Sy','127.0.0.1',1777430555),(5,1,'Posted Announcement','announcement',NULL,'Posted Welcome announcement','127.0.0.1',1777430555),(6,1,'Login','Auth',NULL,NULL,'::1',1777430569),(7,1,'Logout','Auth',NULL,NULL,'::1',1777430616),(8,2,'Login','Auth',NULL,NULL,'::1',1777430633),(9,2,'Logout','Auth',NULL,NULL,'::1',1777430674),(10,3,'Login','Auth',NULL,NULL,'::1',1777430694),(11,3,'Logout','Auth',NULL,NULL,'::1',1777430888),(12,1,'Login','Auth',NULL,NULL,'::1',1777430891),(13,1,'Logout','Auth',NULL,NULL,'::1',1777431211),(14,3,'Login','Auth',NULL,NULL,'::1',1777431222),(15,3,'Logout','Auth',NULL,NULL,'::1',1777431522),(16,1,'Login','Auth',NULL,NULL,'::1',1777431527),(17,1,'Logout','Auth',NULL,NULL,'::1',1777431683),(18,3,'Login','Auth',NULL,NULL,'::1',1777431688),(19,3,'Logout','Auth',NULL,NULL,'::1',1777431751),(20,1,'Login','Auth',NULL,NULL,'::1',1777431754),(21,1,'Logout','Auth',NULL,NULL,'::1',1777431832),(22,2,'Login','Auth',NULL,NULL,'::1',1777431850),(23,2,'Logout','Auth',NULL,NULL,'::1',1777431881),(24,2,'Login','Auth',NULL,NULL,'::1',1777831647),(25,2,'Logout','Auth',NULL,NULL,'::1',1777831651),(26,3,'Login','Auth',NULL,NULL,'::1',1777831656),(27,3,'Logout','Auth',NULL,NULL,'::1',1777832389),(28,2,'Login','Auth',NULL,NULL,'::1',1777832394),(29,2,'Logout','Auth',NULL,NULL,'::1',1777832403),(30,2,'Login','Auth',NULL,NULL,'::1',1777832406),(31,2,'Logout','Auth',NULL,NULL,'::1',1777832448),(32,3,'Login','Auth',NULL,NULL,'::1',1777832451),(33,3,'Logout','Auth',NULL,NULL,'::1',1777832979),(34,1,'Login','Auth',NULL,NULL,'::1',1777832984),(35,1,'Logout','Auth',NULL,NULL,'::1',1777832988),(36,2,'Login','Auth',NULL,NULL,'::1',1777832993),(37,2,'Logout','Auth',NULL,NULL,'::1',1777833002),(38,3,'Login','Auth',NULL,NULL,'::1',1777833005),(39,3,'Logout','Auth',NULL,NULL,'::1',1777833148),(40,1,'Login','Auth',NULL,NULL,'::1',1777833150),(41,1,'Logout','Auth',NULL,NULL,'::1',1777833153),(42,1,'Login','Auth',NULL,NULL,'::1',1777833239),(43,1,'Logout','Auth',NULL,NULL,'::1',1777833272),(44,3,'Login','Auth',NULL,NULL,'::1',1777833276),(45,3,'Logout','Auth',NULL,NULL,'::1',1777833282),(46,1,'Login','Auth',NULL,NULL,'::1',1777833285),(47,1,'Logout','Auth',NULL,NULL,'::1',1777833560),(48,3,'Login','Auth',NULL,NULL,'::1',1777833562),(49,3,'Logout','Auth',NULL,NULL,'::1',1777833696),(50,3,'Login','Auth',NULL,NULL,'::1',1777833705),(51,3,'Logout','Auth',NULL,NULL,'::1',1777833707),(52,1,'Login','Auth',NULL,NULL,'::1',1777833709),(53,1,'Logout','Auth',NULL,NULL,'::1',1777833717),(54,3,'Login','Auth',NULL,NULL,'::1',1777833720),(55,3,'Logout','Auth',NULL,NULL,'::1',1777833738),(56,1,'Login','Auth',NULL,NULL,'::1',1777833742),(57,1,'Logout','Auth',NULL,NULL,'::1',1777833858),(58,3,'Login','Auth',NULL,NULL,'::1',1777833860),(59,3,'Logout','Auth',NULL,NULL,'::1',1777834001),(60,1,'Login','Auth',NULL,NULL,'::1',1777834005),(61,1,'Logout','Auth',NULL,NULL,'::1',1777834044),(62,2,'Login','Auth',NULL,NULL,'::1',1777834047),(63,2,'Logout','Auth',NULL,NULL,'::1',1777834290),(64,3,'Login','Auth',NULL,NULL,'::1',1777834301),(65,3,'Logout','Auth',NULL,NULL,'::1',1777834308),(66,1,'Login','Auth',NULL,NULL,'::1',1777834313),(67,1,'Logout','Auth',NULL,NULL,'::1',1777834378),(68,3,'Login','Auth',NULL,NULL,'::1',1777834381),(69,3,'Logout','Auth',NULL,NULL,'::1',1777834413),(70,1,'Login','Auth',NULL,NULL,'::1',1777834416),(71,1,'Logout','Auth',NULL,NULL,'::1',1777835059),(72,3,'Login','Auth',NULL,NULL,'::1',1777835063),(73,3,'Logout','Auth',NULL,NULL,'::1',1777835071),(74,1,'Login','Auth',NULL,NULL,'::1',1777835074),(75,1,'Create Payroll Period','Payroll',2,'dsadsa','::1',1777835267),(76,1,'Process Payroll','Payroll',2,'Generated 17 payslips','::1',1777835280),(77,1,'Create Payroll Period','Payroll',3,'balagbag','::1',1777835314),(78,1,'Process Payroll','Payroll',3,'Generated 17 payslips','::1',1777835337),(79,1,'Create Payroll Period','Payroll',4,'balagbag','::1',1777835391),(80,1,'Process Payroll','Payroll',4,'Generated 17 payslips','::1',1777835394),(81,1,'Create Payroll Period','Payroll',5,'balagbagpt2','::1',1777835439),(82,1,'Process Payroll','Payroll',5,'Generated 17 payslips','::1',1777835442),(83,1,'Logout','Auth',NULL,NULL,'::1',1777835580),(84,3,'Login','Auth',NULL,NULL,'::1',1777835582),(85,3,'Logout','Auth',NULL,NULL,'::1',1777835699),(86,1,'Login','Auth',NULL,NULL,'::1',1777835705),(87,1,'Logout','Auth',NULL,NULL,'::1',1777835724),(88,3,'Login','Auth',NULL,NULL,'::1',1777835727),(89,3,'Logout','Auth',NULL,NULL,'::1',1777835793),(90,1,'Login','Auth',NULL,NULL,'::1',1777835798),(91,1,'Logout','Auth',NULL,NULL,'::1',1777835813),(92,3,'Login','Auth',NULL,NULL,'::1',1777835816),(93,3,'Logout','Auth',NULL,NULL,'::1',1777835954),(94,2,'Login','Auth',NULL,NULL,'::1',1777835957),(95,2,'Logout','Auth',NULL,NULL,'::1',1777835987),(96,3,'Login','Auth',NULL,NULL,'::1',1777835990),(97,3,'Logout','Auth',NULL,NULL,'::1',1777836688),(98,1,'Login','Auth',NULL,NULL,'::1',1777836690),(99,1,'Logout','Auth',NULL,NULL,'::1',1777836763),(100,3,'Login','Auth',NULL,NULL,'::1',1777836766),(101,3,'Logout','Auth',NULL,NULL,'::1',1777836803),(102,1,'Login','Auth',NULL,NULL,'::1',1777836807),(103,1,'Logout','Auth',NULL,NULL,'::1',1777836817),(104,3,'Login','Auth',NULL,NULL,'::1',1777836821),(105,3,'Logout','Auth',NULL,NULL,'::1',1777837051),(106,2,'Login','Auth',NULL,NULL,'::1',1777837054),(107,2,'Logout','Auth',NULL,NULL,'::1',1777837291),(108,3,'Login','Auth',NULL,NULL,'::1',1777837297),(109,3,'Logout','Auth',NULL,NULL,'::1',1777837528),(110,1,'Login','Auth',NULL,NULL,'::1',1777837533),(111,1,'Logout','Auth',NULL,NULL,'::1',1777837547),(112,2,'Login','Auth',NULL,NULL,'::1',1777837549),(113,2,'Logout','Auth',NULL,NULL,'::1',1777837562),(114,3,'Login','Auth',NULL,NULL,'::1',1777837565),(115,3,'Logout','Auth',NULL,NULL,'::1',1777877589),(116,2,'Login','Auth',NULL,NULL,'::1',1777877594),(117,2,'Logout','Auth',NULL,NULL,'::1',1777877612),(118,1,'Login','Auth',NULL,NULL,'::1',1777877616),(119,1,'Create Payroll Period','Payroll',6,'tarantado','::1',1777877635),(120,1,'Process Payroll','Payroll',6,'Generated 17 payslips','::1',1777877641),(121,1,'Create Employee','Employee',18,'REMUS AIMIEL MARASIGAN','::1',1777878381),(122,1,'Logout','Auth',NULL,NULL,'::1',1777878451),(123,3,'Login','Auth',NULL,NULL,'::1',1777878454),(124,3,'Logout','Auth',NULL,NULL,'::1',1777878473),(125,2,'Login','Auth',NULL,NULL,'::1',1777878478),(126,2,'Logout','Auth',NULL,NULL,'::1',1777878483),(127,1,'Login','Auth',NULL,NULL,'::1',1777878486),(128,1,'Delete Employee','Employee',18,NULL,'::1',1777878507),(129,1,'Create Employee','Employee',19,'REMUS AIMIEL MARASIGAN','::1',1777879298),(130,1,'Leave Request Approved','Leave',3,'Status: Approved','::1',1777879716),(131,1,'Leave Request Denied','Leave',4,'Status: Denied','::1',1777879718),(132,1,'Update Department','Department',3,'DevOps & Infrastructure','::1',1777880361),(133,1,'Update Department','Department',7,'doaspdoiadops','::1',1777916222),(134,1,'Logout','Auth',NULL,NULL,'::1',1777916842),(135,3,'Login','Auth',NULL,NULL,'::1',1777916847),(136,3,'Logout','Auth',NULL,NULL,'::1',1777917375),(137,1,'Login','Auth',NULL,NULL,'::1',1777917377),(138,1,'Leave Request Pending','Leave',1,'Status: Pending','::1',1777917387),(139,1,'Leave Request Approved','Leave',1,'Status: Approved','::1',1777917389),(140,1,'Logout','Auth',NULL,NULL,'::1',1777917399),(141,3,'Login','Auth',NULL,NULL,'::1',1777917401),(142,3,'Leave Request Submitted','Leave',3,'Personal Leave: 2026-05-02 to 2026-05-08','::1',1777917440),(143,3,'Logout','Auth',NULL,NULL,'::1',1777917447),(144,3,'Login','Auth',NULL,NULL,'::1',1777917451),(145,3,'Logout','Auth',NULL,NULL,'::1',1777917491),(146,1,'Login','Auth',NULL,NULL,'::1',1777917493),(147,1,'Logout','Auth',NULL,NULL,'::1',1777917868),(148,3,'Login','Auth',NULL,NULL,'::1',1777917872),(149,3,'Logout','Auth',NULL,NULL,'::1',1777918057),(150,3,'Login','Auth',NULL,NULL,'::1',1777918060),(151,3,'Logout','Auth',NULL,NULL,'::1',1777918074),(152,1,'Login','Auth',NULL,NULL,'::1',1777918076),(153,1,'Create Payroll Period','Payroll',7,'balagbagdsd','::1',1777918092),(154,1,'Process Payroll','Payroll',7,'Generated 18 payslips','::1',1777918095),(155,1,'Release Payroll','Payroll',7,'Released to employees','::1',1777918099),(156,1,'Logout','Auth',NULL,NULL,'::1',1777918102),(157,3,'Login','Auth',NULL,NULL,'::1',1777918105),(158,3,'Logout','Auth',NULL,NULL,'::1',1777918126),(159,2,'Login','Auth',NULL,NULL,'::1',1777918129),(160,2,'Logout','Auth',NULL,NULL,'::1',1777918142),(161,1,'Login','Auth',NULL,NULL,'::1',1777918145),(162,1,'Leave Request Approved','Leave',7,'Status: Approved','::1',1777918367),(163,1,'Logout','Auth',NULL,NULL,'::1',1777918372),(164,3,'Login','Auth',NULL,NULL,'::1',1777918375),(165,3,'Logout','Auth',NULL,NULL,'::1',1777918695),(166,1,'Login','Auth',NULL,NULL,'::1',1777918700),(167,1,'Logout','Auth',NULL,NULL,'::1',1777918784),(168,15,'Login','Auth',NULL,NULL,'::1',1777918790),(169,15,'Logout','Auth',NULL,NULL,'::1',1777918801),(170,1,'Login','Auth',NULL,NULL,'::1',1777918805),(171,1,'Reset Password','User Management',7,'Password reset by admin','::1',1777918817),(172,1,'Delete User','User Management',21,NULL,'::1',1777918827),(173,1,'Logout','Auth',NULL,NULL,'::1',1777918829),(174,7,'Login','Auth',NULL,NULL,'::1',1777918837),(175,7,'Logout','Auth',NULL,NULL,'::1',1777918874),(176,3,'Login','Auth',NULL,NULL,'::1',1777918876),(177,3,'Logout','Auth',NULL,NULL,'::1',1777918937),(178,3,'Login','Auth',NULL,NULL,'::1',1777918940),(179,3,'Logout','Auth',NULL,NULL,'::1',1777918967),(180,1,'Login','Auth',NULL,NULL,'::1',1777918970),(181,1,'Logout','Auth',NULL,NULL,'::1',1777919014),(182,1,'Login','Auth',NULL,NULL,'::1',1777919017),(183,1,'Logout','Auth',NULL,NULL,'::1',1777919027),(184,3,'Login','Auth',NULL,NULL,'::1',1777919029),(185,3,'Logout','Auth',NULL,NULL,'::1',1777919123),(186,2,'Login','Auth',NULL,NULL,'::1',1777919127),(187,2,'Logout','Auth',NULL,NULL,'::1',1777919287),(188,3,'Login','Auth',NULL,NULL,'::1',1777919289),(189,3,'Logout','Auth',NULL,NULL,'::1',1777919333),(190,1,'Login','Auth',NULL,NULL,'::1',1777919336),(191,1,'Logout','Auth',NULL,NULL,'::1',1777919363),(192,3,'Login','Auth',NULL,NULL,'::1',1777919366),(193,3,'Logout','Auth',NULL,NULL,'::1',1777919477),(194,1,'Login','Auth',NULL,NULL,'::1',1777919482),(195,1,'Logout','Auth',NULL,NULL,'::1',1777919637),(196,3,'Login','Auth',NULL,NULL,'::1',1777919639),(197,3,'Logout','Auth',NULL,NULL,'::1',1777920484),(198,2,'Login','Auth',NULL,NULL,'::1',1777920488),(199,2,'Logout','Auth',NULL,NULL,'::1',1777920965),(200,3,'Login','Auth',NULL,NULL,'::1',1777920968),(201,3,'Logout','Auth',NULL,NULL,'::1',1777921140),(202,1,'Login','Auth',NULL,NULL,'::1',1777921143),(203,1,'Logout','Auth',NULL,NULL,'::1',1777921250),(204,3,'Login','Auth',NULL,NULL,'::1',1777921252),(205,3,'Leave Request Submitted','Leave',3,'Sick Leave: 2026-05-04 to 2026-05-29','::1',1777921302),(206,3,'Logout','Auth',NULL,NULL,'::1',1777921310),(207,1,'Login','Auth',NULL,NULL,'::1',1777921313),(208,1,'Leave Request Denied','Leave',8,'Status: Denied','::1',1777921330),(209,1,'Logout','Auth',NULL,NULL,'::1',1777921334),(210,3,'Login','Auth',NULL,NULL,'::1',1777921341),(211,3,'Leave Request Submitted','Leave',3,'Bereavement Leave (Immediate Family): 2026-05-05 to 2026-05-11','::1',1777882399),(212,3,'Logout','Auth',NULL,NULL,'::1',1777882406),(213,1,'Login','Auth',NULL,NULL,'::1',1777882409),(214,1,'Leave Request Approved','Leave',9,'Status: Approved','::1',1777882414),(215,1,'Logout','Auth',NULL,NULL,'::1',1777882415),(216,3,'Login','Auth',NULL,NULL,'::1',1777882418),(217,3,'Leave Request Submitted','Leave',3,'Vacation Leave: 2026-05-05 to 2026-05-12','::1',1777882563),(218,3,'Logout','Auth',NULL,NULL,'::1',1777882568),(219,1,'Login','Auth',NULL,NULL,'::1',1777882571),(220,1,'Leave Request Approved','Leave',10,'Status: Approved','::1',1777882575),(221,1,'Logout','Auth',NULL,NULL,'::1',1777882578),(222,3,'Login','Auth',NULL,NULL,'::1',1777882582),(223,3,'Logout','Auth',NULL,NULL,'::1',1777882644),(224,3,'Login','Auth',NULL,NULL,'::1',1777882654),(225,3,'Logout','Auth',NULL,NULL,'::1',1777882667),(226,1,'Login','Auth',NULL,NULL,'::1',1777882673),(227,1,'Logout','Auth',NULL,NULL,'::1',1777882694),(228,3,'Login','Auth',NULL,NULL,'::1',1777882695),(229,3,'Logout','Auth',NULL,NULL,'::1',1777882703),(230,1,'Login','Auth',NULL,NULL,'::1',1777882707),(231,1,'Leave Request Pending','Leave',7,'Status: Pending','::1',1777882742),(232,1,'Logout','Auth',NULL,NULL,'::1',1777882745),(233,3,'Login','Auth',NULL,NULL,'::1',1777882748),(234,3,'Logout','Auth',NULL,NULL,'::1',1777883013),(235,1,'Login','Auth',NULL,NULL,'::1',1777883026),(236,1,'Login','Auth',NULL,NULL,'::1',1777883130),(237,1,'Login','Auth',NULL,NULL,'::1',1777883220),(238,1,'Logout','Auth',NULL,NULL,'::1',1777884528),(239,1,'Logout','Auth',NULL,NULL,'::1',1777884535),(240,1,'Logout','Auth',NULL,NULL,'::1',1777884551),(241,3,'Login','Auth',NULL,NULL,'::1',1777884553),(242,3,'Logout','Auth',NULL,NULL,'::1',1777884601),(243,1,'Login','Auth',NULL,NULL,'::1',1777884604),(244,1,'Create User','User Management',NULL,'sada (Employee)','::1',1777884802),(245,1,'Logout','Auth',NULL,NULL,'::1',1777885010),(246,1,'Login','Auth',NULL,NULL,'::1',1777885013),(247,1,'Logout','Auth',NULL,NULL,'::1',1777885017),(248,3,'Login','Auth',NULL,NULL,'::1',1777885021),(249,3,'Logout','Auth',NULL,NULL,'::1',1777885143),(250,1,'Login','Auth',NULL,NULL,'::1',1777885145),(251,1,'Logout','Auth',NULL,NULL,'::1',1777885245),(252,1,'Login','Auth',NULL,NULL,'::1',1777885251),(253,1,'Create Employee','Employee',21,'ddasd dsad','::1',1777885278),(254,1,'Delete Employee','Employee',21,NULL,'::1',1777885299),(255,1,'Logout','Auth',NULL,NULL,'::1',1777885355),(256,3,'Login','Auth',NULL,NULL,'::1',1777885358),(257,3,'Logout','Auth',NULL,NULL,'::1',1777860751),(258,1,'Login','Auth',NULL,NULL,'::1',1777860753),(259,1,'Logout','Auth',NULL,NULL,'::1',1777860788),(260,3,'Login','Auth',NULL,NULL,'::1',1777860795),(261,3,'Logout','Auth',NULL,NULL,'::1',1777861004),(262,1,'Login','Auth',NULL,NULL,'::1',1777861009),(263,1,'Leave Request Denied','Leave',7,'Status: Denied','::1',1777862612),(264,1,'Leave Request Pending','Leave',7,'Status: Pending','::1',1777862618),(265,1,'Leave Request Approved','Leave',7,'Status: Approved','::1',1777862620),(266,1,'Create Employee','Employee',22,'REMUS AIMIEL MARASIGAN','::1',1777862695);
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `benefit`
--

LOCK TABLES `benefit` WRITE;
/*!40000 ALTER TABLE `benefit` DISABLE KEYS */;
INSERT INTO `benefit` VALUES (1,'HMO Coverage','Comprehensive health maintenance organization coverage','Health',5000.00,1777430555),(2,'SSS','Social Security System mandatory contribution','Government',900.00,1777430555),(3,'PhilHealth','Philippine Health Insurance contribution','Government',400.00,1777430555),(4,'Pag-IBIG Fund','Home Development Mutual Fund contribution','Government',100.00,1777430555),(5,'13th Month Pay','Mandatory 13th month pay','Bonus',0.00,1777430555),(6,'Meal Allowance','Daily meal allowance for onsite work','Allowance',150.00,1777430555),(7,'Transportation Allowance','Monthly transportation subsidy','Allowance',2000.00,1777430555),(8,'Internet Allowance','Monthly internet allowance for WFH days','Allowance',1500.00,1777430555);
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidate`
--

LOCK TABLES `candidate` WRITE;
/*!40000 ALTER TABLE `candidate` DISABLE KEYS */;
INSERT INTO `candidate` VALUES (1,1,'Paolo Villanueva','paolo.v@email.com','09189999001','Interview',70000.00,'2026-04-29',1777430555,1777430555),(2,1,'Christine Mendoza','chris.m@email.com','09189999002','Applied',65000.00,'2026-04-29',1777430555,1777430555),(3,1,'Erwin Buenaventura','erwin.b@email.com','09189999003','Screening',75000.00,'2026-04-29',1777430555,1777430555),(4,2,'Sarah Quiambao','sarah.q@email.com','09189999004','Applied',50000.00,'2026-04-29',1777430555,1777430555),(5,3,'Marco Herrera','marco.h@email.com','09189999005','Interview',55000.00,'2026-04-29',1777430555,1777430555);
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department`
--

LOCK TABLES `department` WRITE;
/*!40000 ALTER TABLE `department` DISABLE KEYS */;
INSERT INTO `department` VALUES (1,'Software Development','Builds and maintains all software products','Makati City',850000.00,1777430555),(2,'QA & Testing','Ensures quality across all deliverables','Makati City',450000.00,1777430555),(3,'DevOps & Infrastructure','Manages cloud, CI/CD, and server infrastructure','Makati City',600000.00,1777430555),(4,'UI/UX Design','Creates user-centered designs and prototypes','BGC, Taguig',380000.00,1777430555),(5,'Project Management','Oversees project delivery and timelines','Makati City',500000.00,1777430555),(6,'HR & Administration','Handles people operations and compliance','Makati City',300000.00,1777430555),(7,'doaspdoiadops','dadwsdwa','Makati City',2000000.00,1777916222);
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
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee`
--

LOCK TABLES `employee` WRITE;
/*!40000 ALTER TABLE `employee` DISABLE KEYS */;
INSERT INTO `employee` VALUES (1,1,'EMP-001','Carlos',NULL,'Reyes',NULL,'Male','admin@cura.ph','09171234561',6,NULL,NULL,'Chief Executive Officer','Full-Time','Regular','2026-04-15',NULL,180000.00,'Married','1985-03-15',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-001-1','12-EMP-001-2','9876-EMP-001-3','111-EMP-001-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(2,2,'EMP-002','Maria',NULL,'Santos',NULL,'Female','hr@cura.ph','09171234562',6,NULL,NULL,'HR Manager','Full-Time','Regular','2026-04-15',NULL,65000.00,'Single','1990-06-20',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-002-1','12-EMP-002-2','9876-EMP-002-3','111-EMP-002-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(3,3,'EMP-003','Juan','','De La Cruz','','Male','juan.delacruz@cura.ph','09171234563',1,NULL,NULL,'Senior Software Engineer','Full-Time','Regular','2026-04-15','2025-10-05',95000.00,'Single','1993-04-12','Angeles City','Filipino',NULL,'','','Metro Manila, Philippines','03-EMP-003-1','12-EMP-003-2','9876-EMP-003-3','111-EMP-003-4',NULL,NULL,'','','',1777430555,1777431712),(4,4,'EMP-004','Ana',NULL,'Garcia',NULL,'Female','ana.garcia@cura.ph','09171234564',1,NULL,NULL,'Software Engineer','Full-Time','Regular','2026-04-15',NULL,62000.00,'Married','1996-07-08',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-004-1','12-EMP-004-2','9876-EMP-004-3','111-EMP-004-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(5,5,'EMP-005','Mark',NULL,'Lim',NULL,'Male','mark.lim@cura.ph','09171234565',1,NULL,NULL,'Junior Developer','Full-Time','Regular','2026-04-17',NULL,28000.00,'Single','1999-11-25',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-005-1','12-EMP-005-2','9876-EMP-005-3','111-EMP-005-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(6,6,'EMP-006','Jenny',NULL,'Tan',NULL,'Female','jenny.tan@cura.ph','09171234566',1,NULL,NULL,'Junior Developer','Full-Time','Regular','2026-04-17',NULL,27500.00,'Single','2000-02-14',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-006-1','12-EMP-006-2','9876-EMP-006-3','111-EMP-006-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(7,7,'EMP-007','Ryan',NULL,'Co',NULL,'Male','ryan.co@cura.ph','09171234567',3,NULL,NULL,'QA Engineer','Full-Time','Regular','2026-04-15',NULL,48000.00,'Single','1994-09-30',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-007-1','12-EMP-007-2','9876-EMP-007-3','111-EMP-007-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(8,8,'EMP-008','Lisa',NULL,'Ong',NULL,'Female','lisa.ong@cura.ph','09171234568',2,NULL,NULL,'QA Engineer','Full-Time','Regular','2026-04-16',NULL,46000.00,'Single','1997-12-05',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-008-1','12-EMP-008-2','9876-EMP-008-3','111-EMP-008-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(9,9,'EMP-009','Ben',NULL,'Wu',NULL,'Male','ben.wu@cura.ph','09171234569',3,NULL,NULL,'DevOps Engineer','Full-Time','Regular','2026-04-15',NULL,80000.00,'Married','1991-08-18',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-009-1','12-EMP-009-2','9876-EMP-009-3','111-EMP-009-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(10,10,'EMP-010','Claire',NULL,'Sy',NULL,'Female','claire.sy@cura.ph','09171234570',4,NULL,NULL,'UI/UX Designer','Full-Time','Regular','2026-04-15',NULL,52000.00,'Single','1995-05-22',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-010-1','12-EMP-010-2','9876-EMP-010-3','111-EMP-010-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(11,11,'EMP-011','Kevin',NULL,'Ng',NULL,'Male','kevin.ng@cura.ph','09171234571',4,NULL,NULL,'UI/UX Designer','Full-Time','Regular','2026-04-17',NULL,50000.00,'Single','1997-01-10',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-011-1','12-EMP-011-2','9876-EMP-011-3','111-EMP-011-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(12,12,'EMP-012','Diana',NULL,'Go',NULL,'Female','diana.go@cura.ph','09171234572',5,NULL,NULL,'Project Manager','Full-Time','Regular','2026-04-15',NULL,75000.00,'Married','1989-03-28',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-012-1','12-EMP-012-2','9876-EMP-012-3','111-EMP-012-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(13,13,'EMP-013','Joel',NULL,'Cruz',NULL,'Male','joel.cruz@cura.ph','09171234573',5,NULL,NULL,'Business Analyst','Full-Time','Regular','2026-04-16',NULL,55000.00,'Single','1992-10-15',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-013-1','12-EMP-013-2','9876-EMP-013-3','111-EMP-013-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(14,14,'EMP-014','Grace',NULL,'Lee',NULL,'Female','grace.lee@cura.ph','09171234574',1,NULL,NULL,'Software Engineer','Full-Time','Regular','2026-04-16',NULL,64000.00,'Single','1994-06-11',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-014-1','12-EMP-014-2','9876-EMP-014-3','111-EMP-014-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(15,15,'EMP-015','Mike',NULL,'Chan',NULL,'Male','mike.chan@cura.ph','09171234575',3,NULL,NULL,'DevOps Engineer','Full-Time','Regular','2026-04-16',NULL,78000.00,'Married','1990-12-03',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-015-1','12-EMP-015-2','9876-EMP-015-3','111-EMP-015-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(16,16,'EMP-016','Kim',NULL,'Ramos',NULL,'Female','kim.ramos@cura.ph','09171234576',6,NULL,NULL,'HR & Admin Officer','Full-Time','Regular','2026-04-18',NULL,35000.00,'Single','1998-04-19',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-016-1','12-EMP-016-2','9876-EMP-016-3','111-EMP-016-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(17,17,'EMP-017','Dante',NULL,'Flores',NULL,'Male','dante.flores@cura.ph','09171234577',1,NULL,NULL,'Junior Developer','Full-Time','Regular','2026-04-19',NULL,28000.00,'Single','2001-07-07',NULL,'Filipino',NULL,NULL,NULL,'Metro Manila, Philippines','03-EMP-017-1','12-EMP-017-2','9876-EMP-017-3','111-EMP-017-4',NULL,NULL,NULL,NULL,NULL,1777430555,1777430555),(20,22,NULL,'Sada',NULL,'',NULL,NULL,'adasd@cura.ph',NULL,NULL,NULL,NULL,NULL,'Full-Time','Probationary',NULL,NULL,0.00,NULL,NULL,NULL,'Filipino',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1777884802,1777884802),(22,24,'EMP-2026-0019','REMUS AIMIEL',NULL,'MARASIGAN',NULL,NULL,'asdjspdjas@cura.ph',NULL,6,2,NULL,NULL,'Full-Time','Probationary',NULL,NULL,0.00,'Single',NULL,NULL,'Filipino',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1777862695,1777862695);
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
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_benefit`
--

LOCK TABLES `employee_benefit` WRITE;
/*!40000 ALTER TABLE `employee_benefit` DISABLE KEYS */;
INSERT INTO `employee_benefit` VALUES (1,1,1,'2026-04-29'),(2,1,2,'2026-04-29'),(3,1,3,'2026-04-29'),(4,1,4,'2026-04-29'),(5,1,5,'2026-04-29'),(6,1,6,'2026-04-29'),(7,1,7,'2026-04-29'),(8,1,8,'2026-04-29'),(9,2,1,'2026-04-29'),(10,2,2,'2026-04-29'),(11,2,3,'2026-04-29'),(12,2,4,'2026-04-29'),(13,2,5,'2026-04-29'),(14,2,6,'2026-04-29'),(15,2,7,'2026-04-29'),(16,2,8,'2026-04-29'),(17,3,1,'2026-04-29'),(18,3,2,'2026-04-29'),(19,3,3,'2026-04-29'),(20,3,4,'2026-04-29'),(21,3,5,'2026-04-29'),(22,3,6,'2026-04-29'),(23,3,7,'2026-04-29'),(24,3,8,'2026-04-29'),(25,4,1,'2026-04-29'),(26,4,2,'2026-04-29'),(27,4,3,'2026-04-29'),(28,4,4,'2026-04-29'),(29,4,5,'2026-04-29'),(30,4,6,'2026-04-29'),(31,4,7,'2026-04-29'),(32,4,8,'2026-04-29'),(33,5,1,'2026-04-29'),(34,5,2,'2026-04-29'),(35,5,3,'2026-04-29'),(36,5,4,'2026-04-29'),(37,5,5,'2026-04-29'),(38,5,6,'2026-04-29'),(39,5,7,'2026-04-29'),(40,5,8,'2026-04-29'),(41,6,1,'2026-04-29'),(42,6,2,'2026-04-29'),(43,6,3,'2026-04-29'),(44,6,4,'2026-04-29'),(45,6,5,'2026-04-29'),(46,6,6,'2026-04-29'),(47,6,7,'2026-04-29'),(48,6,8,'2026-04-29'),(49,7,1,'2026-04-29'),(50,7,2,'2026-04-29'),(51,7,3,'2026-04-29'),(52,7,4,'2026-04-29'),(53,7,5,'2026-04-29'),(54,7,6,'2026-04-29'),(55,7,7,'2026-04-29'),(56,7,8,'2026-04-29'),(57,8,1,'2026-04-29'),(58,8,2,'2026-04-29'),(59,8,3,'2026-04-29'),(60,8,4,'2026-04-29'),(61,8,5,'2026-04-29'),(62,8,6,'2026-04-29'),(63,8,7,'2026-04-29'),(64,8,8,'2026-04-29'),(65,9,1,'2026-04-29'),(66,9,2,'2026-04-29'),(67,9,3,'2026-04-29'),(68,9,4,'2026-04-29'),(69,9,5,'2026-04-29'),(70,9,6,'2026-04-29'),(71,9,7,'2026-04-29'),(72,9,8,'2026-04-29'),(73,10,1,'2026-04-29'),(74,10,2,'2026-04-29'),(75,10,3,'2026-04-29'),(76,10,4,'2026-04-29'),(77,10,5,'2026-04-29'),(78,10,6,'2026-04-29'),(79,10,7,'2026-04-29'),(80,10,8,'2026-04-29'),(81,11,1,'2026-04-29'),(82,11,2,'2026-04-29'),(83,11,3,'2026-04-29'),(84,11,4,'2026-04-29'),(85,11,5,'2026-04-29'),(86,11,6,'2026-04-29'),(87,11,7,'2026-04-29'),(88,11,8,'2026-04-29'),(89,12,1,'2026-04-29'),(90,12,2,'2026-04-29'),(91,12,3,'2026-04-29'),(92,12,4,'2026-04-29'),(93,12,5,'2026-04-29'),(94,12,6,'2026-04-29'),(95,12,7,'2026-04-29'),(96,12,8,'2026-04-29'),(97,13,1,'2026-04-29'),(98,13,2,'2026-04-29'),(99,13,3,'2026-04-29'),(100,13,4,'2026-04-29'),(101,13,5,'2026-04-29'),(102,13,6,'2026-04-29'),(103,13,7,'2026-04-29'),(104,13,8,'2026-04-29'),(105,14,1,'2026-04-29'),(106,14,2,'2026-04-29'),(107,14,3,'2026-04-29'),(108,14,4,'2026-04-29'),(109,14,5,'2026-04-29'),(110,14,6,'2026-04-29'),(111,14,7,'2026-04-29'),(112,14,8,'2026-04-29'),(113,15,1,'2026-04-29'),(114,15,2,'2026-04-29'),(115,15,3,'2026-04-29'),(116,15,4,'2026-04-29'),(117,15,5,'2026-04-29'),(118,15,6,'2026-04-29'),(119,15,7,'2026-04-29'),(120,15,8,'2026-04-29'),(121,16,1,'2026-04-29'),(122,16,2,'2026-04-29'),(123,16,3,'2026-04-29'),(124,16,4,'2026-04-29'),(125,16,5,'2026-04-29'),(126,16,6,'2026-04-29'),(127,16,7,'2026-04-29'),(128,16,8,'2026-04-29'),(129,17,1,'2026-04-29'),(130,17,2,'2026-04-29'),(131,17,3,'2026-04-29'),(132,17,4,'2026-04-29'),(133,17,5,'2026-04-29'),(134,17,6,'2026-04-29'),(135,17,7,'2026-04-29'),(136,17,8,'2026-04-29');
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
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_doc_checklist`
--

LOCK TABLES `employee_doc_checklist` WRITE;
/*!40000 ALTER TABLE `employee_doc_checklist` DISABLE KEYS */;
INSERT INTO `employee_doc_checklist` VALUES (1,3,'Employment Contract','Pending',NULL,NULL,1777430695,1777430695),(2,3,'SSS E1 Form / SS Card','Pending',NULL,NULL,1777430695,1777430695),(3,3,'PhilHealth MDR (Member Data Record)','Pending',NULL,NULL,1777430695,1777430695),(4,3,'Pag-IBIG MDF (Membership Data Form)','Pending',NULL,NULL,1777430695,1777430695),(5,3,'BIR Form 2316 / TIN Card','Pending',NULL,NULL,1777430695,1777430695),(6,3,'NBI Clearance','Pending',NULL,NULL,1777430695,1777430695),(7,3,'Birth Certificate (PSA-authenticated)','Pending',NULL,NULL,1777430695,1777430695),(8,3,'Medical Certificate','Pending',NULL,NULL,1777430695,1777430695),(9,3,'Diploma / Transcript of Records','Pending',NULL,NULL,1777430695,1777430695),(10,3,'2×2 ID Photo (white background)','Pending',NULL,NULL,1777430695,1777430695),(31,20,'Employment Contract','Pending',NULL,NULL,1777884802,1777884802),(32,20,'SSS E1 Form / SS Card','Pending',NULL,NULL,1777884802,1777884802),(33,20,'PhilHealth MDR','Pending',NULL,NULL,1777884802,1777884802),(34,20,'Pag-IBIG MDF','Pending',NULL,NULL,1777884802,1777884802),(35,20,'BIR Form 2316 / TIN Card','Pending',NULL,NULL,1777884802,1777884802),(36,20,'NBI Clearance','Pending',NULL,NULL,1777884802,1777884802),(37,20,'Birth Certificate (PSA)','Pending',NULL,NULL,1777884802,1777884802),(38,20,'Medical Certificate','Pending',NULL,NULL,1777884802,1777884802),(39,20,'Diploma / Transcript of Records','Pending',NULL,NULL,1777884802,1777884802),(40,20,'2×2 ID Photo','Pending',NULL,NULL,1777884802,1777884802),(51,22,'Employment Contract','Pending',NULL,NULL,1777862695,1777862695),(52,22,'SSS E1 Form / SS Card','Pending',NULL,NULL,1777862695,1777862695),(53,22,'PhilHealth MDR','Pending',NULL,NULL,1777862695,1777862695),(54,22,'Pag-IBIG MDF','Pending',NULL,NULL,1777862695,1777862695),(55,22,'BIR Form 2316 / TIN Card','Pending',NULL,NULL,1777862695,1777862695),(56,22,'NBI Clearance','Pending',NULL,NULL,1777862695,1777862695),(57,22,'Birth Certificate (PSA)','Pending',NULL,NULL,1777862695,1777862695),(58,22,'Medical Certificate','Pending',NULL,NULL,1777862695,1777862695),(59,22,'Diploma / Transcript of Records','Pending',NULL,NULL,1777862695,1777862695),(60,22,'2\\u00d72 ID Photo','Pending',NULL,NULL,1777862695,1777862695);
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES (1,3,4,'Great work on the auth module!','positive',0,1777430555),(2,12,5,'You are progressing well. Keep pushing!','constructive',0,1777430555),(3,9,15,'Docker setup was flawless. Very efficient.','positive',0,1777430555),(4,2,16,'Please submit government forms before deadline.','constructive',0,1777430555),(5,NULL,1,'Suggestion: provide standing desks for the dev team.','suggestion',1,1777430555),(6,2,3,'TANAYDAMO SAMASNAN MU ','constructive',0,1777835977);
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holiday`
--

LOCK TABLES `holiday` WRITE;
/*!40000 ALTER TABLE `holiday` DISABLE KEYS */;
INSERT INTO `holiday` VALUES (1,'New Year Day','2026-01-01','Regular',1,1777430555),(2,'EDSA People Power Revolution','2026-02-25','Special Non-Working',1,1777430555),(3,'Maundy Thursday','2026-04-02','Regular',0,1777430555),(4,'Good Friday','2026-04-03','Regular',0,1777430555),(5,'Araw ng Kagitingan','2026-04-09','Regular',1,1777430555),(6,'Labor Day','2026-05-01','Regular',1,1777430555),(7,'Independence Day','2026-06-12','Regular',1,1777430555),(8,'Ninoy Aquino Day','2026-08-21','Special Non-Working',1,1777430555),(9,'National Heroes Day','2026-08-31','Regular',1,1777430555),(10,'All Saints Day','2026-11-01','Special Non-Working',1,1777430555),(11,'Bonifacio Day','2026-11-30','Regular',1,1777430555),(12,'Feast of Immaculate Conception','2026-12-08','Special Non-Working',1,1777430555),(13,'Christmas Day','2026-12-25','Regular',1,1777430555),(14,'Rizal Day','2026-12-30','Regular',1,1777430555);
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_posting`
--

LOCK TABLES `job_posting` WRITE;
/*!40000 ALTER TABLE `job_posting` DISABLE KEYS */;
INSERT INTO `job_posting` VALUES (1,'Full-Stack Developer',1,'We are looking for a talented Full-Stack Developer.','3+ years in React, Node.js or PHP. Strong SQL.',60000.00,120000.00,'Makati City','Full-time','Open',1,1777430555,1777430555),(2,'QA Automation Engineer',2,'Build and maintain automated test suites.','2+ years in Selenium/Cypress. CI/CD knowledge.',45000.00,75000.00,'Makati City','Full-time','Open',1,1777430555,1777430555),(3,'Product Designer',4,'Create beautiful user-centered designs.','Portfolio required. Figma proficiency. 2+ years.',40000.00,70000.00,'BGC, Taguig','Full-time','Open',1,1777430555,1777430555);
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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balance`
--

LOCK TABLES `leave_balance` WRITE;
/*!40000 ALTER TABLE `leave_balance` DISABLE KEYS */;
INSERT INTO `leave_balance` VALUES (1,2,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777920732),(2,3,2026,6.25,0,6.00,0.00,0,5.0,0.0,0,0.0,0,0.0,1777882716),(3,4,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777921182),(4,9,2026,2.25,1,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777862607),(5,1,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777921182),(6,10,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777921182),(7,17,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777921182),(8,12,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777921182),(9,14,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777921182),(10,6,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777921182),(11,13,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777921182),(12,11,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777921182),(13,16,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777921182),(14,8,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777921182),(15,5,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777921182),(16,15,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777921182),(17,7,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777921182),(18,20,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777884901),(19,22,2026,0.00,0,0.00,0.00,0,0.0,0.0,0,0.0,0,0.0,1777864771);
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_request`
--

LOCK TABLES `leave_request` WRITE;
/*!40000 ALTER TABLE `leave_request` DISABLE KEYS */;
INSERT INTO `leave_request` VALUES (1,5,'Sick Leave','2026-04-26','2026-04-26',1.0,'Fever and flu',NULL,NULL,'Approved',1,1777430555,1777917389),(2,8,'Personal Leave','2026-04-28','2026-04-28',1.0,'Personal errand',NULL,NULL,'Approved',2,1777430555,1777430555),(3,6,'Vacation Leave','2026-05-02','2026-05-06',5.0,'Family vacation',NULL,NULL,'Approved',1,1777430555,1777879716),(4,7,'Sick Leave','2026-04-30','2026-04-30',1.0,'Medical appointment',NULL,NULL,'Denied',1,1777430555,1777879718),(5,10,'Vacation Leave','2026-04-19','2026-04-21',3.0,'Rest',NULL,NULL,'Approved',2,1777430555,1777430555),(6,12,'Emergency Leave','2026-04-24','2026-04-24',1.0,'Family emergency',NULL,NULL,'Approved',2,1777430555,1777430555),(7,3,'Personal Leave','2026-05-02','2026-05-08',7.0,'outing',NULL,'Without Pay','Approved',1,1777917440,1777862620),(8,3,'Sick Leave','2026-05-04','2026-05-29',26.0,'',NULL,NULL,'Denied',1,1777921302,1777921330),(9,3,'Bereavement Leave (Immediate Family)','2026-05-05','2026-05-11',5.0,'',NULL,'Bereavement','Approved',1,1777882399,1777882414),(10,3,'Vacation Leave','2026-05-05','2026-05-12',6.0,'',NULL,'SIL','Approved',1,1777882563,1777882575);
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
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lms_enrollment`
--

LOCK TABLES `lms_enrollment` WRITE;
/*!40000 ALTER TABLE `lms_enrollment` DISABLE KEYS */;
INSERT INTO `lms_enrollment` VALUES (1,1,1,31,'In Progress',NULL,1777430555,1777430555),(2,2,1,56,'In Progress',NULL,1777430555,1777430555),(3,3,1,67,'In Progress',NULL,1777430555,1777430555),(4,4,1,29,'In Progress',NULL,1777430555,1777430555),(5,5,1,33,'In Progress',NULL,1777430555,1777430555),(6,6,1,0,'Not Started',NULL,1777430555,1777430555),(7,7,1,99,'In Progress',NULL,1777430555,1777430555),(8,8,1,8,'In Progress',NULL,1777430555,1777430555),(9,1,2,100,'Completed',1777430555,1777430555,1777430555),(10,2,2,23,'In Progress',NULL,1777430555,1777430555),(11,3,2,49,'In Progress',NULL,1777430555,1777430555),(12,4,2,89,'In Progress',NULL,1777430555,1777430555),(13,5,2,91,'In Progress',NULL,1777430555,1777430555),(14,6,2,0,'Not Started',NULL,1777430555,1777430555),(15,7,2,35,'In Progress',NULL,1777430555,1777430555),(16,8,2,25,'In Progress',NULL,1777430555,1777430555),(17,1,3,75,'In Progress',NULL,1777430555,1777430555),(18,2,3,90,'In Progress',NULL,1777430555,1777430555),(19,3,3,44,'In Progress',NULL,1777430555,1777430555),(20,4,3,98,'In Progress',NULL,1777430555,1777430555),(21,5,3,93,'In Progress',NULL,1777430555,1777430555),(22,6,3,47,'In Progress',NULL,1777430555,1777430555),(23,7,3,69,'In Progress',NULL,1777430555,1777430555),(24,8,3,54,'In Progress',NULL,1777430555,1777430555),(25,1,4,95,'In Progress',NULL,1777430555,1777430555),(26,2,4,79,'In Progress',NULL,1777430555,1777430555),(27,3,4,78,'In Progress',NULL,1777430555,1777430555),(28,4,4,80,'In Progress',NULL,1777430555,1777430555),(29,5,4,86,'In Progress',NULL,1777430555,1777430555),(30,6,4,17,'In Progress',NULL,1777430555,1777430555),(31,7,4,36,'In Progress',NULL,1777430555,1777430555),(32,8,4,32,'In Progress',NULL,1777430555,1777430555),(33,1,5,86,'In Progress',NULL,1777430555,1777430555),(34,2,5,63,'In Progress',NULL,1777430555,1777430555),(35,3,5,77,'In Progress',NULL,1777430555,1777430555),(36,4,5,9,'In Progress',NULL,1777430555,1777430555),(37,5,5,21,'In Progress',NULL,1777430555,1777430555),(38,6,5,71,'In Progress',NULL,1777430555,1777430555),(39,7,5,47,'In Progress',NULL,1777430555,1777430555),(40,8,5,58,'In Progress',NULL,1777430555,1777430555),(41,1,6,9,'In Progress',NULL,1777430555,1777430555),(42,2,6,88,'In Progress',NULL,1777430555,1777430555),(43,3,6,98,'In Progress',NULL,1777430555,1777430555),(44,4,6,16,'In Progress',NULL,1777430555,1777430555),(45,5,6,47,'In Progress',NULL,1777430555,1777430555),(46,6,6,100,'Completed',1777430555,1777430555,1777430555),(47,7,6,80,'In Progress',NULL,1777430555,1777430555),(48,8,6,0,'Not Started',NULL,1777430555,1777430555),(49,1,7,47,'In Progress',NULL,1777430555,1777430555),(50,2,7,71,'In Progress',NULL,1777430555,1777430555),(51,3,7,29,'In Progress',NULL,1777430555,1777430555),(52,4,7,97,'In Progress',NULL,1777430555,1777430555),(53,5,7,57,'In Progress',NULL,1777430555,1777430555),(54,6,7,6,'In Progress',NULL,1777430555,1777430555),(55,7,7,64,'In Progress',NULL,1777430555,1777430555),(56,8,7,33,'In Progress',NULL,1777430555,1777430555),(57,1,8,7,'In Progress',NULL,1777430555,1777430555),(58,2,8,30,'In Progress',NULL,1777430555,1777430555),(59,3,8,12,'In Progress',NULL,1777430555,1777430555),(60,4,8,25,'In Progress',NULL,1777430555,1777430555),(61,5,8,81,'In Progress',NULL,1777430555,1777430555),(62,6,8,82,'In Progress',NULL,1777430555,1777430555),(63,7,8,67,'In Progress',NULL,1777430555,1777430555),(64,8,8,13,'In Progress',NULL,1777430555,1777430555),(65,1,9,4,'In Progress',NULL,1777430555,1777430555),(66,2,9,49,'In Progress',NULL,1777430555,1777430555),(67,3,9,23,'In Progress',NULL,1777430555,1777430555),(68,4,9,97,'In Progress',NULL,1777430555,1777430555),(69,5,9,89,'In Progress',NULL,1777430555,1777430555),(70,6,9,2,'In Progress',NULL,1777430555,1777430555),(71,7,9,0,'Not Started',NULL,1777430555,1777430555),(72,8,9,97,'In Progress',NULL,1777430555,1777430555),(73,1,10,3,'In Progress',NULL,1777430555,1777430555),(74,2,10,77,'In Progress',NULL,1777430555,1777430555),(75,3,10,10,'In Progress',NULL,1777430555,1777430555),(76,4,10,79,'In Progress',NULL,1777430555,1777430555),(77,5,10,26,'In Progress',NULL,1777430555,1777430555),(78,6,10,27,'In Progress',NULL,1777430555,1777430555),(79,7,10,70,'In Progress',NULL,1777430555,1777430555),(80,8,10,21,'In Progress',NULL,1777430555,1777430555),(81,1,11,67,'In Progress',NULL,1777430555,1777430555),(82,2,11,92,'In Progress',NULL,1777430555,1777430555),(83,3,11,67,'In Progress',NULL,1777430555,1777430555),(84,4,11,35,'In Progress',NULL,1777430555,1777430555),(85,5,11,78,'In Progress',NULL,1777430555,1777430555),(86,6,11,15,'In Progress',NULL,1777430555,1777430555),(87,7,11,12,'In Progress',NULL,1777430555,1777430555),(88,8,11,81,'In Progress',NULL,1777430555,1777430555),(89,1,12,58,'In Progress',NULL,1777430555,1777430555),(90,2,12,88,'In Progress',NULL,1777430555,1777430555),(91,3,12,56,'In Progress',NULL,1777430555,1777430555),(92,4,12,25,'In Progress',NULL,1777430555,1777430555),(93,5,12,11,'In Progress',NULL,1777430555,1777430555),(94,6,12,99,'In Progress',NULL,1777430555,1777430555),(95,7,12,56,'In Progress',NULL,1777430555,1777430555),(96,8,12,2,'In Progress',NULL,1777430555,1777430555),(97,1,13,57,'In Progress',NULL,1777430555,1777430555),(98,2,13,31,'In Progress',NULL,1777430555,1777430555),(99,3,13,92,'In Progress',NULL,1777430555,1777430555),(100,4,13,67,'In Progress',NULL,1777430555,1777430555),(101,5,13,48,'In Progress',NULL,1777430555,1777430555),(102,6,13,60,'In Progress',NULL,1777430555,1777430555),(103,7,13,27,'In Progress',NULL,1777430555,1777430555),(104,8,13,23,'In Progress',NULL,1777430555,1777430555),(105,1,14,28,'In Progress',NULL,1777430555,1777430555),(106,2,14,32,'In Progress',NULL,1777430555,1777430555),(107,3,14,5,'In Progress',NULL,1777430555,1777430555),(108,4,14,78,'In Progress',NULL,1777430555,1777430555),(109,5,14,21,'In Progress',NULL,1777430555,1777430555),(110,6,14,99,'In Progress',NULL,1777430555,1777430555),(111,7,14,63,'In Progress',NULL,1777430555,1777430555),(112,8,14,94,'In Progress',NULL,1777430555,1777430555),(113,1,15,23,'In Progress',NULL,1777430555,1777430555),(114,2,15,30,'In Progress',NULL,1777430555,1777430555),(115,3,15,56,'In Progress',NULL,1777430555,1777430555),(116,4,15,32,'In Progress',NULL,1777430555,1777430555),(117,5,15,89,'In Progress',NULL,1777430555,1777430555),(118,6,15,46,'In Progress',NULL,1777430555,1777430555),(119,7,15,56,'In Progress',NULL,1777430555,1777430555),(120,8,15,44,'In Progress',NULL,1777430555,1777430555),(121,1,16,36,'In Progress',NULL,1777430555,1777430555),(122,2,16,60,'In Progress',NULL,1777430555,1777430555),(123,3,16,96,'In Progress',NULL,1777430555,1777430555),(124,4,16,81,'In Progress',NULL,1777430555,1777430555),(125,5,16,96,'In Progress',NULL,1777430555,1777430555),(126,6,16,68,'In Progress',NULL,1777430555,1777430555),(127,7,16,16,'In Progress',NULL,1777430555,1777430555),(128,8,16,98,'In Progress',NULL,1777430555,1777430555),(129,1,17,79,'In Progress',NULL,1777430555,1777430555),(130,2,17,69,'In Progress',NULL,1777430555,1777430555),(131,3,17,12,'In Progress',NULL,1777430555,1777430555),(132,4,17,94,'In Progress',NULL,1777430555,1777430555),(133,5,17,8,'In Progress',NULL,1777430555,1777430555),(134,6,17,62,'In Progress',NULL,1777430555,1777430555),(135,7,17,51,'In Progress',NULL,1777430555,1777430555),(136,8,17,12,'In Progress',NULL,1777430555,1777430555);
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lms_module`
--

LOCK TABLES `lms_module` WRITE;
/*!40000 ALTER TABLE `lms_module` DISABLE KEYS */;
INSERT INTO `lms_module` VALUES (1,'Company Culture & Values','Introduction to CURA values, vision and mission','document',45,6,1,1777430555,1777430555),(2,'Information Security Basics','Mandatory cybersecurity awareness training','document',60,NULL,1,1777430555,1777430555),(3,'Git & Version Control','Practical guide to Git workflows and PRs','video',90,1,1,1777430555,1777430555),(4,'Agile & Scrum Fundamentals','Introduction to Agile methodology and Scrum','document',120,NULL,1,1777430555,1777430555),(5,'Figma for Designers','Hands-on Figma fundamentals and prototyping','video',180,4,1,1777430555,1777430555),(6,'Docker & Kubernetes Basics','Container orchestration essentials','video',150,3,1,1777430555,1777430555),(7,'QA Best Practices','Test planning, bug reporting, regression testing','document',90,2,1,1777430555,1777430555),(8,'Data Privacy Act Compliance','Understanding RA 10173 in the workplace','document',60,NULL,1,1777430555,1777430555);
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
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onboarding_task`
--

LOCK TABLES `onboarding_task` WRITE;
/*!40000 ALTER TABLE `onboarding_task` DISABLE KEYS */;
INSERT INTO `onboarding_task` VALUES (1,1,'Submit government ID copies','Pre-boarding','Completed','2026-05-07',NULL,1777430555,1777430555),(2,1,'Sign employment contract','Pre-boarding','Completed','2026-05-03',NULL,1777430555,1777430555),(3,1,'Complete personal data sheet','Pre-boarding','Completed','2026-05-01',NULL,1777430555,1777430555),(4,1,'Setup company email and accounts','Week 1','Completed','2026-05-12',NULL,1777430555,1777430555),(5,1,'Meet department team members','Week 1','Completed','2026-05-11',NULL,1777430555,1777430555),(6,1,'Complete Info Security training','Week 1','In Progress','2026-05-12',NULL,1777430555,1777430555),(7,1,'Setup development environment','Week 1','In Progress','2026-04-30',NULL,1777430555,1777430555),(8,1,'Read company handbook','Week 1','Completed','2026-05-09',NULL,1777430555,1777430555),(9,2,'Submit government ID copies','Pre-boarding','Completed','2026-05-03',NULL,1777430555,1777430555),(10,2,'Sign employment contract','Pre-boarding','Completed','2026-05-01',NULL,1777430555,1777430555),(11,2,'Complete personal data sheet','Pre-boarding','Completed','2026-05-09',NULL,1777430555,1777430555),(12,2,'Setup company email and accounts','Week 1','Completed','2026-05-08',NULL,1777430555,1777430555),(13,2,'Meet department team members','Week 1','Completed','2026-05-01',NULL,1777430555,1777430555),(14,2,'Complete Info Security training','Week 1','In Progress','2026-05-03',NULL,1777430555,1777430555),(15,2,'Setup development environment','Week 1','In Progress','2026-05-02',NULL,1777430555,1777430555),(16,2,'Read company handbook','Week 1','Completed','2026-05-03',NULL,1777430555,1777430555),(17,3,'Submit government ID copies','Pre-boarding','Completed','2026-05-11',NULL,1777430555,1777430555),(18,3,'Sign employment contract','Pre-boarding','Completed','2026-05-09',NULL,1777430555,1777430555),(19,3,'Complete personal data sheet','Pre-boarding','Completed','2026-05-10',NULL,1777430555,1777430555),(20,3,'Setup company email and accounts','Week 1','Completed','2026-05-03',NULL,1777430555,1777430555),(21,3,'Meet department team members','Week 1','Completed','2026-05-02',NULL,1777430555,1777430555),(22,3,'Complete Info Security training','Week 1','In Progress','2026-05-02',NULL,1777430555,1777430555),(23,3,'Setup development environment','Week 1','In Progress','2026-04-30',NULL,1777430555,1777430555),(24,3,'Read company handbook','Week 1','Completed','2026-05-03',NULL,1777430555,1777430555),(25,4,'Submit government ID copies','Pre-boarding','Completed','2026-04-30',NULL,1777430555,1777430555),(26,4,'Sign employment contract','Pre-boarding','Completed','2026-05-01',NULL,1777430555,1777430555),(27,4,'Complete personal data sheet','Pre-boarding','Completed','2026-05-11',NULL,1777430555,1777430555),(28,4,'Setup company email and accounts','Week 1','Completed','2026-05-05',NULL,1777430555,1777430555),(29,4,'Meet department team members','Week 1','Completed','2026-05-11',NULL,1777430555,1777430555),(30,4,'Complete Info Security training','Week 1','In Progress','2026-05-08',NULL,1777430555,1777430555),(31,4,'Setup development environment','Week 1','In Progress','2026-05-03',NULL,1777430555,1777430555),(32,4,'Read company handbook','Week 1','Completed','2026-05-11',NULL,1777430555,1777430555),(33,5,'Submit government ID copies','Pre-boarding','Completed','2026-05-04',NULL,1777430555,1777430555),(34,5,'Sign employment contract','Pre-boarding','Completed','2026-05-01',NULL,1777430555,1777430555),(35,5,'Complete personal data sheet','Pre-boarding','Completed','2026-05-04',NULL,1777430555,1777430555),(36,5,'Setup company email and accounts','Week 1','Completed','2026-05-13',NULL,1777430555,1777430555),(37,5,'Meet department team members','Week 1','Completed','2026-05-09',NULL,1777430555,1777430555),(38,5,'Complete Info Security training','Week 1','In Progress','2026-05-09',NULL,1777430555,1777430555),(39,5,'Setup development environment','Week 1','In Progress','2026-05-05',NULL,1777430555,1777430555),(40,5,'Read company handbook','Week 1','Completed','2026-05-05',NULL,1777430555,1777430555),(41,5,'Meet with direct supervisor','Week 2','Completed','2026-05-04',NULL,1777430555,1777430555),(42,5,'Complete first project assignment','Week 2','In Progress','2026-05-07',NULL,1777430555,1777430555),(43,5,'Submit SSS/PhilHealth/Pag-IBIG forms','Week 2','Not Started','2026-05-03',NULL,1777430555,1777430555),(44,5,'Complete HMO enrollment','Integration','Not Started','2026-05-05',NULL,1777430555,1777430555),(45,6,'Submit government ID copies','Pre-boarding','Completed','2026-05-08',NULL,1777430555,1777430555),(46,6,'Sign employment contract','Pre-boarding','Completed','2026-05-09',NULL,1777430555,1777430555),(47,6,'Complete personal data sheet','Pre-boarding','Completed','2026-05-12',NULL,1777430555,1777430555),(48,6,'Setup company email and accounts','Week 1','Completed','2026-04-30',NULL,1777430555,1777430555),(49,6,'Meet department team members','Week 1','Completed','2026-05-09',NULL,1777430555,1777430555),(50,6,'Complete Info Security training','Week 1','In Progress','2026-05-09',NULL,1777430555,1777430555),(51,6,'Setup development environment','Week 1','In Progress','2026-05-11',NULL,1777430555,1777430555),(52,6,'Read company handbook','Week 1','Completed','2026-05-08',NULL,1777430555,1777430555),(53,6,'Meet with direct supervisor','Week 2','Completed','2026-05-07',NULL,1777430555,1777430555),(54,6,'Complete first project assignment','Week 2','In Progress','2026-05-10',NULL,1777430555,1777430555),(55,6,'Submit SSS/PhilHealth/Pag-IBIG forms','Week 2','Not Started','2026-05-06',NULL,1777430555,1777430555),(56,6,'Complete HMO enrollment','Integration','Not Started','2026-05-03',NULL,1777430555,1777430555),(57,7,'Submit government ID copies','Pre-boarding','Completed','2026-05-12',NULL,1777430555,1777430555),(58,7,'Sign employment contract','Pre-boarding','Completed','2026-05-12',NULL,1777430555,1777430555),(59,7,'Complete personal data sheet','Pre-boarding','Completed','2026-05-05',NULL,1777430555,1777430555),(60,7,'Setup company email and accounts','Week 1','Completed','2026-05-12',NULL,1777430555,1777430555),(61,7,'Meet department team members','Week 1','Completed','2026-05-03',NULL,1777430555,1777430555),(62,7,'Complete Info Security training','Week 1','In Progress','2026-04-30',NULL,1777430555,1777430555),(63,7,'Setup development environment','Week 1','In Progress','2026-05-08',NULL,1777430555,1777430555),(64,7,'Read company handbook','Week 1','Completed','2026-05-12',NULL,1777430555,1777430555),(65,7,'Meet with direct supervisor','Week 2','Completed','2026-05-13',NULL,1777430555,1777430555),(66,7,'Complete first project assignment','Week 2','In Progress','2026-05-11',NULL,1777430555,1777430555),(67,7,'Submit SSS/PhilHealth/Pag-IBIG forms','Week 2','Not Started','2026-05-08',NULL,1777430555,1777430555),(68,7,'Complete HMO enrollment','Integration','Not Started','2026-05-04',NULL,1777430555,1777430555),(69,8,'Submit government ID copies','Pre-boarding','Completed','2026-05-03',NULL,1777430555,1777430555),(70,8,'Sign employment contract','Pre-boarding','Completed','2026-05-05',NULL,1777430555,1777430555),(71,8,'Complete personal data sheet','Pre-boarding','Completed','2026-05-11',NULL,1777430555,1777430555),(72,8,'Setup company email and accounts','Week 1','Completed','2026-05-02',NULL,1777430555,1777430555),(73,8,'Meet department team members','Week 1','Completed','2026-05-07',NULL,1777430555,1777430555),(74,8,'Complete Info Security training','Week 1','In Progress','2026-05-09',NULL,1777430555,1777430555),(75,8,'Setup development environment','Week 1','In Progress','2026-05-12',NULL,1777430555,1777430555),(76,8,'Read company handbook','Week 1','Completed','2026-05-08',NULL,1777430555,1777430555),(77,9,'Submit government ID copies','Pre-boarding','Completed','2026-05-04',NULL,1777430555,1777430555),(78,9,'Sign employment contract','Pre-boarding','Completed','2026-05-11',NULL,1777430555,1777430555),(79,9,'Complete personal data sheet','Pre-boarding','Completed','2026-05-12',NULL,1777430555,1777430555),(80,9,'Setup company email and accounts','Week 1','Completed','2026-04-30',NULL,1777430555,1777430555),(81,9,'Meet department team members','Week 1','Completed','2026-05-10',NULL,1777430555,1777430555),(82,9,'Complete Info Security training','Week 1','In Progress','2026-05-01',NULL,1777430555,1777430555),(83,9,'Setup development environment','Week 1','In Progress','2026-05-12',NULL,1777430555,1777430555),(84,9,'Read company handbook','Week 1','Completed','2026-04-30',NULL,1777430555,1777430555),(85,10,'Submit government ID copies','Pre-boarding','Completed','2026-05-04',NULL,1777430555,1777430555),(86,10,'Sign employment contract','Pre-boarding','Completed','2026-05-01',NULL,1777430555,1777430555),(87,10,'Complete personal data sheet','Pre-boarding','Completed','2026-05-06',NULL,1777430555,1777430555),(88,10,'Setup company email and accounts','Week 1','Completed','2026-05-02',NULL,1777430555,1777430555),(89,10,'Meet department team members','Week 1','Completed','2026-05-13',NULL,1777430555,1777430555),(90,10,'Complete Info Security training','Week 1','In Progress','2026-05-04',NULL,1777430555,1777430555),(91,10,'Setup development environment','Week 1','In Progress','2026-05-07',NULL,1777430555,1777430555),(92,10,'Read company handbook','Week 1','Completed','2026-05-09',NULL,1777430555,1777430555),(93,11,'Submit government ID copies','Pre-boarding','Completed','2026-05-13',NULL,1777430555,1777430555),(94,11,'Sign employment contract','Pre-boarding','Completed','2026-05-12',NULL,1777430555,1777430555),(95,11,'Complete personal data sheet','Pre-boarding','Completed','2026-05-02',NULL,1777430555,1777430555),(96,11,'Setup company email and accounts','Week 1','Completed','2026-04-30',NULL,1777430555,1777430555),(97,11,'Meet department team members','Week 1','Completed','2026-04-30',NULL,1777430555,1777430555),(98,11,'Complete Info Security training','Week 1','In Progress','2026-05-01',NULL,1777430555,1777430555),(99,11,'Setup development environment','Week 1','In Progress','2026-05-03',NULL,1777430555,1777430555),(100,11,'Read company handbook','Week 1','Completed','2026-05-05',NULL,1777430555,1777430555),(101,12,'Submit government ID copies','Pre-boarding','Completed','2026-05-05',NULL,1777430555,1777430555),(102,12,'Sign employment contract','Pre-boarding','Completed','2026-05-07',NULL,1777430555,1777430555),(103,12,'Complete personal data sheet','Pre-boarding','Completed','2026-05-12',NULL,1777430555,1777430555),(104,12,'Setup company email and accounts','Week 1','Completed','2026-05-09',NULL,1777430555,1777430555),(105,12,'Meet department team members','Week 1','Completed','2026-05-08',NULL,1777430555,1777430555),(106,12,'Complete Info Security training','Week 1','In Progress','2026-05-12',NULL,1777430555,1777430555),(107,12,'Setup development environment','Week 1','In Progress','2026-04-30',NULL,1777430555,1777430555),(108,12,'Read company handbook','Week 1','Completed','2026-05-06',NULL,1777430555,1777430555),(109,13,'Submit government ID copies','Pre-boarding','Completed','2026-04-30',NULL,1777430555,1777430555),(110,13,'Sign employment contract','Pre-boarding','Completed','2026-05-01',NULL,1777430555,1777430555),(111,13,'Complete personal data sheet','Pre-boarding','Completed','2026-04-30',NULL,1777430555,1777430555),(112,13,'Setup company email and accounts','Week 1','Completed','2026-04-30',NULL,1777430555,1777430555),(113,13,'Meet department team members','Week 1','Completed','2026-05-01',NULL,1777430555,1777430555),(114,13,'Complete Info Security training','Week 1','In Progress','2026-05-09',NULL,1777430555,1777430555),(115,13,'Setup development environment','Week 1','In Progress','2026-05-07',NULL,1777430555,1777430555),(116,13,'Read company handbook','Week 1','Completed','2026-05-13',NULL,1777430555,1777430555),(117,14,'Submit government ID copies','Pre-boarding','Completed','2026-05-13',NULL,1777430555,1777430555),(118,14,'Sign employment contract','Pre-boarding','Completed','2026-05-06',NULL,1777430555,1777430555),(119,14,'Complete personal data sheet','Pre-boarding','Completed','2026-05-05',NULL,1777430555,1777430555),(120,14,'Setup company email and accounts','Week 1','Completed','2026-05-12',NULL,1777430555,1777430555),(121,14,'Meet department team members','Week 1','Completed','2026-05-08',NULL,1777430555,1777430555),(122,14,'Complete Info Security training','Week 1','In Progress','2026-04-30',NULL,1777430555,1777430555),(123,14,'Setup development environment','Week 1','In Progress','2026-05-12',NULL,1777430555,1777430555),(124,14,'Read company handbook','Week 1','Completed','2026-05-01',NULL,1777430555,1777430555),(125,15,'Submit government ID copies','Pre-boarding','Completed','2026-05-04',NULL,1777430555,1777430555),(126,15,'Sign employment contract','Pre-boarding','Completed','2026-05-01',NULL,1777430555,1777430555),(127,15,'Complete personal data sheet','Pre-boarding','Completed','2026-05-09',NULL,1777430555,1777430555),(128,15,'Setup company email and accounts','Week 1','Completed','2026-05-05',NULL,1777430555,1777430555),(129,15,'Meet department team members','Week 1','Completed','2026-05-04',NULL,1777430555,1777430555),(130,15,'Complete Info Security training','Week 1','In Progress','2026-05-05',NULL,1777430555,1777430555),(131,15,'Setup development environment','Week 1','In Progress','2026-05-06',NULL,1777430555,1777430555),(132,15,'Read company handbook','Week 1','Completed','2026-05-05',NULL,1777430555,1777430555),(133,16,'Submit government ID copies','Pre-boarding','Completed','2026-05-05',NULL,1777430555,1777430555),(134,16,'Sign employment contract','Pre-boarding','Completed','2026-05-07',NULL,1777430555,1777430555),(135,16,'Complete personal data sheet','Pre-boarding','Completed','2026-05-05',NULL,1777430555,1777430555),(136,16,'Setup company email and accounts','Week 1','Completed','2026-05-10',NULL,1777430555,1777430555),(137,16,'Meet department team members','Week 1','Completed','2026-05-05',NULL,1777430555,1777430555),(138,16,'Complete Info Security training','Week 1','In Progress','2026-05-10',NULL,1777430555,1777430555),(139,16,'Setup development environment','Week 1','In Progress','2026-05-03',NULL,1777430555,1777430555),(140,16,'Read company handbook','Week 1','Completed','2026-05-07',NULL,1777430555,1777430555),(141,16,'Meet with direct supervisor','Week 2','Completed','2026-05-05',NULL,1777430555,1777430555),(142,16,'Complete first project assignment','Week 2','In Progress','2026-05-13',NULL,1777430555,1777430555),(143,16,'Submit SSS/PhilHealth/Pag-IBIG forms','Week 2','Not Started','2026-04-30',NULL,1777430555,1777430555),(144,16,'Complete HMO enrollment','Integration','Not Started','2026-05-08',NULL,1777430555,1777430555),(145,17,'Submit government ID copies','Pre-boarding','Completed','2026-05-07',NULL,1777430555,1777430555),(146,17,'Sign employment contract','Pre-boarding','Completed','2026-05-10',NULL,1777430555,1777430555),(147,17,'Complete personal data sheet','Pre-boarding','Completed','2026-05-02',NULL,1777430555,1777430555),(148,17,'Setup company email and accounts','Week 1','Completed','2026-05-05',NULL,1777430555,1777430555),(149,17,'Meet department team members','Week 1','Completed','2026-05-09',NULL,1777430555,1777430555),(150,17,'Complete Info Security training','Week 1','In Progress','2026-05-11',NULL,1777430555,1777430555),(151,17,'Setup development environment','Week 1','In Progress','2026-05-09',NULL,1777430555,1777430555),(152,17,'Read company handbook','Week 1','Completed','2026-05-07',NULL,1777430555,1777430555),(153,17,'Meet with direct supervisor','Week 2','Completed','2026-05-08',NULL,1777430555,1777430555),(154,17,'Complete first project assignment','Week 2','In Progress','2026-05-10',NULL,1777430555,1777430555),(155,17,'Submit SSS/PhilHealth/Pag-IBIG forms','Week 2','Not Started','2026-05-10',NULL,1777430555,1777430555),(156,17,'Complete HMO enrollment','Integration','Not Started','2026-05-03',NULL,1777430555,1777430555);
/*!40000 ALTER TABLE `onboarding_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll`
--

DROP TABLE IF EXISTS `payroll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_label` varchar(100) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `pay_date` date NOT NULL,
  `status` enum('Draft','Processed','Released') NOT NULL DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL DEFAULT 0,
  `updated_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll`
--

LOCK TABLES `payroll` WRITE;
/*!40000 ALTER TABLE `payroll` DISABLE KEYS */;
INSERT INTO `payroll` VALUES (1,'April 2026 – Semi-monthly','2026-04-01','2026-04-15','2026-04-15','Processed',1,1777430555,1777430555),(4,'balagbag','2026-05-01','2026-05-31','2026-05-15','Processed',1,1777835391,1777835394),(5,'balagbagpt2','2026-05-01','2026-05-31','2026-05-15','Processed',1,1777835439,1777835442),(6,'tarantado','2026-05-01','2026-05-31','2026-05-15','Processed',1,1777877635,1777877641),(7,'balagbagdsd','2026-04-30','2026-05-31','2026-05-15','Released',1,1777918092,1777918099);
/*!40000 ALTER TABLE `payroll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslip`
--

DROP TABLE IF EXISTS `payslip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payslip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `basic_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(12,2) NOT NULL DEFAULT 0.00,
  `overtime_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gross_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sss` decimal(10,2) NOT NULL DEFAULT 0.00,
  `phil_health` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pag_ibig` decimal(10,2) NOT NULL DEFAULT 0.00,
  `income_tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Processed','Released','Cancelled') NOT NULL DEFAULT 'Processed',
  `created_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payslip` (`payroll_id`,`employee_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `payslip_ibfk_1` FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payslip_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslip`
--

LOCK TABLES `payslip` WRITE;
/*!40000 ALTER TABLE `payslip` DISABLE KEYS */;
INSERT INTO `payslip` VALUES (1,1,1,90000.00,9000.00,770.00,99770.00,450.00,200.00,50.00,15917.00,16617.00,83153.00,'Processed',1777430555),(2,1,2,32500.00,3250.00,0.00,35750.00,450.00,200.00,50.00,4417.00,5117.00,30633.00,'Processed',1777430555),(3,1,3,47500.00,4750.00,398.00,52648.00,450.00,200.00,50.00,7417.00,8117.00,44531.00,'Processed',1777430555),(4,1,4,31000.00,3100.00,479.00,34579.00,450.00,200.00,50.00,4117.00,4817.00,29762.00,'Processed',1777430555),(5,1,5,14000.00,1400.00,0.00,15400.00,450.00,200.00,50.00,717.00,1417.00,13983.00,'Processed',1777430555),(6,1,6,13750.00,1375.00,0.00,15125.00,450.00,200.00,50.00,667.00,1367.00,13758.00,'Processed',1777430555),(7,1,7,24000.00,2400.00,0.00,26400.00,450.00,200.00,50.00,2717.00,3417.00,22983.00,'Processed',1777430555),(8,1,8,23000.00,2300.00,0.00,25300.00,450.00,200.00,50.00,2517.00,3217.00,22083.00,'Processed',1777430555),(9,1,9,40000.00,4000.00,0.00,44000.00,450.00,200.00,50.00,5917.00,6617.00,37383.00,'Processed',1777430555),(10,1,10,26000.00,2600.00,0.00,28600.00,450.00,200.00,50.00,3117.00,3817.00,24783.00,'Processed',1777430555),(11,1,11,25000.00,2500.00,0.00,27500.00,450.00,200.00,50.00,2917.00,3617.00,23883.00,'Processed',1777430555),(12,1,12,37500.00,3750.00,0.00,41250.00,450.00,200.00,50.00,5417.00,6117.00,35133.00,'Processed',1777430555),(13,1,13,27500.00,2750.00,0.00,30250.00,450.00,200.00,50.00,3417.00,4117.00,26133.00,'Processed',1777430555),(14,1,14,32000.00,3200.00,0.00,35200.00,450.00,200.00,50.00,4317.00,5017.00,30183.00,'Processed',1777430555),(15,1,15,39000.00,3900.00,377.00,43277.00,450.00,200.00,50.00,5717.00,6417.00,36860.00,'Processed',1777430555),(16,1,16,17500.00,1750.00,0.00,19250.00,450.00,200.00,50.00,1417.00,2117.00,17133.00,'Processed',1777430555),(17,1,17,14000.00,1400.00,583.00,15983.00,450.00,200.00,50.00,717.00,1417.00,14566.00,'Processed',1777430555),(52,4,1,180000.00,18000.00,3835.23,201835.23,900.00,400.00,100.00,31833.40,33233.40,168601.83,'Processed',1777835394),(53,4,2,65000.00,6500.00,1384.94,72884.94,900.00,400.00,100.00,8833.40,10233.40,62651.54,'Processed',1777835394),(54,4,3,95000.00,9500.00,1349.43,105849.43,900.00,400.00,100.00,14833.40,16233.40,89616.03,'Processed',1777835394),(55,4,4,62000.00,6200.00,1321.02,69521.02,900.00,400.00,100.00,8233.40,9633.40,59887.62,'Processed',1777835394),(56,4,5,28000.00,2800.00,596.59,31396.59,900.00,400.00,100.00,1433.40,2833.40,28563.19,'Processed',1777835394),(57,4,6,27500.00,2750.00,585.94,30835.94,900.00,400.00,100.00,1333.40,2733.40,28102.54,'Processed',1777835394),(58,4,7,48000.00,4800.00,1022.73,53822.73,900.00,400.00,100.00,5433.40,6833.40,46989.33,'Processed',1777835394),(59,4,8,46000.00,4600.00,980.11,51580.11,900.00,400.00,100.00,5033.40,6433.40,45146.71,'Processed',1777835394),(60,4,9,80000.00,8000.00,1704.55,89704.55,900.00,400.00,100.00,11833.40,13233.40,76471.15,'Processed',1777835394),(61,4,10,52000.00,5200.00,1107.95,58307.95,900.00,400.00,100.00,6233.40,7633.40,50674.55,'Processed',1777835394),(62,4,11,50000.00,5000.00,1065.34,56065.34,900.00,400.00,100.00,5833.40,7233.40,48831.94,'Processed',1777835394),(63,4,12,75000.00,7500.00,1598.01,84098.01,900.00,400.00,100.00,10833.40,12233.40,71864.61,'Processed',1777835394),(64,4,13,55000.00,5500.00,1171.88,61671.88,900.00,400.00,100.00,6833.40,8233.40,53438.48,'Processed',1777835394),(65,4,14,64000.00,6400.00,1363.64,71763.64,900.00,400.00,100.00,8633.40,10033.40,61730.24,'Processed',1777835394),(66,4,15,78000.00,7800.00,1661.93,87461.93,900.00,400.00,100.00,11433.40,12833.40,74628.53,'Processed',1777835394),(67,4,16,35000.00,3500.00,745.74,39245.74,900.00,400.00,100.00,2833.40,4233.40,35012.34,'Processed',1777835394),(68,4,17,28000.00,2800.00,596.59,31396.59,900.00,400.00,100.00,1433.40,2833.40,28563.19,'Processed',1777835394),(69,5,1,180000.00,18000.00,3835.23,201835.23,900.00,400.00,100.00,31833.40,33233.40,168601.83,'Processed',1777835442),(70,5,2,65000.00,6500.00,1384.94,72884.94,900.00,400.00,100.00,8833.40,10233.40,62651.54,'Processed',1777835442),(71,5,3,95000.00,9500.00,2024.15,106524.15,900.00,400.00,100.00,14833.40,16233.40,90290.75,'Processed',1777835442),(72,5,4,62000.00,6200.00,1321.02,69521.02,900.00,400.00,100.00,8233.40,9633.40,59887.62,'Processed',1777835442),(73,5,5,28000.00,2800.00,596.59,31396.59,900.00,400.00,100.00,1433.40,2833.40,28563.19,'Processed',1777835442),(74,5,6,27500.00,2750.00,585.94,30835.94,900.00,400.00,100.00,1333.40,2733.40,28102.54,'Processed',1777835442),(75,5,7,48000.00,4800.00,1022.73,53822.73,900.00,400.00,100.00,5433.40,6833.40,46989.33,'Processed',1777835442),(76,5,8,46000.00,4600.00,980.11,51580.11,900.00,400.00,100.00,5033.40,6433.40,45146.71,'Processed',1777835442),(77,5,9,80000.00,8000.00,1704.55,89704.55,900.00,400.00,100.00,11833.40,13233.40,76471.15,'Processed',1777835442),(78,5,10,52000.00,5200.00,1107.95,58307.95,900.00,400.00,100.00,6233.40,7633.40,50674.55,'Processed',1777835442),(79,5,11,50000.00,5000.00,1065.34,56065.34,900.00,400.00,100.00,5833.40,7233.40,48831.94,'Processed',1777835442),(80,5,12,75000.00,7500.00,1598.01,84098.01,900.00,400.00,100.00,10833.40,12233.40,71864.61,'Processed',1777835442),(81,5,13,55000.00,5500.00,1171.88,61671.88,900.00,400.00,100.00,6833.40,8233.40,53438.48,'Processed',1777835442),(82,5,14,64000.00,6400.00,1363.64,71763.64,900.00,400.00,100.00,8633.40,10033.40,61730.24,'Processed',1777835442),(83,5,15,78000.00,7800.00,1661.93,87461.93,900.00,400.00,100.00,11433.40,12833.40,74628.53,'Processed',1777835442),(84,5,16,35000.00,3500.00,745.74,39245.74,900.00,400.00,100.00,2833.40,4233.40,35012.34,'Processed',1777835442),(85,5,17,28000.00,2800.00,596.59,31396.59,900.00,400.00,100.00,1433.40,2833.40,28563.19,'Processed',1777835442),(86,6,1,180000.00,18000.00,3835.23,201835.23,900.00,400.00,100.00,31833.40,33233.40,168601.83,'Processed',1777877641),(87,6,2,65000.00,6500.00,1384.94,72884.94,900.00,400.00,100.00,8833.40,10233.40,62651.54,'Processed',1777877641),(88,6,3,95000.00,9500.00,1511.36,106011.36,900.00,400.00,100.00,14833.40,16233.40,89777.96,'Processed',1777877641),(89,6,4,62000.00,6200.00,1321.02,69521.02,900.00,400.00,100.00,8233.40,9633.40,59887.62,'Processed',1777877641),(90,6,5,28000.00,2800.00,596.59,31396.59,900.00,400.00,100.00,1433.40,2833.40,28563.19,'Processed',1777877641),(91,6,6,27500.00,2750.00,585.94,30835.94,900.00,400.00,100.00,1333.40,2733.40,28102.54,'Processed',1777877641),(92,6,7,48000.00,4800.00,1022.73,53822.73,900.00,400.00,100.00,5433.40,6833.40,46989.33,'Processed',1777877641),(93,6,8,46000.00,4600.00,980.11,51580.11,900.00,400.00,100.00,5033.40,6433.40,45146.71,'Processed',1777877641),(94,6,9,80000.00,8000.00,1704.55,89704.55,900.00,400.00,100.00,11833.40,13233.40,76471.15,'Processed',1777877641),(95,6,10,52000.00,5200.00,1107.95,58307.95,900.00,400.00,100.00,6233.40,7633.40,50674.55,'Processed',1777877641),(96,6,11,50000.00,5000.00,1065.34,56065.34,900.00,400.00,100.00,5833.40,7233.40,48831.94,'Processed',1777877641),(97,6,12,75000.00,7500.00,1598.01,84098.01,900.00,400.00,100.00,10833.40,12233.40,71864.61,'Processed',1777877641),(98,6,13,55000.00,5500.00,1171.88,61671.88,900.00,400.00,100.00,6833.40,8233.40,53438.48,'Processed',1777877641),(99,6,14,64000.00,6400.00,1363.64,71763.64,900.00,400.00,100.00,8633.40,10033.40,61730.24,'Processed',1777877641),(100,6,15,78000.00,7800.00,1661.93,87461.93,900.00,400.00,100.00,11433.40,12833.40,74628.53,'Processed',1777877641),(101,6,16,35000.00,3500.00,745.74,39245.74,900.00,400.00,100.00,2833.40,4233.40,35012.34,'Processed',1777877641),(102,6,17,28000.00,2800.00,596.59,31396.59,900.00,400.00,100.00,1433.40,2833.40,28563.19,'Processed',1777877641),(103,7,1,180000.00,18000.00,5113.64,203113.64,900.00,400.00,100.00,31833.40,33233.40,169880.24,'Released',1777918095),(104,7,2,65000.00,6500.00,1846.59,73346.59,900.00,400.00,100.00,8833.40,10233.40,63113.19,'Released',1777918095),(105,7,3,95000.00,9500.00,2186.08,106686.08,900.00,400.00,100.00,14833.40,16233.40,90452.68,'Released',1777918095),(106,7,4,62000.00,6200.00,1761.36,69961.36,900.00,400.00,100.00,8233.40,9633.40,60327.96,'Released',1777918095),(107,7,5,28000.00,2800.00,795.45,31595.45,900.00,400.00,100.00,1433.40,2833.40,28762.05,'Released',1777918095),(108,7,6,27500.00,2750.00,781.25,31031.25,900.00,400.00,100.00,1333.40,2733.40,28297.85,'Released',1777918095),(109,7,7,48000.00,4800.00,1363.64,54163.64,900.00,400.00,100.00,5433.40,6833.40,47330.24,'Released',1777918095),(110,7,8,46000.00,4600.00,1306.82,51906.82,900.00,400.00,100.00,5033.40,6433.40,45473.42,'Released',1777918095),(111,7,9,80000.00,8000.00,2272.73,90272.73,900.00,400.00,100.00,11833.40,13233.40,77039.33,'Released',1777918095),(112,7,10,52000.00,5200.00,1477.27,58677.27,900.00,400.00,100.00,6233.40,7633.40,51043.87,'Released',1777918095),(113,7,11,50000.00,5000.00,1420.45,56420.45,900.00,400.00,100.00,5833.40,7233.40,49187.05,'Released',1777918095),(114,7,12,75000.00,7500.00,2130.68,84630.68,900.00,400.00,100.00,10833.40,12233.40,72397.28,'Released',1777918095),(115,7,13,55000.00,5500.00,1562.50,62062.50,900.00,400.00,100.00,6833.40,8233.40,53829.10,'Released',1777918095),(116,7,14,64000.00,6400.00,1818.18,72218.18,900.00,400.00,100.00,8633.40,10033.40,62184.78,'Released',1777918095),(117,7,15,78000.00,7800.00,2215.91,88015.91,900.00,400.00,100.00,11433.40,12833.40,75182.51,'Released',1777918095),(118,7,16,35000.00,3500.00,994.32,39494.32,900.00,400.00,100.00,2833.40,4233.40,35260.92,'Released',1777918095),(119,7,17,28000.00,2800.00,795.45,31595.45,900.00,400.00,100.00,1433.40,2833.40,28762.05,'Released',1777918095);
/*!40000 ALTER TABLE `payslip` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personnel_action`
--

LOCK TABLES `personnel_action` WRITE;
/*!40000 ALTER TABLE `personnel_action` DISABLE KEYS */;
INSERT INTO `personnel_action` VALUES (1,3,'Promotion','2026-04-29','Software Engineer','Senior Software Engineer','Promoted after 6-month review','Approved',1,1777430555,1777430555),(2,12,'Salary Adjustment','2026-04-29','70000','75000','Performance-based increase for Q1 excellence','Approved',1,1777430555,1777430555);
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
  `department_id` int(11) DEFAULT NULL,
  `salary_min` decimal(12,2) DEFAULT 0.00,
  `salary_max` decimal(12,2) DEFAULT 0.00,
  `created_at` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `position_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `position`
--

LOCK TABLES `position` WRITE;
/*!40000 ALTER TABLE `position` DISABLE KEYS */;
INSERT INTO `position` VALUES (1,'Chief Executive Officer',6,120000.00,200000.00,1777430555),(2,'HR Manager',6,45000.00,75000.00,1777430555),(3,'Senior Software Engineer',1,80000.00,120000.00,1777430555),(4,'Software Engineer',1,45000.00,80000.00,1777430555),(5,'Junior Developer',1,25000.00,45000.00,1777430555),(6,'QA Engineer',2,40000.00,65000.00,1777430555),(7,'DevOps Engineer',3,60000.00,95000.00,1777430555),(8,'UI/UX Designer',4,40000.00,70000.00,1777430555),(9,'Project Manager',5,60000.00,90000.00,1777430555),(10,'Business Analyst',5,45000.00,75000.00,1777430555);
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project`
--

LOCK TABLES `project` WRITE;
/*!40000 ALTER TABLE `project` DISABLE KEYS */;
INSERT INTO `project` VALUES (1,'CURAConnect Web App',12,1,'Main company product','2026-04-15','2026-07-28','Active',1777430555,1777430555),(2,'Company Website Redesign',12,4,'Redesign corporate website','2026-04-19','2026-05-29','Active',1777430555,1777430555),(3,'CI/CD Pipeline Setup',12,3,'Implement automated CI/CD','2026-04-17','2026-05-13','Active',1777430555,1777430555),(4,'QA Test Framework',12,2,'Build automated test suite','2026-04-22','2026-06-13','Active',1777430555,1777430555);
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_member`
--

LOCK TABLES `project_member` WRITE;
/*!40000 ALTER TABLE `project_member` DISABLE KEYS */;
INSERT INTO `project_member` VALUES (1,1,3),(2,1,4),(3,1,5),(4,1,6),(5,1,14),(6,1,17);
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_task`
--

LOCK TABLES `project_task` WRITE;
/*!40000 ALTER TABLE `project_task` DISABLE KEYS */;
INSERT INTO `project_task` VALUES (1,1,3,'Setup project structure','High','Completed',100,'2026-05-04',1777430555),(2,1,4,'Implement auth module','High','In Progress',60,'2026-05-06',1777430555),(3,1,5,'Build dashboard UI','Medium','To Do',0,'2026-05-09',1777430555),(4,1,14,'Database schema design','High','Completed',100,'2026-05-02',1777430555),(5,2,10,'Wireframes','High','Completed',100,'2026-05-04',1777430555),(6,2,11,'High-fidelity mockups','Medium','In Progress',50,'2026-05-11',1777430555),(7,3,9,'GitHub Actions setup','High','Completed',100,'2026-05-01',1777430555),(8,3,15,'Docker containerization','High','In Progress',70,'2026-05-06',1777430555),(9,4,7,'Select testing framework','Medium','Completed',100,'2026-05-02',1777430555),(10,4,8,'Write smoke tests','High','In Progress',30,'2026-05-13',1777430555);
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
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift`
--

LOCK TABLES `shift` WRITE;
/*!40000 ALTER TABLE `shift` DISABLE KEYS */;
INSERT INTO `shift` VALUES (1,1,'2026-04-29','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(2,1,'2026-04-30','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(3,1,'2026-05-01','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(4,1,'2026-05-04','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(5,1,'2026-05-05','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(6,2,'2026-04-29','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(7,2,'2026-04-30','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(8,2,'2026-05-01','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(9,2,'2026-05-04','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(10,2,'2026-05-05','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(11,3,'2026-04-29','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(12,3,'2026-04-30','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(13,3,'2026-05-01','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(14,3,'2026-05-04','03:00:00','10:00:00','morning shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(15,3,'2026-05-05','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(16,4,'2026-04-29','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(17,4,'2026-04-30','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(18,4,'2026-05-01','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(19,4,'2026-05-04','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(20,4,'2026-05-05','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(21,5,'2026-04-29','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(22,5,'2026-04-30','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(23,5,'2026-05-01','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(24,5,'2026-05-04','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(25,5,'2026-05-05','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(26,6,'2026-04-29','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(27,6,'2026-04-30','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(28,6,'2026-05-01','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(29,6,'2026-05-04','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(30,6,'2026-05-05','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(31,7,'2026-04-29','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(32,7,'2026-04-30','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(33,7,'2026-05-01','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(34,7,'2026-05-04','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(35,7,'2026-05-05','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(36,8,'2026-04-29','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(37,8,'2026-04-30','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(38,8,'2026-05-01','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(39,8,'2026-05-04','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(40,8,'2026-05-05','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(41,9,'2026-04-29','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Remote','Scheduled',1,1777430555),(42,9,'2026-04-30','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Remote','Scheduled',1,1777430555),(43,9,'2026-05-01','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Remote','Scheduled',1,1777430555),(44,9,'2026-05-04','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Remote','Scheduled',1,1777430555),(45,9,'2026-05-05','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Remote','Scheduled',1,1777430555),(46,10,'2026-04-29','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(47,10,'2026-04-30','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(48,10,'2026-05-01','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(49,10,'2026-05-04','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(50,10,'2026-05-05','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(51,11,'2026-04-29','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(52,11,'2026-04-30','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(53,11,'2026-05-01','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(54,11,'2026-05-04','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(55,11,'2026-05-05','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(56,12,'2026-04-29','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(57,12,'2026-04-30','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(58,12,'2026-05-01','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(59,12,'2026-05-04','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(60,12,'2026-05-05','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(61,13,'2026-04-29','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(62,13,'2026-04-30','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(63,13,'2026-05-01','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(64,13,'2026-05-04','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(65,13,'2026-05-05','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(66,14,'2026-04-29','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(67,14,'2026-04-30','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(68,14,'2026-05-01','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(69,14,'2026-05-04','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(70,14,'2026-05-05','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(71,15,'2026-04-29','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Remote','Scheduled',1,1777430555),(72,15,'2026-04-30','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Remote','Scheduled',1,1777430555),(73,15,'2026-05-01','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Remote','Scheduled',1,1777430555),(74,15,'2026-05-04','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Remote','Scheduled',1,1777430555),(75,15,'2026-05-05','13:00:00','22:00:00','Afternoon Shift',NULL,60,NULL,NULL,'Remote','Scheduled',1,1777430555),(76,16,'2026-04-29','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(77,16,'2026-04-30','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(78,16,'2026-05-01','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(79,16,'2026-05-04','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(80,16,'2026-05-05','08:00:00','17:00:00','Morning Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(81,17,'2026-04-29','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(82,17,'2026-04-30','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(83,17,'2026-05-01','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(84,17,'2026-05-04','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(85,17,'2026-05-05','08:00:00','17:00:00','Regular Shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777430555),(90,3,'2026-05-08','08:00:00','17:00:00','mid shift',NULL,60,NULL,NULL,'Onsite','Scheduled',1,1777919622),(92,3,'2026-05-06','09:00:00','18:00:00','',1,60,NULL,NULL,'Onsite','Scheduled',1,1777883265),(93,14,'2026-05-07','09:00:00','18:00:00','',1,60,'16:00:00','17:00:00','Onsite','Scheduled',1,1777861640),(94,13,'2026-05-18','09:00:00','18:00:00','Standard',1,60,'16:00:00','17:00:00','Onsite','Scheduled',1,1777861683),(95,13,'2026-05-19','09:00:00','18:00:00','Standard',1,60,'16:00:00','17:00:00','Onsite','Scheduled',1,1777861683),(96,13,'2026-05-20','09:00:00','18:00:00','Standard',1,60,'16:00:00','17:00:00','Onsite','Scheduled',1,1777861683),(97,13,'2026-05-21','09:00:00','18:00:00','Standard',1,60,'16:00:00','17:00:00','Onsite','Scheduled',1,1777861683),(98,13,'2026-05-22','09:00:00','18:00:00','Standard',1,60,'16:00:00','17:00:00','Onsite','Scheduled',1,1777861683),(99,13,'2026-05-25','09:00:00','18:00:00','Standard',1,60,'16:00:00','17:00:00','Onsite','Scheduled',1,1777861683),(100,13,'2026-05-26','09:00:00','18:00:00','Standard',1,60,'16:00:00','17:00:00','Onsite','Scheduled',1,1777861683),(101,13,'2026-05-27','09:00:00','18:00:00','Standard',1,60,'16:00:00','17:00:00','Onsite','Scheduled',1,1777861683),(102,13,'2026-05-28','09:00:00','18:00:00','Standard',1,60,'16:00:00','17:00:00','Onsite','Scheduled',1,1777861683),(103,13,'2026-05-29','09:00:00','18:00:00','Standard',1,60,'16:00:00','17:00:00','Onsite','Scheduled',1,1777861683),(104,13,'2026-06-01','09:00:00','18:00:00','Standard',1,60,'16:00:00','17:00:00','Onsite','Scheduled',1,1777861683);
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
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_notification`
--

LOCK TABLES `system_notification` WRITE;
/*!40000 ALTER TABLE `system_notification` DISABLE KEYS */;
INSERT INTO `system_notification` VALUES (1,1,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',1,'2026-04-29 04:42:35'),(2,2,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',1,'2026-04-29 04:42:35'),(3,3,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',1,'2026-04-29 04:42:35'),(4,4,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',0,'2026-04-29 04:42:35'),(5,5,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',0,'2026-04-29 04:42:35'),(6,6,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',0,'2026-04-29 04:42:35'),(7,7,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',1,'2026-04-29 04:42:35'),(8,8,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',0,'2026-04-29 04:42:35'),(9,9,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',0,'2026-04-29 04:42:35'),(10,10,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',0,'2026-04-29 04:42:35'),(11,11,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',0,'2026-04-29 04:42:35'),(12,12,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',0,'2026-04-29 04:42:35'),(13,13,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',0,'2026-04-29 04:42:35'),(14,14,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',0,'2026-04-29 04:42:35'),(15,15,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',0,'2026-04-29 04:42:35'),(16,16,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',0,'2026-04-29 04:42:35'),(17,17,'success','Welcome to Staffora HRMS!','Your account is set up. Explore your dashboard.','/HRMSV3/index.php?page=dashboard',0,'2026-04-29 04:42:35'),(18,1,'warning','2 Pending Leave Requests','Jenny Tan and Ryan Co have pending leave requests.','/HRMSV3/index.php?page=leave',1,'2026-04-29 04:42:35'),(19,2,'info','Onboarding Reminder','5 employees have incomplete onboarding tasks.','/HRMSV3/index.php?page=onboarding',1,'2026-04-29 04:42:35'),(20,1,'info','Welcome back!','Your session is active. Have a great day!','/HRMSV3/index.php?page=dashboard',1,'2026-04-29 10:42:49'),(21,2,'info','Welcome back!','Your session is active. Have a great day!','/HRMSV3/index.php?page=dashboard',1,'2026-04-29 10:43:53'),(22,3,'info','Welcome back!','Your session is active. Have a great day!','/HRMSV3/index.php?page=dashboard',1,'2026-04-29 10:44:54'),(23,2,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',1,'2026-05-04 03:16:51'),(24,3,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',1,'2026-05-04 03:16:51'),(25,4,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',0,'2026-05-04 03:16:51'),(26,5,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',0,'2026-05-04 03:16:51'),(27,6,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',0,'2026-05-04 03:16:51'),(28,7,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',1,'2026-05-04 03:16:51'),(29,8,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',0,'2026-05-04 03:16:51'),(30,9,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',0,'2026-05-04 03:16:51'),(31,10,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',0,'2026-05-04 03:16:51'),(32,11,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',0,'2026-05-04 03:16:51'),(33,12,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',0,'2026-05-04 03:16:51'),(34,13,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',0,'2026-05-04 03:16:51'),(35,14,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',0,'2026-05-04 03:16:51'),(36,15,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',0,'2026-05-04 03:16:51'),(37,16,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',0,'2026-05-04 03:16:51'),(38,17,'info','New Announcement: jdjsidajdsosd','balagbag pakak','/HRMSV3/index.php?page=announcement',0,'2026-05-04 03:16:51'),(39,6,'success','Leave Request Approved','Your leave request has been Approved.','/HRMSV3/index.php?page=my_leave',0,'2026-05-04 15:28:36'),(40,7,'danger','Leave Request Denied','Your leave request has been Denied.','/HRMSV3/index.php?page=my_leave',1,'2026-05-04 15:28:38'),(41,5,'success','Leave Request Approved','Your leave request has been Approved.','/HRMSV3/index.php?page=my_leave',0,'2026-05-05 01:56:29'),(42,3,'success','Leave Request Approved','Your leave request has been Approved.','/HRMSV3/index.php?page=my_leave',1,'2026-05-05 02:12:47'),(43,15,'info','Welcome back!','Your session is active. Have a great day!','/HRMSV3/index.php?page=dashboard',0,'2026-05-05 02:19:50'),(44,7,'info','Welcome back!','Your session is active. Have a great day!','/HRMSV3/index.php?page=dashboard',1,'2026-05-05 02:20:37'),(45,3,'info','New Shift Assigned','You have been assigned a shift on May 8, 2026 (8:00 AM – 5:00 PM)','/HRMSV3/index.php?page=shift',1,'2026-05-05 02:33:42'),(46,3,'danger','Leave Request Denied','Your leave request has been Denied.','/HRMSV3/index.php?page=my_leave',1,'2026-05-05 03:02:10'),(47,3,'success','Leave Request Approved','Your leave request has been Approved.','/HRMSV3/index.php?page=my_leave',1,'2026-05-04 16:13:34'),(48,3,'success','Leave Request Approved','Your leave request has been Approved.','/HRMSV3/index.php?page=my_leave',1,'2026-05-04 16:16:15'),(49,3,'info','New Shift Assigned','You have been assigned a shift on May 6, 2026 (9:00 AM – 6:00 PM)','/HRMSV3/index.php?page=shift',1,'2026-05-04 16:27:45'),(50,3,'success','Timelog Dispute Approved','Your timelog dispute for 2026-04-16 has been approved and your timelog has been corrected.','/HRMSV3/index.php?page=timelog_dispute',1,'2026-05-04 10:12:36'),(51,14,'info','New Schedule Assigned','You have been assigned a schedule from May 7, 2026 to May 7, 2026 (9:00 AM – 6:00 PM)','/HRMSV3/index.php?page=shift',0,'2026-05-04 10:27:20'),(52,13,'info','New Schedule Assigned','You have been assigned a schedule from May 17, 2026 to Jun 1, 2026 (9:00 AM – 6:00 PM)','/HRMSV3/index.php?page=shift',0,'2026-05-04 10:28:03'),(53,3,'danger','Leave Request Denied','Your leave request has been Denied.','/HRMSV3/index.php?page=my_leave',0,'2026-05-04 10:43:32'),(54,3,'success','Leave Request Approved','Your leave request has been Approved.','/HRMSV3/index.php?page=my_leave',0,'2026-05-04 10:43:40');
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_setting`
--

LOCK TABLES `system_setting` WRITE;
/*!40000 ALTER TABLE `system_setting` DISABLE KEYS */;
INSERT INTO `system_setting` VALUES (1,'company_name','CURA Corporation',1777430555),(2,'company_email','hr@cura.ph',1777430555),(3,'leave_vacation','15',1777430555),(4,'leave_sick','10',1777430555),(5,'leave_personal','3',1777430555),(6,'leave_emergency','3',1777430555),(7,'payroll_cutoff','15',1777430555),(8,'work_hours_per_day','8',1777430555);
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
INSERT INTO `timelog_dispute` VALUES (1,3,'2026-04-16','08:06:00','17:14:00','07:57:00','17:14:00',60,'Timelog Correction','asfsad',NULL,'Approved',1,1777860756,1777860745);
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'admin','admin@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Super Admin','Active',6,1777430555),(2,'hr.manager','hr@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Manager','Active',6,1777430555),(3,'juan.delacruz','juan.delacruz@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',1,1777430555),(4,'ana.garcia','ana.garcia@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',1,1777430555),(5,'mark.lim','mark.lim@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',1,1777430555),(6,'jenny.tan','jenny.tan@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',1,1777430555),(7,'ryan.co','ryan.co@cura.ph','213311d0722e191141a06b0adaa37a4b','Employee','Active',2,1777430555),(8,'lisa.ong','lisa.ong@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',2,1777430555),(9,'ben.wu','ben.wu@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',3,1777430555),(10,'claire.sy','claire.sy@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',4,1777430555),(11,'kevin.ng','kevin.ng@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',4,1777430555),(12,'diana.go','diana.go@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',5,1777430555),(13,'joel.cruz','joel.cruz@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',5,1777430555),(14,'grace.lee','grace.lee@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',1,1777430555),(15,'mike.chan','mike.chan@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',3,1777430555),(16,'kim.ramos','kim.ramos@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',6,1777430555),(17,'dante.flores','dante.flores@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',1,1777430555),(18,'remus.aimiel.marasigan','aimielmarasigan@gmail.com','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',6,1777878381),(22,'sada','adasd@cura.ph','6d6589c8ea9eb6e3d12cbf3bebbee7b9','Employee','Active',NULL,1777884802),(23,'ddasd.dsad','aimielmarasigan@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',6,1777885278),(24,'remus.aimiel.marasigan','asdjspdjas@cura.ph','42f749ade7f9e195bf475f37a44cafcb','Employee','Active',6,1777862695);
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
INSERT INTO `work_rule` VALUES (1,'Standard','Basic',8.00,60,4,'09:00:00','18:00:00',0,0,'10:00:00',4.00,'1,2,3,4,5',1,NULL,1777921918);
/*!40000 ALTER TABLE `work_rule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'hrms_db'
--
