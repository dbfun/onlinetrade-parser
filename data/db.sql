-- MySQL dump 10.13  Distrib 5.5.51-38.1, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: dev_onlinetrade_parser
-- ------------------------------------------------------
-- Server version	5.5.51-38.1

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
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(32) NOT NULL DEFAULT '',
  `picture` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  PRIMARY KEY (`name`),
  UNIQUE KEY `idx_u` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `distributor_id` int(11) NOT NULL DEFAULT '0',
  `old_sku` char(32) NOT NULL DEFAULT '',
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `new_sku` int(11) NOT NULL DEFAULT '0',
  `parent_sku` char(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `uri` varchar(255) NOT NULL DEFAULT '',
  `picture` varchar(255) NOT NULL DEFAULT '',
  `description` mediumtext NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `idx_dist_sku` (`distributor_id`,`old_sku`,`id`),
  KEY `idx_old_sku` (`old_sku`)
) ENGINE=InnoDB AUTO_INCREMENT=1255 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `items` (
  `distributor_id` int(11) NOT NULL DEFAULT '0',
  `old_sku` char(32) NOT NULL DEFAULT '',
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `new_sku` char(32) NOT NULL DEFAULT '',
  `cat_sku` char(32) NOT NULL DEFAULT '',
  `brand_sku` char(64) NOT NULL DEFAULT '',
  `brand_code` varchar(255) NOT NULL DEFAULT '',
  `brand_country` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `price` double(10,3) NOT NULL DEFAULT '0.000',
  `pictures` mediumtext NOT NULL,
  `description` mediumtext NOT NULL,
  `meta_description` text NOT NULL,
  `guaranty` varchar(255) NOT NULL,
  `options` mediumtext NOT NULL,
  `complectation` mediumtext NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `idx_dist_sku` (`distributor_id`,`old_sku`,`id`),
  KEY `idx_brand_sku` (`brand_sku`),
  KEY `idx_cat_sku` (`cat_sku`)
) ENGINE=InnoDB AUTO_INCREMENT=42794 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping events for database 'dev_onlinetrade_parser'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-03-30 20:46:44
