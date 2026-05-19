-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: db_countera
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
-- Current Database: `db_countera`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `db_countera` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `db_countera`;

--
-- Table structure for table `ballots`
--

DROP TABLE IF EXISTS `ballots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ballots` (
  `id` int NOT NULL AUTO_INCREMENT,
  `city_id` int NOT NULL,
  `ballot_number` varchar(20) NOT NULL,
  `status` enum('unused','used') DEFAULT 'unused',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ballot_number` (`ballot_number`),
  KEY `city_id` (`city_id`),
  CONSTRAINT `ballots_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ballots`
--

LOCK TABLES `ballots` WRITE;
/*!40000 ALTER TABLE `ballots` DISABLE KEYS */;
/*!40000 ALTER TABLE `ballots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `candidates`
--

DROP TABLE IF EXISTS `candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `position_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `party_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `position_id` (`position_id`),
  KEY `party_id` (`party_id`),
  CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `candidates_ibfk_2` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidates`
--

LOCK TABLES `candidates` WRITE;
/*!40000 ALTER TABLE `candidates` DISABLE KEYS */;
INSERT INTO `candidates` VALUES (1,1,'Ramon Dela Cruz',1),(2,1,'Eduardo Villanueva',2),(3,11,'Lorenzo Santos',1),(4,11,'Miguel Bautista',2),(5,12,'Antonio Reyes',1),(6,12,'Jose Mercado',1),(7,12,'Paolo Fernandez',1),(8,12,'Mark Anthony Lim',1),(9,12,'Carlo Mendoza',1),(10,12,'Ryan Torres',1),(11,12,'Nathaniel Cruz',1),(12,12,'Patrick Valdez',1),(13,12,'Vincent Navarro',1),(14,12,'Kenneth Garcia',1),(15,12,'Jerome Castillo',1),(16,12,'Francis Alcantara',1),(17,12,'Daniel Robles',2),(18,12,'Leo Marquez',2),(19,12,'Adrian Pineda',2),(20,12,'Brian Solis',2),(21,12,'Harold Aquino',2),(22,12,'Julius Navarro',2),(23,12,'Emmanuel Rojas',2),(24,12,'Kevin Bautista',2),(25,12,'Rafael Dominguez',2),(26,12,'Gilbert Ramos',2),(27,12,'Noel Serrano',2),(28,12,'Victor Salazar',2);
/*!40000 ALTER TABLE `candidates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cities`
--

DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `councilor_limit` int DEFAULT '12',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cities`
--

LOCK TABLES `cities` WRITE;
/*!40000 ALTER TABLE `cities` DISABLE KEYS */;
INSERT INTO `cities` VALUES (1,'Lipa City',12,'2026-05-11 09:34:13');
/*!40000 ALTER TABLE `cities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parties`
--

DROP TABLE IF EXISTS `parties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parties` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `alias` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parties`
--

LOCK TABLES `parties` WRITE;
/*!40000 ALTER TABLE `parties` DISABLE KEYS */;
INSERT INTO `parties` VALUES (1,'Party1','P1','2026-05-14 08:11:18'),(2,'Party2','P2','2026-05-15 13:07:23');
/*!40000 ALTER TABLE `parties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `positions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `city_id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `max_votes` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `city_id` (`city_id`),
  CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
INSERT INTO `positions` VALUES (1,1,'Mayor',1),(11,1,'Vice Mayor',1),(12,1,'City Councilor',12);
/*!40000 ALTER TABLE `positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `result_import_logs`
--

DROP TABLE IF EXISTS `result_import_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `result_import_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `city_id` int NOT NULL,
  `import_key` varchar(64) NOT NULL,
  `expected_votes` int NOT NULL DEFAULT '0',
  `received_votes` int NOT NULL DEFAULT '0',
  `method` enum('3g','manual') NOT NULL,
  `status` enum('pending','accepted','rejected','duplicate') NOT NULL DEFAULT 'pending',
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_import_key` (`import_key`),
  KEY `idx_city` (`city_id`),
  CONSTRAINT `fk_import_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `result_import_logs`
--

LOCK TABLES `result_import_logs` WRITE;
/*!40000 ALTER TABLE `result_import_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `result_import_logs` ENABLE KEYS */;
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
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$12$atEt18kdmlwudLussr1VsOrzjZYCgMwChwpwQ/YeHlJdPyeoyaY0a','2026-05-11 09:39:26');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `votes`
--

DROP TABLE IF EXISTS `votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ballot_id` int NOT NULL,
  `candidate_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ballot_id` (`ballot_id`),
  KEY `candidate_id` (`candidate_id`),
  CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`ballot_id`) REFERENCES `ballots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `votes`
--

LOCK TABLES `votes` WRITE;
/*!40000 ALTER TABLE `votes` DISABLE KEYS */;
/*!40000 ALTER TABLE `votes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-19 15:24:56
