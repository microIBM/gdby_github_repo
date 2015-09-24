-- MySQL dump 10.13  Distrib 5.5.37, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: d_dachuwang
-- ------------------------------------------------------
-- Server version	5.5.37-0ubuntu0.14.04.1

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
-- Table structure for table `t_product`
--

DROP TABLE IF EXISTS `t_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_product` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类编号',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商家id',
  `unit_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '计量单位id',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
  `sub_title` varchar(255) NOT NULL DEFAULT '' COMMENT '商品子名称',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '商品描述',
  `pic_url` varchar(255) NOT NULL DEFAULT '' COMMENT '商品图片',
  `weight` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品权重',
  `suttle` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品净重',
  `spec` varchar(255) NOT NULL DEFAULT '' COMMENT '商品规格描述',
  `price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品价格',
  `market_price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品市场价格',
  `status` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品状态0-禁用1-已通过2-待审',
  `created_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品创建时间',
  `updated_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品更新时间',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `user_id` (`user_id`),
  KEY `unit_id` (`unit_id`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `t_product`
--

LOCK TABLES `t_product` WRITE;
/*!40000 ALTER TABLE `t_product` DISABLE KEYS */;
/*!40000 ALTER TABLE `t_product` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-03-04 15:02:25
