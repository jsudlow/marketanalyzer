-- MySQL dump 10.13  Distrib 5.5.9, for Win32 (x86)
--
-- Host: localhost    Database: abbastoons_stock
-- ------------------------------------------------------
-- Server version	5.5.29

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `abbastoons_stock`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `abbastoons_stock` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `abbastoons_stock`;

--
-- Table structure for table `stock_snapshot`
--

DROP TABLE IF EXISTS `stock_snapshot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_snapshot` (
  `idstock_snapshot` int(11) NOT NULL AUTO_INCREMENT,
  `ticker_symbol` varchar(45) DEFAULT NULL,
  `date_captured` date DEFAULT NULL,
  `hi` decimal(8,2) DEFAULT NULL,
  `low` decimal(8,2) DEFAULT NULL,
  `open` decimal(6,2) DEFAULT NULL,
  `close` decimal(6,2) DEFAULT NULL,
  PRIMARY KEY (`idstock_snapshot`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_snapshot`
--

LOCK TABLES `stock_snapshot` WRITE;
/*!40000 ALTER TABLE `stock_snapshot` DISABLE KEYS */;
INSERT INTO `stock_snapshot` VALUES (1,'fb','2014-08-21',75.19,74.41,NULL,NULL),(2,'twtr','2014-08-21',45.35,44.84,NULL,NULL),(3,'pgf','2014-08-21',18.18,18.13,NULL,NULL),(4,'twtr','2014-08-24',46.14,44.80,NULL,NULL),(5,'fb','2014-08-24',74.73,73.57,NULL,NULL),(6,'mcd','2014-08-24',94.86,94.27,NULL,NULL),(7,'pgf','2014-08-24',18.18,18.15,NULL,NULL),(8,'slv','2014-08-24',18.75,18.54,NULL,NULL),(9,'fb','2014-08-25',75.28,74.79,NULL,NULL),(10,'twtr','2014-08-25',46.36,45.70,NULL,NULL),(11,'mcd','2014-08-25',95.37,94.41,NULL,NULL),(12,'fb','2014-08-19',75.28,74.79,NULL,NULL),(13,'fb','2014-08-20',75.28,74.79,NULL,NULL),(14,'fb','2014-08-22',75.28,74.79,NULL,NULL),(15,'fb','2014-08-23',75.28,74.79,NULL,NULL),(16,'mcd','2014-08-30',94.20,93.51,NULL,NULL),(17,'fb','2014-08-30',74.82,74.01,NULL,NULL),(18,'mcd','2014-09-03',93.49,93.04,93.27,92.80),(20,'twtr','2014-09-03',51.85,49.05,51.83,49.33),(21,'fb','2014-09-03',77.48,75.60,77.14,75.83),(22,'mcd','2014-10-08',93.95,92.81,93.08,93.83),(23,'pgf','2014-10-08',18.06,17.99,18.00,18.05),(24,'amd','2014-10-08',3.31,3.18,3.30,3.28),(25,'del','2014-10-08',63.79,61.96,62.56,63.68);
/*!40000 ALTER TABLE `stock_snapshot` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-10-09  0:14:11
