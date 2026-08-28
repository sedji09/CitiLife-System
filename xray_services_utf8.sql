-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: localhost    Database: citilife_db
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

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
-- Table structure for table `xray_services`
--

DROP TABLE IF EXISTS `xray_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `xray_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `exam_type` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `xray_services`
--

LOCK TABLES `xray_services` WRITE;
/*!40000 ALTER TABLE `xray_services` DISABLE KEYS */;
INSERT INTO `xray_services` VALUES (1,'Chest','Chest PA',400.00,'active','2026-08-14 15:56:00'),(2,'Chest','Chest AP',450.00,'active','2026-08-14 15:56:00'),(3,'Chest','Chest AP/LAT',600.00,'active','2026-08-14 15:56:00'),(4,'Chest','Apicolordotic View',450.00,'active','2026-08-14 15:56:00'),(5,'Abdomen','Abdomen (Pedia)',500.00,'active','2026-08-14 15:56:00'),(6,'Abdomen','Abdomen Upright',600.00,'active','2026-08-14 15:56:00'),(7,'Lower Extremities','Foot',350.00,'active','2026-08-14 15:56:00'),(8,'Upper Extremities','Elbow',350.00,'active','2026-08-14 15:56:00'),(9,'Spine','Cervical AP/LAT',700.00,'active','2026-08-14 15:56:00'),(12,'Upper Extremities','Arm',200.00,'active','2026-08-25 04:27:37');
/*!40000 ALTER TABLE `xray_services` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-28 11:31:14
