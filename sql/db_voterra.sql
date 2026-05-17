-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: db_voterra
-- ------------------------------------------------------
-- Server version	8.0.30

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
-- Current Database: `db_voterra`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `db_voterra` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `db_voterra`;

--
-- Table structure for table `authorized_ballots`
--

DROP TABLE IF EXISTS `authorized_ballots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `authorized_ballots` (
  `ballot_number` varchar(50) NOT NULL,
  `is_used` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`ballot_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authorized_ballots`
--

LOCK TABLES `authorized_ballots` WRITE;
/*!40000 ALTER TABLE `authorized_ballots` DISABLE KEYS */;
INSERT INTO `authorized_ballots` VALUES ('LIP-53F5-0001',1),('LIP-53F5-0002',1),('LIP-53F5-0003',0),('LIP-53F5-0004',0),('LIP-53F5-0005',0),('LIP-53F5-0006',0),('LIP-53F5-0007',0),('LIP-53F5-0008',0),('LIP-53F5-0009',0),('LIP-53F5-0010',0);
/*!40000 ALTER TABLE `authorized_ballots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `candidates`
--

DROP TABLE IF EXISTS `candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidates` (
  `id` int NOT NULL,
  `position_id` int DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `party_alias` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidates`
--

LOCK TABLES `candidates` WRITE;
/*!40000 ALTER TABLE `candidates` DISABLE KEYS */;
INSERT INTO `candidates` VALUES (1,1,'Ramon Dela Cruz','P1'),(2,1,'Eduardo Villanueva','P2'),(3,11,'Lorenzo Santos','P1'),(4,11,'Miguel Bautista','P2'),(5,12,'Antonio Reyes','P1'),(6,12,'Jose Mercado','P1'),(7,12,'Paolo Fernandez','P1'),(8,12,'Mark Anthony Lim','P1'),(9,12,'Carlo Mendoza','P1'),(10,12,'Ryan Torres','P1'),(11,12,'Nathaniel Cruz','P1'),(12,12,'Patrick Valdez','P1'),(13,12,'Vincent Navarro','P1'),(14,12,'Kenneth Garcia','P1'),(15,12,'Jerome Castillo','P1'),(16,12,'Francis Alcantara','P1'),(17,12,'Daniel Robles','P2'),(18,12,'Leo Marquez','P2'),(19,12,'Adrian Pineda','P2'),(20,12,'Brian Solis','P2'),(21,12,'Harold Aquino','P2'),(22,12,'Julius Navarro','P2'),(23,12,'Emmanuel Rojas','P2'),(24,12,'Kevin Bautista','P2'),(25,12,'Rafael Dominguez','P2'),(26,12,'Gilbert Ramos','P2'),(27,12,'Noel Serrano','P2'),(28,12,'Victor Salazar','P2');
/*!40000 ALTER TABLE `candidates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `local_votes`
--

DROP TABLE IF EXISTS `local_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `local_votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ballot_number` varchar(50) DEFAULT NULL,
  `candidate_id` int DEFAULT NULL,
  `is_transmitted` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `local_votes`
--

LOCK TABLES `local_votes` WRITE;
/*!40000 ALTER TABLE `local_votes` DISABLE KEYS */;
INSERT INTO `local_votes` VALUES (1,'LIP-53F5-0001',2,1,'2026-05-16 08:33:00'),(2,'LIP-53F5-0001',3,1,'2026-05-16 08:33:00'),(3,'LIP-53F5-0001',19,1,'2026-05-16 08:33:00'),(4,'LIP-53F5-0001',20,1,'2026-05-16 08:33:00'),(5,'LIP-53F5-0001',17,1,'2026-05-16 08:33:00'),(6,'LIP-53F5-0001',16,1,'2026-05-16 08:33:00'),(7,'LIP-53F5-0001',21,1,'2026-05-16 08:33:00'),(8,'LIP-53F5-0001',6,1,'2026-05-16 08:33:00'),(9,'LIP-53F5-0001',14,1,'2026-05-16 08:33:00'),(10,'LIP-53F5-0001',18,1,'2026-05-16 08:33:00'),(11,'LIP-53F5-0001',11,1,'2026-05-16 08:33:00'),(12,'LIP-53F5-0001',7,1,'2026-05-16 08:33:00'),(13,'LIP-53F5-0001',25,1,'2026-05-16 08:33:00'),(14,'LIP-53F5-0001',28,1,'2026-05-16 08:33:00'),(15,'LIP-53F5-0002',2,1,'2026-05-17 03:17:38'),(16,'LIP-53F5-0002',3,1,'2026-05-17 03:17:38'),(17,'LIP-53F5-0002',19,1,'2026-05-17 03:17:38'),(18,'LIP-53F5-0002',20,1,'2026-05-17 03:17:38'),(19,'LIP-53F5-0002',17,1,'2026-05-17 03:17:38'),(20,'LIP-53F5-0002',16,1,'2026-05-17 03:17:38'),(21,'LIP-53F5-0002',21,1,'2026-05-17 03:17:38'),(22,'LIP-53F5-0002',6,1,'2026-05-17 03:17:38'),(23,'LIP-53F5-0002',14,1,'2026-05-17 03:17:38'),(24,'LIP-53F5-0002',18,1,'2026-05-17 03:17:38'),(25,'LIP-53F5-0002',11,1,'2026-05-17 03:17:38'),(26,'LIP-53F5-0002',7,1,'2026-05-17 03:17:38'),(27,'LIP-53F5-0002',25,1,'2026-05-17 03:17:38'),(28,'LIP-53F5-0002',28,1,'2026-05-17 03:17:38');
/*!40000 ALTER TABLE `local_votes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `positions` (
  `id` int NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `max_votes` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
INSERT INTO `positions` VALUES (1,'Mayor',1),(11,'Vice Mayor',1),(12,'City Councilor',12);
/*!40000 ALTER TABLE `positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `key` varchar(50) NOT NULL,
  `value` text,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('city_id','1'),('city_name','Lipa City');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-17 11:22:01
