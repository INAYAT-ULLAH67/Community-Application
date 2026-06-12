-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: PakhtoonCommunityDB
-- ------------------------------------------------------
-- Server version	8.0.45-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Degrees`
--

DROP TABLE IF EXISTS `Degrees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Degrees` (
  `degree_is` int NOT NULL,
  `degree_name` varchar(50) NOT NULL,
  `pays_party_fund` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`degree_is`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Degrees`
--

LOCK TABLES `Degrees` WRITE;
/*!40000 ALTER TABLE `Degrees` DISABLE KEYS */;
INSERT INTO `Degrees` VALUES (1,'Undergraduate',1),(2,'Graduate',0);
/*!40000 ALTER TABLE `Degrees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Departments`
--

DROP TABLE IF EXISTS `Departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Departments` (
  `dept_prefix` varchar(5) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  PRIMARY KEY (`dept_prefix`),
  UNIQUE KEY `dept_name` (`dept_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Departments`
--

LOCK TABLES `Departments` WRITE;
/*!40000 ALTER TABLE `Departments` DISABLE KEYS */;
INSERT INTO `Departments` VALUES ('CE','civil engineering'),('CS','Computer System Engineering'),('EE','Electrical Engineering');
/*!40000 ALTER TABLE `Departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Funds`
--

DROP TABLE IF EXISTS `Funds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Funds` (
  `fund_id` int NOT NULL AUTO_INCREMENT,
  `fund_name` varchar(100) NOT NULL,
  `fund_type` enum('Monthly','Party') NOT NULL,
  `required_amount` decimal(10,2) DEFAULT NULL,
  `target_year` year NOT NULL,
  PRIMARY KEY (`fund_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Funds`
--

LOCK TABLES `Funds` WRITE;
/*!40000 ALTER TABLE `Funds` DISABLE KEYS */;
INSERT INTO `Funds` VALUES (3,'April Dues 2026','Monthly',4000.00,2026),(5,'MayDue','Monthly',400.00,2026);
/*!40000 ALTER TABLE `Funds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Payments`
--

DROP TABLE IF EXISTS `Payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Payments` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `dept_prefix` varchar(5) DEFAULT NULL,
  `batch_year` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `fund_id` int DEFAULT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `dept_prefix` (`dept_prefix`,`batch_year`,`student_id`),
  KEY `fund_id` (`fund_id`),
  CONSTRAINT `Payments_ibfk_1` FOREIGN KEY (`dept_prefix`, `batch_year`, `student_id`) REFERENCES `Students` (`dept_prefix`, `batch_year`, `student_id`),
  CONSTRAINT `Payments_ibfk_2` FOREIGN KEY (`fund_id`) REFERENCES `Funds` (`fund_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Payments`
--

LOCK TABLES `Payments` WRITE;
/*!40000 ALTER TABLE `Payments` DISABLE KEYS */;
INSERT INTO `Payments` VALUES (3,'CS',24,149,5,400.00,'2026-05-12 10:37:49');
/*!40000 ALTER TABLE `Payments` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `check_graduate_payment_eligibility` BEFORE INSERT ON `Payments` FOR EACH ROW BEGIN
    DECLARE s_degree INT;
    
    
    SELECT degree_is INTO s_degree 
    FROM Students 
    WHERE dept_prefix = NEW.dept_prefix 
      AND batch_year = NEW.batch_year 
      AND student_id = NEW.student_id;
    
    
    IF s_degree = 2 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Restriction: Graduate students cannot make any payments.';
    END IF;
    
    
    
    
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `Roles`
--

DROP TABLE IF EXISTS `Roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Roles` (
  `role_id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Roles`
--

LOCK TABLES `Roles` WRITE;
/*!40000 ALTER TABLE `Roles` DISABLE KEYS */;
INSERT INTO `Roles` VALUES (3,'Batch Representative'),(4,'Financial Secretary'),(1,'President'),(2,'Vice President');
/*!40000 ALTER TABLE `Roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Student_Phones`
--

DROP TABLE IF EXISTS `Student_Phones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Student_Phones` (
  `dept_prefix` varchar(5) NOT NULL,
  `batch_year` int NOT NULL,
  `student_id` int NOT NULL,
  `phone_no` varchar(15) NOT NULL,
  PRIMARY KEY (`dept_prefix`,`batch_year`,`student_id`,`phone_no`),
  CONSTRAINT `Student_Phones_ibfk_1` FOREIGN KEY (`dept_prefix`, `batch_year`, `student_id`) REFERENCES `Students` (`dept_prefix`, `batch_year`, `student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Student_Phones`
--

LOCK TABLES `Student_Phones` WRITE;
/*!40000 ALTER TABLE `Student_Phones` DISABLE KEYS */;
/*!40000 ALTER TABLE `Student_Phones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Students`
--

DROP TABLE IF EXISTS `Students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Students` (
  `dept_prefix` varchar(5) NOT NULL,
  `batch_year` int NOT NULL,
  `student_id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `degree_is` int NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','student') DEFAULT 'student',
  PRIMARY KEY (`dept_prefix`,`batch_year`,`student_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  KEY `degree_is` (`degree_is`),
  CONSTRAINT `Students_ibfk_1` FOREIGN KEY (`dept_prefix`) REFERENCES `Departments` (`dept_prefix`),
  CONSTRAINT `Students_ibfk_2` FOREIGN KEY (`degree_is`) REFERENCES `Degrees` (`degree_is`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Students`
--

LOCK TABLES `Students` WRITE;
/*!40000 ALTER TABLE `Students` DISABLE KEYS */;
INSERT INTO `Students` VALUES ('CE',24,214,'Wajid','wajid@gmail.com',1,'wajidKhan','$2y$10$lddRfNIGhfQCY.C8h8kgZu3g/wPYPOOm0XIJsAIuuLJJ0M1gfo672','student'),('CS',24,144,'AhamdNoor','ahmad@gmail.com',1,'AhamdNoor','$2y$10$6.YnbZZ9nwGOenzyiVwQleP3/hEnBPLEr7i6rkKR3sxR4Qa3KrZ7.','student'),('CS',24,148,'IbrarAhmad','ibrar@gmail.com',1,'ibrarahmad','$2y$10$Ib86Nyn07HquNbDoQhPJNuxvmTBhuSZLY2BqUilWvkHKwc0RFb7gi','admin'),('CS',24,149,'Inayat Ullah','ullah55.492@gmail.com',1,'inayatullah','$2y$10$AFh9bYb4mzxbqm5gmKTcreoyNhdtwxufY5.cJyf0T5cKYfolz/WPq','admin'),('EE',23,1,'NoorAzam','azam@gmail.com',1,'NoorAzam','$2y$10$sZ5WBomFruA1o.fNUnmOh.dUH1SBMI6AzkgCdtF9c95joPQrtN3Li','admin');
/*!40000 ALTER TABLE `Students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Undergraduate_Leadership`
--

DROP TABLE IF EXISTS `Undergraduate_Leadership`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Undergraduate_Leadership` (
  `dept_prefix` varchar(5) NOT NULL,
  `batch_year` int NOT NULL,
  `student_id` int NOT NULL,
  `role_id` int NOT NULL,
  `tenure_year` year NOT NULL,
  PRIMARY KEY (`dept_prefix`,`batch_year`,`student_id`,`role_id`,`tenure_year`),
  UNIQUE KEY `unique_global_batch_leader` (`batch_year`,`role_id`,`tenure_year`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `Undergraduate_Leadership_ibfk_1` FOREIGN KEY (`dept_prefix`, `batch_year`, `student_id`) REFERENCES `Students` (`dept_prefix`, `batch_year`, `student_id`),
  CONSTRAINT `Undergraduate_Leadership_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `Roles` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Undergraduate_Leadership`
--

LOCK TABLES `Undergraduate_Leadership` WRITE;
/*!40000 ALTER TABLE `Undergraduate_Leadership` DISABLE KEYS */;
INSERT INTO `Undergraduate_Leadership` VALUES ('CS',24,149,3,2026),('CS',24,148,4,2026),('EE',23,1,4,2026);
/*!40000 ALTER TABLE `Undergraduate_Leadership` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `restrict_leadership_to_undergrads` BEFORE INSERT ON `Undergraduate_Leadership` FOR EACH ROW BEGIN
    DECLARE s_degree INT;
    
    
    SELECT degree_is INTO s_degree 
    FROM Students 
    WHERE dept_prefix = NEW.dept_prefix 
      AND batch_year = NEW.batch_year 
      AND student_id = NEW.student_id;
    
    
    IF s_degree = 2 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Permission Denied: Graduate students cannot hold Undergraduate Leadership roles.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-17 14:47:26
