-- MySQL dump 10.13  Distrib 5.5.35, for Linux (x86_64)
--
-- Host: localhost    Database: gaia
-- ------------------------------------------------------
-- Server version	5.5.35

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
-- Table structure for table `GAIA_ATOM_CAMPAIGN_USED_HISTORY`
--

DROP TABLE IF EXISTS `GAIA_ATOM_CAMPAIGN_USED_HISTORY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_ATOM_CAMPAIGN_USED_HISTORY` (
  `atom_campaign_used_history_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `atom_campaign_id` int(11) NOT NULL,
  `serial_code` char(40) COLLATE utf8_unicode_ci NOT NULL,
  `serial_code_crc32` int(10) unsigned NOT NULL COMMENT 'serial_codeの検索インデックス用カラム。\nserial_codeをハッシュ化した値を格納する。\n値の衝突が起こるので、検索時にはハッシュ化していない値も指定する必要がある。',
  `created_time` datetime NOT NULL,
  PRIMARY KEY (`atom_campaign_used_history_id`,`created_time`),
  KEY `idx_userid_campaignid` (`user_id`,`atom_campaign_id`) COMMENT ' /* comment truncated */ /*ユーザーが該当のキャンペーンを利用済みかどうかの検索に利用する目的のインデックス*/',
  KEY `idx_atomcampaignid_serialcodecrc32` (`atom_campaign_id`,`serial_code_crc32`) COMMENT ' /* comment truncated */ /*該当のキャンペーンにおいてそのシリアルコードが利用済みかどうかを検索するためのインデックス*/',
  KEY `fk_campaign_log_serial_user_account1_idx` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
/*!50100 PARTITION BY RANGE (TO_DAYS(created_time))
(PARTITION pmax VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_ATOM_CAMPAIGN_USED_HISTORY`
--

LOCK TABLES `GAIA_ATOM_CAMPAIGN_USED_HISTORY` WRITE;
/*!40000 ALTER TABLE `GAIA_ATOM_CAMPAIGN_USED_HISTORY` DISABLE KEYS */;
INSERT INTO `GAIA_ATOM_CAMPAIGN_USED_HISTORY` VALUES (1,1,1,'gaia.sample.serial',2316646757,'2014-01-22 06:00:31'),(2,1,1,'gaia.sample.serial',2316646757,'2014-01-22 06:00:35');
/*!40000 ALTER TABLE `GAIA_ATOM_CAMPAIGN_USED_HISTORY` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_BBS`
--

DROP TABLE IF EXISTS `GAIA_BBS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_BBS` (
  `bbs_id` int(11) NOT NULL AUTO_INCREMENT,
  `bbs_type_id` tinyint(4) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT '管理側で識別するための名前',
  `created_time` datetime NOT NULL,
  `deleted_time` datetime DEFAULT NULL,
  PRIMARY KEY (`bbs_id`),
  KEY `idx_deletedtime_bbstypeid` (`deleted_time`,`bbs_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_BBS`
--

LOCK TABLES `GAIA_BBS` WRITE;
/*!40000 ALTER TABLE `GAIA_BBS` DISABLE KEYS */;
INSERT INTO `GAIA_BBS` VALUES (1,1,'test','2014-03-15 17:30:26',NULL),(2,1,'test2','2014-03-15 17:30:28',NULL),(3,1,'test3','2014-03-15 17:31:00',NULL),(4,2,'pre','2014-03-17 09:17:07',NULL),(5,2,'pre2','2014-03-17 09:17:14',NULL),(6,2,'pre3','2014-03-17 09:17:17',NULL);
/*!40000 ALTER TABLE `GAIA_BBS` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_BBS_BLACK_LISTED_USER`
--

DROP TABLE IF EXISTS `GAIA_BBS_BLACK_LISTED_USER`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_BBS_BLACK_LISTED_USER` (
  `bbs_id` int(11) NOT NULL,
  `black_listed_user_id` bigint(20) NOT NULL,
  `created_time` datetime NOT NULL,
  PRIMARY KEY (`bbs_id`,`black_listed_user_id`),
  KEY `fk_GAIA_BBS_BLACK_LISTED_USER_GAIA_USER_ACCOUNT1_idx` (`black_listed_user_id`),
  KEY `idx_bbsid_createdtime` (`bbs_id`,`created_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_BBS_BLACK_LISTED_USER`
--

LOCK TABLES `GAIA_BBS_BLACK_LISTED_USER` WRITE;
/*!40000 ALTER TABLE `GAIA_BBS_BLACK_LISTED_USER` DISABLE KEYS */;
INSERT INTO `GAIA_BBS_BLACK_LISTED_USER` VALUES (1,4,'2014-03-15 20:56:48'),(1,5,'2014-03-15 20:56:56');
/*!40000 ALTER TABLE `GAIA_BBS_BLACK_LISTED_USER` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_BBS_BLACK_LISTED_USER_HISTORY`
--

DROP TABLE IF EXISTS `GAIA_BBS_BLACK_LISTED_USER_HISTORY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_BBS_BLACK_LISTED_USER_HISTORY` (
  `bbs_black_listed_user_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `bbs_black_list_action_type_id` tinyint(4) NOT NULL,
  `action_user_id` bigint(20) NOT NULL,
  `bbs_id` int(11) NOT NULL,
  `black_listed_user_id` bigint(20) NOT NULL,
  `created_time` datetime NOT NULL,
  PRIMARY KEY (`bbs_black_listed_user_history_id`),
  KEY `fk_GAIA_BBS_BLACK_LISTED_USER_HISTORY_GAIA_USER_ACCOUNT1_idx` (`action_user_id`),
  KEY `fk_GAIA_BBS_BLACK_LISTED_USER_HISTORY_GAIA_USER_ACCOUNT2_idx` (`black_listed_user_id`),
  KEY `fk_GAIA_BBS_BLACK_LISTED_USER_HISTORY_GAIA_BBS1_idx` (`bbs_id`),
  KEY `idx_bbsid_createdtime` (`bbs_id`,`created_time`),
  KEY `idx_blacklisteduserid_createdtime` (`black_listed_user_id`,`created_time`),
  KEY `fk_GAIA_MST_BBS_BLACK_LIST_ACTION_TYPE_idx` (`bbs_black_list_action_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_BBS_BLACK_LISTED_USER_HISTORY`
--

LOCK TABLES `GAIA_BBS_BLACK_LISTED_USER_HISTORY` WRITE;
/*!40000 ALTER TABLE `GAIA_BBS_BLACK_LISTED_USER_HISTORY` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_BBS_BLACK_LISTED_USER_HISTORY` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_BBS_NON_THREAD_MESSAGE`
--

DROP TABLE IF EXISTS `GAIA_BBS_NON_THREAD_MESSAGE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_BBS_NON_THREAD_MESSAGE` (
  `bbs_message_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `bbs_id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `post_time` datetime NOT NULL,
  `created_time` datetime NOT NULL,
  `updated_time` datetime NOT NULL,
  `deleted_time` datetime DEFAULT NULL,
  PRIMARY KEY (`bbs_message_id`),
  KEY `fk_GAIA_NON_THREAD_BBS_MESSAGE_GAIA_USER_ACCOUNT1_idx` (`user_id`),
  KEY `fk_bbsid_idx` (`bbs_id`),
  KEY `idx_bbsid_deletedtime_posttime` (`bbs_id`,`deleted_time`,`post_time`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_BBS_NON_THREAD_MESSAGE`
--

LOCK TABLES `GAIA_BBS_NON_THREAD_MESSAGE` WRITE;
/*!40000 ALTER TABLE `GAIA_BBS_NON_THREAD_MESSAGE` DISABLE KEYS */;
INSERT INTO `GAIA_BBS_NON_THREAD_MESSAGE` VALUES (1,4,1,'事前投稿1','2014-03-17 18:17:21','2014-03-17 09:17:21','2014-03-17 09:17:21',NULL),(2,4,1,'事前投稿2','2014-03-17 18:17:24','2014-03-17 09:17:24','2014-03-17 09:17:24',NULL),(3,5,1,'事前投稿3','2014-03-17 18:17:27','2014-03-17 09:17:27','2014-03-17 09:17:27',NULL),(4,6,1,'事前投稿4','2014-03-17 18:17:30','2014-03-17 09:17:30','2014-03-17 09:17:30',NULL);
/*!40000 ALTER TABLE `GAIA_BBS_NON_THREAD_MESSAGE` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_BBS_THREAD`
--

DROP TABLE IF EXISTS `GAIA_BBS_THREAD`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_BBS_THREAD` (
  `bbs_thread_id` int(11) NOT NULL AUTO_INCREMENT,
  `bbs_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'スレッドタイトル、',
  `last_post_time` datetime DEFAULT NULL COMMENT 'スレッドに最後のメッセージが投稿された日時のキャッシュ。最後に投稿されたmessageのcreated_timeと同じになる想定。スレッドをメッセージ投稿日時順の表示にする際にあったほうがよい。',
  `deleted_time` datetime DEFAULT NULL,
  PRIMARY KEY (`bbs_thread_id`),
  KEY `idx_threadid_deletedtime_bbsid` (`bbs_thread_id`,`deleted_time`,`bbs_id`),
  KEY `idx_bbsid_deletedtime_lastposttime` (`bbs_id`,`deleted_time`,`last_post_time`),
  KEY `fk_bbsid_idx` (`bbs_id`),
  KEY `idx_deletedtime_lastposttime` (`deleted_time`,`last_post_time`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_BBS_THREAD`
--

LOCK TABLES `GAIA_BBS_THREAD` WRITE;
/*!40000 ALTER TABLE `GAIA_BBS_THREAD` DISABLE KEYS */;
INSERT INTO `GAIA_BBS_THREAD` VALUES (1,1,'テストスレ1','2014-03-16 02:59:52',NULL),(2,1,'テストスレ2','2014-03-16 03:00:01',NULL),(3,2,'テストスレ3',NULL,NULL);
/*!40000 ALTER TABLE `GAIA_BBS_THREAD` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_BBS_THREAD_MESSAGE`
--

DROP TABLE IF EXISTS `GAIA_BBS_THREAD_MESSAGE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_BBS_THREAD_MESSAGE` (
  `bbs_message_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `bbs_thread_id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `post_time` datetime NOT NULL,
  `created_time` datetime NOT NULL,
  `updated_time` datetime NOT NULL,
  `deleted_time` datetime DEFAULT NULL,
  PRIMARY KEY (`bbs_message_id`),
  KEY `fk_GAIA_BBS_THREAD_MESSAGE_GAIA_USER_ACCOUNT1_idx` (`user_id`),
  KEY `idx_threadid_deletedtime_posttime` (`bbs_thread_id`,`deleted_time`,`post_time`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_BBS_THREAD_MESSAGE`
--

LOCK TABLES `GAIA_BBS_THREAD_MESSAGE` WRITE;
/*!40000 ALTER TABLE `GAIA_BBS_THREAD_MESSAGE` DISABLE KEYS */;
INSERT INTO `GAIA_BBS_THREAD_MESSAGE` VALUES (1,1,1,'message1','2014-03-16 02:59:44','2014-03-15 17:59:44','2014-03-15 17:59:44',NULL),(2,1,1,'message2','2014-03-16 02:59:49','2014-03-15 17:59:49','2014-03-15 17:59:49',NULL),(3,1,1,'message3','2014-03-16 02:59:52','2014-03-15 17:59:52','2014-03-15 17:59:52',NULL),(4,2,1,'message4','2014-03-16 03:00:01','2014-03-15 18:00:01','2014-03-15 18:00:01',NULL);
/*!40000 ALTER TABLE `GAIA_BBS_THREAD_MESSAGE` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_GACHA_HISTORY`
--

DROP TABLE IF EXISTS `GAIA_GACHA_HISTORY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_GACHA_HISTORY` (
  `gacha_history_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'ガチャ履歴',
  `user_id` bigint(20) NOT NULL COMMENT 'ユーザID',
  `gacha_id` int(11) NOT NULL COMMENT 'ガチャID',
  `asset_type_id` int(11) NOT NULL COMMENT 'アイテムタイプID',
  `asset_id` bigint(20) NOT NULL COMMENT '取得カードID',
  `asset_count` int(11) NOT NULL,
  `created_time` datetime NOT NULL COMMENT '実行日時',
  PRIMARY KEY (`gacha_history_id`,`created_time`),
  KEY `fk_gachaid` (`gacha_id`),
  KEY `user_id_idx` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='ガチャ履歴'
/*!50100 PARTITION BY RANGE (TO_DAYS(created_time))
(PARTITION pmax VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_GACHA_HISTORY`
--

LOCK TABLES `GAIA_GACHA_HISTORY` WRITE;
/*!40000 ALTER TABLE `GAIA_GACHA_HISTORY` DISABLE KEYS */;
INSERT INTO `GAIA_GACHA_HISTORY` VALUES (1,0,1,1,1,1,'2014-01-22 15:53:44'),(2,0,1,1,1,1,'2014-01-22 15:53:44'),(3,0,1,1,2,1,'2014-01-22 15:53:44'),(4,0,1,1,2,1,'2014-01-22 15:53:44'),(5,0,1,1,2,1,'2014-01-22 15:53:44'),(6,0,1,1,1,1,'2014-01-22 15:53:44'),(7,0,1,1,1,1,'2014-01-22 15:53:44'),(8,0,1,1,1,1,'2014-01-22 15:53:44'),(9,0,1,1,1,1,'2014-01-22 15:53:44'),(10,0,1,1,1,1,'2014-01-22 15:53:44'),(11,0,1,1,1,1,'2014-01-22 15:53:49'),(12,0,1,1,1,1,'2014-01-22 15:53:49'),(13,0,1,1,2,1,'2014-01-22 15:53:49'),(14,0,1,1,2,1,'2014-01-22 15:53:49'),(15,0,1,1,1,1,'2014-01-22 15:53:49'),(16,0,1,1,2,1,'2014-01-22 15:53:49'),(17,0,1,1,2,1,'2014-01-22 15:53:49'),(18,0,1,1,1,1,'2014-01-22 15:53:49'),(19,0,1,1,1,1,'2014-01-22 15:53:49'),(20,0,1,1,1,1,'2014-01-22 15:53:49'),(21,0,2,1,3,1,'2014-01-22 15:54:09'),(22,0,2,1,5,1,'2014-01-22 15:54:11');
/*!40000 ALTER TABLE `GAIA_GACHA_HISTORY` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MNT_USER_ADMIN`
--

DROP TABLE IF EXISTS `GAIA_MNT_USER_ADMIN`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MNT_USER_ADMIN` (
  `admin_user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `login_id` varchar(50) CHARACTER SET utf8 NOT NULL,
  `password` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `created_time` datetime NOT NULL,
  `created_admin_id` bigint(20) NOT NULL,
  `updated_time` datetime NOT NULL,
  `updated_admin_id` bigint(20) NOT NULL,
  PRIMARY KEY (`admin_user_id`),
  UNIQUE KEY `login_id_UNIQUE` (`login_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MNT_USER_ADMIN`
--

LOCK TABLES `GAIA_MNT_USER_ADMIN` WRITE;
/*!40000 ALTER TABLE `GAIA_MNT_USER_ADMIN` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_MNT_USER_ADMIN` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MNT_USER_ADMIN_ROLE`
--

DROP TABLE IF EXISTS `GAIA_MNT_USER_ADMIN_ROLE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MNT_USER_ADMIN_ROLE` (
  `admin_user_id` bigint(20) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_time` datetime NOT NULL,
  `created_admin_id` bigint(20) NOT NULL,
  `updated_time` datetime NOT NULL,
  `updated_admin_id` bigint(20) NOT NULL,
  PRIMARY KEY (`admin_user_id`,`role_id`),
  KEY `fk_gaia_mnt_user_admin_role_admin_user_id_idx` (`admin_user_id`),
  KEY `fk_gaia_mnt_user_admin_role_role_id1_idx` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MNT_USER_ADMIN_ROLE`
--

LOCK TABLES `GAIA_MNT_USER_ADMIN_ROLE` WRITE;
/*!40000 ALTER TABLE `GAIA_MNT_USER_ADMIN_ROLE` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_MNT_USER_ADMIN_ROLE` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MNT_USER_ADMIN_ROLE_PLIVILEGE`
--

DROP TABLE IF EXISTS `GAIA_MNT_USER_ADMIN_ROLE_PLIVILEGE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MNT_USER_ADMIN_ROLE_PLIVILEGE` (
  `role_id` int(11) NOT NULL,
  `privilege_id` int(11) NOT NULL,
  `created_time` datetime NOT NULL,
  `created_admin_id` bigint(20) NOT NULL,
  `updated_time` datetime NOT NULL,
  `updated_admin_id` bigint(20) NOT NULL,
  PRIMARY KEY (`role_id`,`privilege_id`),
  KEY `fk_gaia_mnt_user_admin_role_plivilege_role_id_idx` (`role_id`),
  KEY `fk_gaia_mnt_user_admin_role_plivilege_privilege_id1_idx` (`privilege_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MNT_USER_ADMIN_ROLE_PLIVILEGE`
--

LOCK TABLES `GAIA_MNT_USER_ADMIN_ROLE_PLIVILEGE` WRITE;
/*!40000 ALTER TABLE `GAIA_MNT_USER_ADMIN_ROLE_PLIVILEGE` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_MNT_USER_ADMIN_ROLE_PLIVILEGE` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_ASSET_TYPE`
--

DROP TABLE IF EXISTS `GAIA_MST_ASSET_TYPE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_ASSET_TYPE` (
  `asset_type_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'アイテムタイプID',
  `asset_type` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT 'アイテムタイプ名',
  PRIMARY KEY (`asset_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='アイテムタイプマスタ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_ASSET_TYPE`
--

LOCK TABLES `GAIA_MST_ASSET_TYPE` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_ASSET_TYPE` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_MST_ASSET_TYPE` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_ATOM_CAMPAIGN`
--

DROP TABLE IF EXISTS `GAIA_MST_ATOM_CAMPAIGN`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_ATOM_CAMPAIGN` (
  `atom_campaign_id` int(11) NOT NULL AUTO_INCREMENT,
  `atom_campaign_type_id` int(11) NOT NULL,
  `campaign_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `campaign_code` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `acceptance_message` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `check_type` int(11) NOT NULL,
  PRIMARY KEY (`atom_campaign_id`),
  UNIQUE KEY `uqidx_campaigncode` (`campaign_code`) COMMENT ' /* comment truncated */ /*分量が少ないため、crc32ハッシュしたカラムは用意しない*/',
  KEY `fk_campaigntypeid_idx` (`atom_campaign_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_ATOM_CAMPAIGN`
--

LOCK TABLES `GAIA_MST_ATOM_CAMPAIGN` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_ATOM_CAMPAIGN` DISABLE KEYS */;
INSERT INTO `GAIA_MST_ATOM_CAMPAIGN` VALUES (1,1,'GAIA利用サンプル','gaia.sample','GAIA利用サンプルコードが認証されました',0);
/*!40000 ALTER TABLE `GAIA_MST_ATOM_CAMPAIGN` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_ATOM_CAMPAIGN_PRESENT`
--

DROP TABLE IF EXISTS `GAIA_MST_ATOM_CAMPAIGN_PRESENT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_ATOM_CAMPAIGN_PRESENT` (
  `atom_campaign_present_id` int(11) NOT NULL AUTO_INCREMENT,
  `atom_campaign_id` int(11) NOT NULL,
  `asset_type_id` int(11) NOT NULL,
  `asset_id` bigint(20) NOT NULL,
  `asset_count` int(11) NOT NULL,
  `present_message` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`atom_campaign_present_id`),
  KEY `fk_mst_campaign_present_mst_campaign_idx` (`atom_campaign_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_ATOM_CAMPAIGN_PRESENT`
--

LOCK TABLES `GAIA_MST_ATOM_CAMPAIGN_PRESENT` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_ATOM_CAMPAIGN_PRESENT` DISABLE KEYS */;
INSERT INTO `GAIA_MST_ATOM_CAMPAIGN_PRESENT` VALUES (1,1,1,1,1,'キャンペーン[GAIA利用サンプル]利用の特典です。');
/*!40000 ALTER TABLE `GAIA_MST_ATOM_CAMPAIGN_PRESENT` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_ATOM_CAMPAIGN_TYPE`
--

DROP TABLE IF EXISTS `GAIA_MST_ATOM_CAMPAIGN_TYPE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_ATOM_CAMPAIGN_TYPE` (
  `atom_campaign_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_type_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`atom_campaign_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_ATOM_CAMPAIGN_TYPE`
--

LOCK TABLES `GAIA_MST_ATOM_CAMPAIGN_TYPE` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_ATOM_CAMPAIGN_TYPE` DISABLE KEYS */;
INSERT INTO `GAIA_MST_ATOM_CAMPAIGN_TYPE` VALUES (1,'preregister'),(2,'invite'),(3,'normal');
/*!40000 ALTER TABLE `GAIA_MST_ATOM_CAMPAIGN_TYPE` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_BBS_BLACK_LIST_ACTION_TYPE`
--

DROP TABLE IF EXISTS `GAIA_MST_BBS_BLACK_LIST_ACTION_TYPE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_BBS_BLACK_LIST_ACTION_TYPE` (
  `bbs_black_list_action_type_id` tinyint(4) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`bbs_black_list_action_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_BBS_BLACK_LIST_ACTION_TYPE`
--

LOCK TABLES `GAIA_MST_BBS_BLACK_LIST_ACTION_TYPE` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_BBS_BLACK_LIST_ACTION_TYPE` DISABLE KEYS */;
INSERT INTO `GAIA_MST_BBS_BLACK_LIST_ACTION_TYPE` VALUES (1,'add'),(2,'remove');
/*!40000 ALTER TABLE `GAIA_MST_BBS_BLACK_LIST_ACTION_TYPE` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_FRIEND_ACTION_TYPE`
--

DROP TABLE IF EXISTS `GAIA_MST_FRIEND_ACTION_TYPE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_FRIEND_ACTION_TYPE` (
  `friend_action_type_id` tinyint(4) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`friend_action_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_FRIEND_ACTION_TYPE`
--

LOCK TABLES `GAIA_MST_FRIEND_ACTION_TYPE` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_FRIEND_ACTION_TYPE` DISABLE KEYS */;
INSERT INTO `GAIA_MST_FRIEND_ACTION_TYPE` VALUES (1,'accept'),(2,'remove');
/*!40000 ALTER TABLE `GAIA_MST_FRIEND_ACTION_TYPE` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_FRIEND_OFFER_ACTION_TYPE`
--

DROP TABLE IF EXISTS `GAIA_MST_FRIEND_OFFER_ACTION_TYPE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_FRIEND_OFFER_ACTION_TYPE` (
  `friend_offer_action_type_id` tinyint(4) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`friend_offer_action_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_FRIEND_OFFER_ACTION_TYPE`
--

LOCK TABLES `GAIA_MST_FRIEND_OFFER_ACTION_TYPE` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_FRIEND_OFFER_ACTION_TYPE` DISABLE KEYS */;
INSERT INTO `GAIA_MST_FRIEND_OFFER_ACTION_TYPE` VALUES (1,'offer'),(2,'accept'),(3,'deny');
/*!40000 ALTER TABLE `GAIA_MST_FRIEND_OFFER_ACTION_TYPE` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_GACHA`
--

DROP TABLE IF EXISTS `GAIA_MST_GACHA`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_GACHA` (
  `gacha_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ガチャID',
  `gacha_type_id` int(11) NOT NULL COMMENT 'ガチャタイプID',
  `gacha_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '名前',
  `effective_from` datetime NOT NULL COMMENT '利用可能期間 from',
  `effective_to` datetime NOT NULL COMMENT '利用可能期間 to',
  PRIMARY KEY (`gacha_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='ガチャマスタ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_GACHA`
--

LOCK TABLES `GAIA_MST_GACHA` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_GACHA` DISABLE KEYS */;
INSERT INTO `GAIA_MST_GACHA` VALUES (1,1,'ノーマルガチャ','1000-01-01 00:00:00','9999-12-31 00:00:00'),(2,1,'レアガチャ','1000-01-01 00:00:00','9999-12-31 00:00:00');
/*!40000 ALTER TABLE `GAIA_MST_GACHA` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_GACHA_CARD`
--

DROP TABLE IF EXISTS `GAIA_MST_GACHA_CARD`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_GACHA_CARD` (
  `gacha_card_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '一意なキー',
  `gacha_group_id` int(11) NOT NULL COMMENT 'ガチャグループID',
  `asset_type_id` int(11) NOT NULL COMMENT 'アセットタイプID（通常はカード関連になるはず）',
  `asset_id` bigint(20) NOT NULL COMMENT 'カードID',
  `asset_count` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`gacha_card_id`),
  KEY `fk_gachagroupid` (`gacha_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='ガチャ排出カードマスタ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_GACHA_CARD`
--

LOCK TABLES `GAIA_MST_GACHA_CARD` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_GACHA_CARD` DISABLE KEYS */;
INSERT INTO `GAIA_MST_GACHA_CARD` VALUES (1,1,1,1,1),(2,1,1,2,1),(3,2,1,3,1),(4,2,1,4,1),(5,3,1,5,1);
/*!40000 ALTER TABLE `GAIA_MST_GACHA_CARD` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_GACHA_GROUP`
--

DROP TABLE IF EXISTS `GAIA_MST_GACHA_GROUP`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_GACHA_GROUP` (
  `gacha_group_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ガチャグループID',
  `gacha_id` int(11) NOT NULL COMMENT 'ガチャID',
  `rarity` int(11) NOT NULL COMMENT 'ランク',
  `rate` decimal(4,3) NOT NULL COMMENT '排出確率',
  PRIMARY KEY (`gacha_group_id`),
  KEY `fk_gachaid` (`gacha_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='ガチャランクグループマスタ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_GACHA_GROUP`
--

LOCK TABLES `GAIA_MST_GACHA_GROUP` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_GACHA_GROUP` DISABLE KEYS */;
INSERT INTO `GAIA_MST_GACHA_GROUP` VALUES (1,1,1,1.000),(2,2,2,0.700),(3,2,3,0.300);
/*!40000 ALTER TABLE `GAIA_MST_GACHA_GROUP` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_INVITE_CAMPAIGN_PRESENT`
--

DROP TABLE IF EXISTS `GAIA_MST_INVITE_CAMPAIGN_PRESENT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_INVITE_CAMPAIGN_PRESENT` (
  `atom_campaign_invite_present_id` int(11) NOT NULL AUTO_INCREMENT,
  `atom_campaign_id` int(11) NOT NULL,
  `asset_type_id` int(11) NOT NULL,
  `asset_id` bigint(20) NOT NULL,
  `asset_count` int(11) NOT NULL,
  `present_message` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`atom_campaign_invite_present_id`),
  KEY `fk_atomcampaignid_idx` (`atom_campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_INVITE_CAMPAIGN_PRESENT`
--

LOCK TABLES `GAIA_MST_INVITE_CAMPAIGN_PRESENT` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_INVITE_CAMPAIGN_PRESENT` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_MST_INVITE_CAMPAIGN_PRESENT` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_MNT_USER_ADMIN_PRIVILEGE`
--

DROP TABLE IF EXISTS `GAIA_MST_MNT_USER_ADMIN_PRIVILEGE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_MNT_USER_ADMIN_PRIVILEGE` (
  `privilege_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `created_time` datetime NOT NULL,
  `created_admin_id` bigint(20) NOT NULL,
  `updated_time` datetime NOT NULL,
  `updated_admin_id` bigint(20) NOT NULL,
  PRIMARY KEY (`privilege_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_MNT_USER_ADMIN_PRIVILEGE`
--

LOCK TABLES `GAIA_MST_MNT_USER_ADMIN_PRIVILEGE` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_MNT_USER_ADMIN_PRIVILEGE` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_MST_MNT_USER_ADMIN_PRIVILEGE` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_MNT_USER_ADMIN_ROLE`
--

DROP TABLE IF EXISTS `GAIA_MST_MNT_USER_ADMIN_ROLE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_MNT_USER_ADMIN_ROLE` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `created_time` datetime NOT NULL,
  `created_admin_id` bigint(20) NOT NULL,
  `updated_time` datetime NOT NULL,
  `updated_admin_id` bigint(20) NOT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_MNT_USER_ADMIN_ROLE`
--

LOCK TABLES `GAIA_MST_MNT_USER_ADMIN_ROLE` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_MNT_USER_ADMIN_ROLE` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_MST_MNT_USER_ADMIN_ROLE` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_NOAH_OFFER_PRESENT`
--

DROP TABLE IF EXISTS `GAIA_MST_NOAH_OFFER_PRESENT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_NOAH_OFFER_PRESENT` (
  `noah_offer_present_id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_type_id` int(11) NOT NULL,
  `asset_id` bigint(20) NOT NULL,
  `effective_from` datetime NOT NULL,
  `effective_to` datetime NOT NULL,
  PRIMARY KEY (`noah_offer_present_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_NOAH_OFFER_PRESENT`
--

LOCK TABLES `GAIA_MST_NOAH_OFFER_PRESENT` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_NOAH_OFFER_PRESENT` DISABLE KEYS */;
INSERT INTO `GAIA_MST_NOAH_OFFER_PRESENT` VALUES (1,1,1,'1000-01-01 00:00:00','9999-12-31 00:00:00');
/*!40000 ALTER TABLE `GAIA_MST_NOAH_OFFER_PRESENT` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_OS_TYPE`
--

DROP TABLE IF EXISTS `GAIA_MST_OS_TYPE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_OS_TYPE` (
  `os_type_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`os_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_OS_TYPE`
--

LOCK TABLES `GAIA_MST_OS_TYPE` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_OS_TYPE` DISABLE KEYS */;
INSERT INTO `GAIA_MST_OS_TYPE` VALUES (1,'iOS'),(2,'Android');
/*!40000 ALTER TABLE `GAIA_MST_OS_TYPE` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_MST_PURCHASE_ITEM_DATA`
--

DROP TABLE IF EXISTS `GAIA_MST_PURCHASE_ITEM_DATA`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_MST_PURCHASE_ITEM_DATA` (
  `purchase_item_data_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '課金アイテムID',
  `os_type_id` int(11) NOT NULL COMMENT 'OS',
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT '名前',
  `price` decimal(15,3) NOT NULL COMMENT '値段',
  `item_identifier` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '注文ID',
  `asset_type_id` int(11) NOT NULL COMMENT 'アイテムタイプID',
  `asset_id` bigint(20) NOT NULL COMMENT 'アイテムID',
  `asset_count` int(11) NOT NULL COMMENT 'アイテム個数',
  `effective_from` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `effective_to` datetime NOT NULL DEFAULT '9999-12-31 23:59:59',
  PRIMARY KEY (`purchase_item_data_id`),
  KEY `fk_mst_purchase_item_data_os_type_id_idx` (`os_type_id`),
  KEY `idx_ostypeid_effectivetimes_itemidentifier` (`os_type_id`,`effective_from`,`effective_to`,`item_identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='課金アイテムマスタ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_MST_PURCHASE_ITEM_DATA`
--

LOCK TABLES `GAIA_MST_PURCHASE_ITEM_DATA` WRITE;
/*!40000 ALTER TABLE `GAIA_MST_PURCHASE_ITEM_DATA` DISABLE KEYS */;
INSERT INTO `GAIA_MST_PURCHASE_ITEM_DATA` VALUES (1,1,'[iOS]サンプル商品1',100.000,'gaia.ios.purchase.sample1',1,1,1,'1000-01-01 00:00:00','9999-12-31 00:00:00'),(2,1,'[iOS]サンプル商品2',1000.000,'gaia.ios.purchase.sample2',1,1,10,'1000-01-01 00:00:00','9999-12-31 00:00:00'),(3,2,'[Android]サンプル商品1',100.000,'gaia.android.purchase.sample1',1,1,1,'1000-01-01 00:00:00','9999-12-31 00:00:00'),(4,2,'[Android]サンプル商品2',1000.000,'gaia.android.purchase.sample2',1,1,1,'1000-01-01 00:00:00','9999-12-31 00:00:00'),(5,2,'[Android]サンプル商品3',500.000,'jp.wonderplanet.zuma',1,1,1,'1000-01-01 00:00:00','9999-12-31 00:00:00');
/*!40000 ALTER TABLE `GAIA_MST_PURCHASE_ITEM_DATA` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_NOAH_OFFER_HISTORY`
--

DROP TABLE IF EXISTS `GAIA_NOAH_OFFER_HISTORY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_NOAH_OFFER_HISTORY` (
  `noah_offer_history_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'オファー履歴ID',
  `user_id` bigint(20) NOT NULL COMMENT 'ユーザID',
  `action_id` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'アクションID - Noahから送られてくる文字列',
  `user_action_id` bigint(20) NOT NULL COMMENT 'ユーザアクションID - Noahから送られてくる数値',
  `created_time` datetime NOT NULL,
  PRIMARY KEY (`noah_offer_history_id`,`created_time`),
  KEY `idx_userid_actionid` (`user_id`,`action_id`),
  KEY `fk_offer_history_user_id_idx` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='オファー履歴'
/*!50100 PARTITION BY RANGE (TO_DAYS(created_time))
(PARTITION pmax VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_NOAH_OFFER_HISTORY`
--

LOCK TABLES `GAIA_NOAH_OFFER_HISTORY` WRITE;
/*!40000 ALTER TABLE `GAIA_NOAH_OFFER_HISTORY` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_NOAH_OFFER_HISTORY` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_PRESENT_BOX`
--

DROP TABLE IF EXISTS `GAIA_PRESENT_BOX`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_PRESENT_BOX` (
  `present_box_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `status_code` tinyint(4) NOT NULL DEFAULT '0',
  `used_time` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `reason_code` tinyint(4) NOT NULL DEFAULT '0',
  `message` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `asset_type_id` int(11) NOT NULL,
  `asset_id` bigint(20) NOT NULL,
  `asset_count` int(11) NOT NULL,
  `created_time` datetime NOT NULL,
  PRIMARY KEY (`present_box_id`),
  KEY `fk_present_box_item_type_id_idx` (`asset_type_id`),
  KEY `fk_present_box_user_account1_idx` (`user_id`),
  KEY `idx_userid_statuscode` (`user_id`,`status_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_PRESENT_BOX`
--

LOCK TABLES `GAIA_PRESENT_BOX` WRITE;
/*!40000 ALTER TABLE `GAIA_PRESENT_BOX` DISABLE KEYS */;
INSERT INTO `GAIA_PRESENT_BOX` VALUES (1,1,0,'1000-01-01 00:00:00',1,'プレゼント付与のサンプルです',1,1,1,'2014-01-22 16:54:46'),(2,1,0,'1000-01-01 00:00:00',1,'プレゼント付与のサンプルです',1,2,1,'2014-01-22 16:56:58'),(3,1,0,'1000-01-01 00:00:00',1,'プレゼント付与のサンプルです',1,3,1,'2014-01-22 16:57:02');
/*!40000 ALTER TABLE `GAIA_PRESENT_BOX` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_PRESENT_RESERVE`
--

DROP TABLE IF EXISTS `GAIA_PRESENT_RESERVE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_PRESENT_RESERVE` (
  `present_reserve_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `status_code` int(11) NOT NULL,
  `reason_code` int(11) NOT NULL,
  `message` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `asset_type_id` int(11) NOT NULL,
  `asset_id` bigint(20) NOT NULL,
  `asset_count` int(11) NOT NULL,
  `created_time` datetime NOT NULL,
  PRIMARY KEY (`present_reserve_id`),
  KEY `fk_assettype` (`asset_type_id`),
  KEY `fk_present_reserve_user_account1_idx` (`user_id`),
  KEY `idx_userid_statuscode` (`user_id`,`status_code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_PRESENT_RESERVE`
--

LOCK TABLES `GAIA_PRESENT_RESERVE` WRITE;
/*!40000 ALTER TABLE `GAIA_PRESENT_RESERVE` DISABLE KEYS */;
INSERT INTO `GAIA_PRESENT_RESERVE` VALUES (1,1,0,1,'プレゼント予約のサンプルです',1,1,1,'2014-01-22 16:55:57');
/*!40000 ALTER TABLE `GAIA_PRESENT_RESERVE` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_PURCHASE_ANDROID_HISTORY`
--

DROP TABLE IF EXISTS `GAIA_PURCHASE_ANDROID_HISTORY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_PURCHASE_ANDROID_HISTORY` (
  `purchase_history_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '購入履歴ID',
  `user_id` bigint(20) NOT NULL COMMENT 'ユーザID',
  `purchase_item_data_id` int(11) NOT NULL COMMENT '課金アイテムID',
  `order_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '購入番号',
  `created_time` datetime NOT NULL,
  PRIMARY KEY (`purchase_history_id`,`created_time`),
  KEY `paid_item_id_idx` (`purchase_item_data_id`),
  KEY `fk_purchase_android_history_user_account1_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='購入履歴android'
/*!50100 PARTITION BY RANGE (TO_DAYS(created_time))
(PARTITION pmax VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_PURCHASE_ANDROID_HISTORY`
--

LOCK TABLES `GAIA_PURCHASE_ANDROID_HISTORY` WRITE;
/*!40000 ALTER TABLE `GAIA_PURCHASE_ANDROID_HISTORY` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_PURCHASE_ANDROID_HISTORY` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_PURCHASE_IOS_HISTORY`
--

DROP TABLE IF EXISTS `GAIA_PURCHASE_IOS_HISTORY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_PURCHASE_IOS_HISTORY` (
  `purchase_history_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '購入履歴ID',
  `user_id` bigint(20) NOT NULL COMMENT 'ユーザID',
  `purchase_item_data_id` int(11) NOT NULL COMMENT '課金アイテムID',
  `transaction_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '購入番号',
  `created_time` datetime NOT NULL,
  PRIMARY KEY (`purchase_history_id`,`created_time`),
  KEY `paid_item_id_idx` (`purchase_item_data_id`),
  KEY `fk_purchase_ios_history_user_account1_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='購入履歴iOS'
/*!50100 PARTITION BY RANGE (TO_DAYS(created_time))
(PARTITION pmax VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_PURCHASE_IOS_HISTORY`
--

LOCK TABLES `GAIA_PURCHASE_IOS_HISTORY` WRITE;
/*!40000 ALTER TABLE `GAIA_PURCHASE_IOS_HISTORY` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_PURCHASE_IOS_HISTORY` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_USER_ACCOUNT`
--

DROP TABLE IF EXISTS `GAIA_USER_ACCOUNT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_USER_ACCOUNT` (
  `user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '50byte分でOK?',
  `take_over_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `take_over_id_crc32` int(10) unsigned NOT NULL,
  `updated_time` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `created_time` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  PRIMARY KEY (`user_id`),
  KEY `idx_takeoveridcrc32` (`take_over_id_crc32`),
  KEY `idx_uuid_userid` (`uuid`,`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_USER_ACCOUNT`
--

LOCK TABLES `GAIA_USER_ACCOUNT` WRITE;
/*!40000 ALTER TABLE `GAIA_USER_ACCOUNT` DISABLE KEYS */;
INSERT INTO `GAIA_USER_ACCOUNT` VALUES (1,'AQZX-WSFR-GYUH-NJKI-KOLP-123456789','RwnKO95372',2733884341,'1000-01-01 00:00:00','1000-01-01 00:00:00'),(2,'AQZX-WSFR-GYUH-NJKI-KOLP-1234567AA','uqUqc02813',1702752469,'1000-01-01 00:00:00','1000-01-01 00:00:00'),(3,'AQZX-WSFR-GYUH-NJKI-KOLP-1234567AB','AZXFi11723',1547694370,'1000-01-01 00:00:00','1000-01-01 00:00:00');
/*!40000 ALTER TABLE `GAIA_USER_ACCOUNT` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_USER_BAN`
--

DROP TABLE IF EXISTS `GAIA_USER_BAN`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_USER_BAN` (
  `user_id` bigint(20) NOT NULL,
  `summary` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `message` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_time` datetime NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_USER_BAN`
--

LOCK TABLES `GAIA_USER_BAN` WRITE;
/*!40000 ALTER TABLE `GAIA_USER_BAN` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_USER_BAN` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_USER_BAN_HISTORY`
--

DROP TABLE IF EXISTS `GAIA_USER_BAN_HISTORY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_USER_BAN_HISTORY` (
  `user_ban_history_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `action` tinyint(4) NOT NULL COMMENT 'CRUD を 0-3で表現',
  `summary` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `message` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status_change_reason` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_time` datetime NOT NULL,
  PRIMARY KEY (`user_ban_history_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_USER_BAN_HISTORY`
--

LOCK TABLES `GAIA_USER_BAN_HISTORY` WRITE;
/*!40000 ALTER TABLE `GAIA_USER_BAN_HISTORY` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_USER_BAN_HISTORY` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_USER_DATA_ABOUT_FRIEND`
--

DROP TABLE IF EXISTS `GAIA_USER_DATA_ABOUT_FRIEND`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_USER_DATA_ABOUT_FRIEND` (
  `user_id` bigint(20) NOT NULL,
  `public_id` bigint(20) NOT NULL COMMENT '公開用ユーザID\n友達検索で利用する',
  `friend_count` smallint(5) unsigned NOT NULL,
  `friend_max` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uqidx_publicid` (`public_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_USER_DATA_ABOUT_FRIEND`
--

LOCK TABLES `GAIA_USER_DATA_ABOUT_FRIEND` WRITE;
/*!40000 ALTER TABLE `GAIA_USER_DATA_ABOUT_FRIEND` DISABLE KEYS */;
INSERT INTO `GAIA_USER_DATA_ABOUT_FRIEND` VALUES (1,180925669,1,10),(2,108235579,1,10),(3,179773678,0,10);
/*!40000 ALTER TABLE `GAIA_USER_DATA_ABOUT_FRIEND` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_USER_FRIEND`
--

DROP TABLE IF EXISTS `GAIA_USER_FRIEND`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_USER_FRIEND` (
  `user_id` bigint(20) NOT NULL,
  `friend_user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`,`friend_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_USER_FRIEND`
--

LOCK TABLES `GAIA_USER_FRIEND` WRITE;
/*!40000 ALTER TABLE `GAIA_USER_FRIEND` DISABLE KEYS */;
INSERT INTO `GAIA_USER_FRIEND` VALUES (1,2),(2,1);
/*!40000 ALTER TABLE `GAIA_USER_FRIEND` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_USER_FRIEND_HISTORY`
--

DROP TABLE IF EXISTS `GAIA_USER_FRIEND_HISTORY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_USER_FRIEND_HISTORY` (
  `user_friend_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `friend_action_type_id` tinyint(4) NOT NULL,
  `action_user_id` bigint(20) NOT NULL,
  `friend_user_id` bigint(20) NOT NULL,
  `created_time` datetime NOT NULL,
  PRIMARY KEY (`user_friend_history_id`),
  KEY `fk_GAIA_USER_FRIEND_HISTORY_GAIA_MST_FRIEND_ACTION1_idx` (`friend_action_type_id`),
  KEY `fk_GAIA_USER_FRIEND_HISTORY_GAIA_USER_ACCOUNT1_idx` (`action_user_id`),
  KEY `fk_GAIA_USER_FRIEND_HISTORY_GAIA_USER_ACCOUNT2_idx` (`friend_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_USER_FRIEND_HISTORY`
--

LOCK TABLES `GAIA_USER_FRIEND_HISTORY` WRITE;
/*!40000 ALTER TABLE `GAIA_USER_FRIEND_HISTORY` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_USER_FRIEND_HISTORY` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_USER_FRIEND_OFFER`
--

DROP TABLE IF EXISTS `GAIA_USER_FRIEND_OFFER`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_USER_FRIEND_OFFER` (
  `user_friend_offer_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `offer_user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_friend_offer_id`),
  UNIQUE KEY `idx_userid_offeruserid` (`user_id`,`offer_user_id`),
  KEY `fk_friend_offer_user_account2_idx` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_USER_FRIEND_OFFER`
--

LOCK TABLES `GAIA_USER_FRIEND_OFFER` WRITE;
/*!40000 ALTER TABLE `GAIA_USER_FRIEND_OFFER` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_USER_FRIEND_OFFER` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_USER_FRIEND_OFFER_HISTORY`
--

DROP TABLE IF EXISTS `GAIA_USER_FRIEND_OFFER_HISTORY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_USER_FRIEND_OFFER_HISTORY` (
  `user_friend_offer_history_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `friend_offer_action_type_id` tinyint(4) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `offer_user_id` bigint(20) NOT NULL,
  `created_time` datetime NOT NULL,
  PRIMARY KEY (`user_friend_offer_history_id`),
  KEY `fk_GAIA_USER_FRIEND_OFFER_HISTORY_GAIA_MST_FRIEND_OFFER_ACT_idx` (`friend_offer_action_type_id`),
  KEY `fk_GAIA_USER_FRIEND_OFFER_HISTORY_GAIA_USER_ACCOUNT1_idx` (`user_id`),
  KEY `fk_GAIA_USER_FRIEND_OFFER_HISTORY_GAIA_USER_ACCOUNT2_idx` (`offer_user_id`),
  KEY `idx_offeruserid_userid_createdtime` (`offer_user_id`,`user_id`,`created_time`),
  KEY `idx_offeruserid_createdtime` (`offer_user_id`,`created_time`),
  KEY `idx_userid_createdtime` (`user_id`,`created_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_USER_FRIEND_OFFER_HISTORY`
--

LOCK TABLES `GAIA_USER_FRIEND_OFFER_HISTORY` WRITE;
/*!40000 ALTER TABLE `GAIA_USER_FRIEND_OFFER_HISTORY` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_USER_FRIEND_OFFER_HISTORY` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_USER_SESSION`
--

DROP TABLE IF EXISTS `GAIA_USER_SESSION`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_USER_SESSION` (
  `user_id` bigint(20) NOT NULL,
  `session_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `noah_id` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`user_id`),
  KEY `fk_user_session_user_account2_idx` (`user_id`),
  KEY `idx_sessionid` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_USER_SESSION`
--

LOCK TABLES `GAIA_USER_SESSION` WRITE;
/*!40000 ALTER TABLE `GAIA_USER_SESSION` DISABLE KEYS */;
INSERT INTO `GAIA_USER_SESSION` VALUES (1,'55d5fd64b7888aaa2aecbbcc2e043cc8',-1),(2,'119a32fb638797118b636ca50b0fe5a8',-1),(3,'41b128a093ed99bd90c2c14cb6ba691a',-1);
/*!40000 ALTER TABLE `GAIA_USER_SESSION` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_USER_TAKE_OVER_HISTORY`
--

DROP TABLE IF EXISTS `GAIA_USER_TAKE_OVER_HISTORY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_USER_TAKE_OVER_HISTORY` (
  `user_takeover_history_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `new_uuid` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `previous_uuid` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `created_time` datetime NOT NULL,
  PRIMARY KEY (`user_takeover_history_id`,`created_time`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
/*!50100 PARTITION BY RANGE (TO_DAYS(created_time))
(PARTITION pmax VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_USER_TAKE_OVER_HISTORY`
--

LOCK TABLES `GAIA_USER_TAKE_OVER_HISTORY` WRITE;
/*!40000 ALTER TABLE `GAIA_USER_TAKE_OVER_HISTORY` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_USER_TAKE_OVER_HISTORY` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GAIA_USER_TAKE_OVER_PASSWORD`
--

DROP TABLE IF EXISTS `GAIA_USER_TAKE_OVER_PASSWORD`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GAIA_USER_TAKE_OVER_PASSWORD` (
  `user_id` bigint(20) NOT NULL,
  `password` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `valid_time` bigint(20) NOT NULL COMMENT 'unix_time形式でミリ秒まで保持する。\n現状の仕様でこの値を利用してパスワードの暗号化を行っているため、DATETIMEに変更できない。',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GAIA_USER_TAKE_OVER_PASSWORD`
--

LOCK TABLES `GAIA_USER_TAKE_OVER_PASSWORD` WRITE;
/*!40000 ALTER TABLE `GAIA_USER_TAKE_OVER_PASSWORD` DISABLE KEYS */;
/*!40000 ALTER TABLE `GAIA_USER_TAKE_OVER_PASSWORD` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-03-17  9:18:02
