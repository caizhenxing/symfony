SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

CREATE SCHEMA IF NOT EXISTS `gaia` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ;
USE `gaia` ;

-- -----------------------------------------------------
-- Table `gaia`.`GAIA_USER_ACCOUNT`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_USER_ACCOUNT` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_USER_ACCOUNT` (
  `user_id` BIGINT NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(50) NOT NULL COMMENT '50byte分でOK?',
  `take_over_id` VARCHAR(50) NOT NULL,
  `take_over_id_crc32` INT UNSIGNED NOT NULL,
  `created_time` DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00',
  `updated_time` DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00',
  PRIMARY KEY (`user_id`),
  INDEX `idx_takeoveridcrc32` (`take_over_id_crc32` ASC),
  INDEX `idx_uuid_userid` (`uuid` ASC, `user_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_USER_FRIEND`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_USER_FRIEND` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_USER_FRIEND` (
  `user_id` BIGINT NOT NULL,
  `friend_user_id` BIGINT NOT NULL,
  PRIMARY KEY (`user_id`, `friend_user_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_USER_DATA_ABOUT_FRIEND`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_USER_DATA_ABOUT_FRIEND` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_USER_DATA_ABOUT_FRIEND` (
  `user_id` BIGINT NOT NULL,
  `public_id` BIGINT NOT NULL COMMENT '公開用ユーザID\n友達検索で利用する',
  `friend_count` SMALLINT UNSIGNED NOT NULL,
  `friend_max` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE INDEX `uqidx_publicid` (`public_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_USER_FRIEND_OFFER`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_USER_FRIEND_OFFER` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_USER_FRIEND_OFFER` (
  `user_friend_offer_id` BIGINT NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NOT NULL,
  `offer_user_id` BIGINT NOT NULL,
  PRIMARY KEY (`user_friend_offer_id`),
  UNIQUE INDEX `idx_userid_offeruserid` (`user_id` ASC, `offer_user_id` ASC),
  INDEX `fk_friend_offer_user_account2_idx` (`user_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_USER_SESSION`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_USER_SESSION` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_USER_SESSION` (
  `user_id` BIGINT NOT NULL,
  `session_id` VARCHAR(50) NOT NULL,
  `noah_id` INT NOT NULL DEFAULT -1,
  PRIMARY KEY (`user_id`),
  INDEX `fk_user_session_user_account2_idx` (`user_id` ASC),
  INDEX `idx_sessionid` (`session_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_USER_TAKE_OVER_PASSWORD`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_USER_TAKE_OVER_PASSWORD` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_USER_TAKE_OVER_PASSWORD` (
  `user_id` BIGINT NOT NULL,
  `password` VARCHAR(100) NOT NULL,
  `salt` VARCHAR(100) NOT NULL,
  `valid_time` BIGINT NOT NULL COMMENT 'unix_time形式でミリ秒まで保持する。\n現状の仕様でこの値を利用してパスワードの暗号化を行っているため、DATETIMEに変更できない。',
  PRIMARY KEY (`user_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MST_ASSET_TYPE`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MST_ASSET_TYPE` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MST_ASSET_TYPE` (
  `asset_type_id` INT NOT NULL AUTO_INCREMENT COMMENT 'アイテムタイプID',
  `asset_type` VARCHAR(50) NOT NULL COMMENT 'アイテムタイプ名',
  PRIMARY KEY (`asset_type_id`))
ENGINE = InnoDB
COMMENT = 'アイテムタイプマスタ';


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_PRESENT_RESERVE`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_PRESENT_RESERVE` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_PRESENT_RESERVE` (
  `present_reserve_id` BIGINT NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NOT NULL,
  `status_code` INT NOT NULL,
  `reason_code` INT NOT NULL,
  `message` VARCHAR(100) NOT NULL,
  `asset_type_id` INT NOT NULL,
  `asset_id` INT NOT NULL,
  `asset_count` INT NOT NULL,
  `created_time` DATETIME NOT NULL,
  PRIMARY KEY (`present_reserve_id`),
  INDEX `fk_assettype` (`asset_type_id` ASC),
  INDEX `fk_present_reserve_user_account1_idx` (`user_id` ASC),
  INDEX `idx_userid_statuscode` (`user_id` ASC, `status_code` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_PRESENT_BOX`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_PRESENT_BOX` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_PRESENT_BOX` (
  `present_box_id` BIGINT NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NOT NULL,
  `status_code` TINYINT NOT NULL DEFAULT 0,
  `used_time` DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00',
  `reason_code` TINYINT NOT NULL DEFAULT 0,
  `message` VARCHAR(100) NOT NULL,
  `asset_type_id` INT NOT NULL,
  `asset_id` INT NOT NULL,
  `asset_count` INT NOT NULL,
  `created_time` DATETIME NOT NULL,
  PRIMARY KEY (`present_box_id`),
  INDEX `fk_present_box_item_type_id_idx` (`asset_type_id` ASC),
  INDEX `fk_present_box_user_account1_idx` (`user_id` ASC),
  INDEX `idx_userid_statuscode` (`user_id` ASC, `status_code` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MST_ATOM_CAMPAIGN_TYPE`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MST_ATOM_CAMPAIGN_TYPE` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MST_ATOM_CAMPAIGN_TYPE` (
  `atom_campaign_type_id` INT NOT NULL AUTO_INCREMENT,
  `campaign_type_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`atom_campaign_type_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MST_ATOM_CAMPAIGN`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MST_ATOM_CAMPAIGN` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MST_ATOM_CAMPAIGN` (
  `atom_campaign_id` INT NOT NULL AUTO_INCREMENT,
  `atom_campaign_type_id` INT NOT NULL,
  `campaign_name` VARCHAR(50) NOT NULL,
  `campaign_code` VARCHAR(40) NOT NULL,
  `acceptance_message` VARCHAR(255) NOT NULL,
  `check_type` INT NOT NULL,
  PRIMARY KEY (`atom_campaign_id`),
  UNIQUE INDEX `uqidx_campaigncode` (`campaign_code` ASC)  COMMENT ' /* comment truncated */ /*分量が少ないため、crc32ハッシュしたカラムは用意しない*/',
  INDEX `fk_campaigntypeid_idx` (`atom_campaign_type_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MST_ATOM_CAMPAIGN_PRESENT`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MST_ATOM_CAMPAIGN_PRESENT` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MST_ATOM_CAMPAIGN_PRESENT` (
  `atom_campaign_present_id` INT NOT NULL AUTO_INCREMENT,
  `atom_campaign_id` INT NOT NULL,
  `asset_type_id` INT NOT NULL,
  `asset_id` INT NOT NULL,
  `asset_count` INT NOT NULL,
  `present_message` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`atom_campaign_present_id`),
  INDEX `fk_mst_campaign_present_mst_campaign_idx` (`atom_campaign_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_ATOM_CAMPAIGN_USED_HISTORY`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_ATOM_CAMPAIGN_USED_HISTORY` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_ATOM_CAMPAIGN_USED_HISTORY` (
  `atom_campaign_used_history_id` BIGINT NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NOT NULL,
  `atom_campaign_id` INT NOT NULL,
  `serial_code` CHAR(40) NOT NULL,
  `serial_code_crc32` INT UNSIGNED NOT NULL COMMENT 'serial_codeの検索インデックス用カラム。\nserial_codeをハッシュ化した値を格納する。\n値の衝突が起こるので、検索時にはハッシュ化していない値も指定する必要がある。',
  `created_time` DATETIME NOT NULL,
  PRIMARY KEY (`atom_campaign_used_history_id`, `created_time`),
  INDEX `idx_userid_campaignid` (`user_id` ASC, `atom_campaign_id` ASC)  COMMENT ' /* comment truncated */ /*ユーザーが該当のキャンペーンを利用済みかどうかの検索に利用する目的のインデックス*/',
  INDEX `idx_atomcampaignid_serialcodecrc32` (`atom_campaign_id` ASC, `serial_code_crc32` ASC)  COMMENT ' /* comment truncated */ /*該当のキャンペーンにおいてそのシリアルコードが利用済みかどうかを検索するためのインデックス*/',
  INDEX `fk_campaign_log_serial_user_account1_idx` (`user_id` ASC))
ENGINE = InnoDB PARTITION BY RANGE(TO_DAYS(created_time)) PARTITIONS 1( PARTITION pmax VALUES LESS THAN (MAXVALUE));


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MST_INVITE_CAMPAIGN_PRESENT`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MST_INVITE_CAMPAIGN_PRESENT` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MST_INVITE_CAMPAIGN_PRESENT` (
  `atom_campaign_invite_present_id` INT NOT NULL AUTO_INCREMENT,
  `atom_campaign_id` INT NOT NULL,
  `asset_type_id` INT NOT NULL,
  `asset_id` INT NOT NULL,
  `asset_count` INT NOT NULL,
  `present_message` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`atom_campaign_invite_present_id`),
  INDEX `fk_atomcampaignid_idx` (`atom_campaign_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MST_GACHA`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MST_GACHA` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MST_GACHA` (
  `gacha_id` INT NOT NULL AUTO_INCREMENT COMMENT 'ガチャID',
  `gacha_type_id` INT NOT NULL COMMENT 'ガチャタイプID',
  `gacha_name` VARCHAR(50) NOT NULL COMMENT '名前',
  `effective_from` DATETIME NOT NULL COMMENT '利用可能期間 from',
  `effective_to` DATETIME NOT NULL COMMENT '利用可能期間 to',
  PRIMARY KEY (`gacha_id`))
ENGINE = InnoDB
COMMENT = 'ガチャマスタ';


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MST_GACHA_GROUP`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MST_GACHA_GROUP` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MST_GACHA_GROUP` (
  `gacha_group_id` INT NOT NULL AUTO_INCREMENT COMMENT 'ガチャグループID',
  `gacha_id` INT NOT NULL COMMENT 'ガチャID',
  `rarity` INT NOT NULL COMMENT 'ランク',
  `rate` DECIMAL(4,3) NOT NULL COMMENT '排出確率',
  PRIMARY KEY (`gacha_group_id`),
  INDEX `fk_gachaid` (`gacha_id` ASC))
ENGINE = InnoDB
COMMENT = 'ガチャランクグループマスタ';


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_GACHA_HISTORY`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_GACHA_HISTORY` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_GACHA_HISTORY` (
  `gacha_history_id` BIGINT NOT NULL AUTO_INCREMENT COMMENT 'ガチャ履歴',
  `user_id` BIGINT NOT NULL COMMENT 'ユーザID',
  `gacha_id` INT NOT NULL COMMENT 'ガチャID',
  `asset_type_id` INT NOT NULL COMMENT 'アイテムタイプID',
  `asset_id` INT NOT NULL COMMENT '取得カードID',
  `asset_count` INT NOT NULL,
  `created_time` DATETIME NOT NULL COMMENT '実行日時',
  PRIMARY KEY (`gacha_history_id`, `created_time`),
  INDEX `fk_gachaid` (`gacha_id` ASC),
  INDEX `user_id_idx` (`user_id` ASC))
ENGINE = InnoDB
COMMENT = 'ガチャ履歴' PARTITION BY RANGE(TO_DAYS(created_time)) PARTITIONS 1( PARTITION pmax VALUES LESS THAN (MAXVALUE));


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MST_GACHA_CARD`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MST_GACHA_CARD` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MST_GACHA_CARD` (
  `gacha_card_id` INT NOT NULL AUTO_INCREMENT COMMENT '一意なキー',
  `gacha_group_id` INT NOT NULL COMMENT 'ガチャグループID',
  `asset_type_id` INT NOT NULL COMMENT 'アセットタイプID（通常はカード関連になるはず）',
  `asset_id` INT NOT NULL COMMENT 'カードID',
  `asset_count` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`gacha_card_id`),
  INDEX `fk_gachagroupid` (`gacha_group_id` ASC))
ENGINE = InnoDB
COMMENT = 'ガチャ排出カードマスタ';


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MST_OS_TYPE`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MST_OS_TYPE` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MST_OS_TYPE` (
  `os_type_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`os_type_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MST_PURCHASE_ITEM_DATA`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MST_PURCHASE_ITEM_DATA` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MST_PURCHASE_ITEM_DATA` (
  `purchase_item_data_id` INT NOT NULL AUTO_INCREMENT COMMENT '課金アイテムID',
  `os_type_id` INT NOT NULL COMMENT 'OS',
  `name` VARCHAR(100) NOT NULL COMMENT '名前',
  `price` DECIMAL(15,3) NOT NULL COMMENT '値段',
  `item_identifier` VARCHAR(255) NOT NULL COMMENT '注文ID',
  `asset_type_id` INT NOT NULL COMMENT 'アイテムタイプID',
  `asset_id` INT NOT NULL COMMENT 'アイテムID',
  `asset_count` INT NOT NULL COMMENT 'アイテム個数',
  `effective_from` DATETIME NOT NULL DEFAULT '1000-01-01 00:00:00',
  `effective_to` DATETIME NOT NULL DEFAULT '9999-12-31 23:59:59',
  PRIMARY KEY (`purchase_item_data_id`),
  INDEX `fk_mst_purchase_item_data_os_type_id_idx` (`os_type_id` ASC),
  INDEX `idx_ostypeid_effectivetimes_itemidentifier` (`os_type_id` ASC, `effective_from` ASC, `effective_to` ASC, `item_identifier` ASC))
ENGINE = InnoDB
COMMENT = '課金アイテムマスタ';


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_PURCHASE_ANDROID_HISTORY`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_PURCHASE_ANDROID_HISTORY` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_PURCHASE_ANDROID_HISTORY` (
  `purchase_history_id` BIGINT NOT NULL AUTO_INCREMENT COMMENT '購入履歴ID',
  `user_id` BIGINT NOT NULL COMMENT 'ユーザID',
  `purchase_item_data_id` INT NOT NULL COMMENT '課金アイテムID',
  `order_id` VARCHAR(255) NOT NULL COMMENT '購入番号',
  `created_time` DATETIME NOT NULL,
  PRIMARY KEY (`purchase_history_id`, `created_time`),
  INDEX `paid_item_id_idx` (`purchase_item_data_id` ASC),
  INDEX `fk_purchase_android_history_user_account1_idx` (`user_id` ASC))
ENGINE = InnoDB
COMMENT = '購入履歴android' PARTITION BY RANGE(TO_DAYS(created_time)) PARTITIONS 1( PARTITION pmax VALUES LESS THAN (MAXVALUE));


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_PURCHASE_IOS_HISTORY`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_PURCHASE_IOS_HISTORY` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_PURCHASE_IOS_HISTORY` (
  `purchase_history_id` BIGINT NOT NULL AUTO_INCREMENT COMMENT '購入履歴ID',
  `user_id` BIGINT NOT NULL COMMENT 'ユーザID',
  `purchase_item_data_id` INT NOT NULL COMMENT '課金アイテムID',
  `transaction_id` VARCHAR(255) NOT NULL COMMENT '購入番号',
  `created_time` DATETIME NOT NULL,
  PRIMARY KEY (`purchase_history_id`, `created_time`),
  INDEX `paid_item_id_idx` (`purchase_item_data_id` ASC),
  INDEX `fk_purchase_ios_history_user_account1_idx` (`user_id` ASC))
ENGINE = InnoDB
COMMENT = '購入履歴iOS' PARTITION BY RANGE(TO_DAYS(created_time)) PARTITIONS 1( PARTITION pmax VALUES LESS THAN (MAXVALUE));


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_NOAH_OFFER_HISTORY`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_NOAH_OFFER_HISTORY` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_NOAH_OFFER_HISTORY` (
  `noah_offer_history_id` BIGINT NOT NULL AUTO_INCREMENT COMMENT 'オファー履歴ID',
  `user_id` BIGINT NOT NULL COMMENT 'ユーザID',
  `action_id` VARCHAR(20) NOT NULL COMMENT 'アクションID - Noahから送られてくる文字列',
  `user_action_id` BIGINT NOT NULL COMMENT 'ユーザアクションID - Noahから送られてくる数値',
  `created_time` DATETIME NOT NULL,
  PRIMARY KEY (`noah_offer_history_id`, `created_time`),
  INDEX `idx_userid_actionid` (`user_id` ASC, `action_id` ASC),
  INDEX `fk_offer_history_user_id_idx` (`user_id` ASC))
ENGINE = InnoDB
COMMENT = 'オファー履歴' PARTITION BY RANGE(TO_DAYS(created_time)) PARTITIONS 1( PARTITION pmax VALUES LESS THAN (MAXVALUE));


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_USER_TAKE_OVER_HISTORY`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_USER_TAKE_OVER_HISTORY` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_USER_TAKE_OVER_HISTORY` (
  `user_takeover_history_id` BIGINT NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NOT NULL,
  `new_uuid` VARCHAR(50) NOT NULL,
  `previous_uuid` VARCHAR(50) NOT NULL,
  `created_time` DATETIME NOT NULL,
  PRIMARY KEY (`user_takeover_history_id`, `created_time`),
  INDEX `idx_user_id` (`user_id` ASC))
ENGINE = InnoDB PARTITION BY RANGE(TO_DAYS(created_time)) PARTITIONS 1( PARTITION pmax VALUES LESS THAN (MAXVALUE));


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_USER_BAN`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_USER_BAN` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_USER_BAN` (
  `user_id` BIGINT NOT NULL,
  `summary` VARCHAR(50) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `created_time` DATETIME NOT NULL,
  PRIMARY KEY (`user_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_USER_BAN_HISTORY`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_USER_BAN_HISTORY` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_USER_BAN_HISTORY` (
  `user_ban_history_id` BIGINT NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NOT NULL,
  `action` tinyint(4) NOT NULL COMMENT 'CRUD を 0-3で表現',
  `summary` VARCHAR(50) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `status_change_reason` VARCHAR(255) NOT NULL DEFAULT '',
  `created_time` DATETIME NOT NULL,
  PRIMARY KEY (`user_ban_history_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MNT_USER_ADMIN`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MNT_USER_ADMIN` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MNT_USER_ADMIN` (
  `admin_user_id` BIGINT NOT NULL AUTO_INCREMENT,
  `login_id` VARCHAR(50) CHARACTER SET 'utf8' NOT NULL,
  `password` VARCHAR(100) NOT NULL,
  `salt` VARCHAR(50) NOT NULL,
  `created_time` DATETIME NOT NULL,
  `created_admin_id` BIGINT NOT NULL,
  `updated_time` DATETIME NOT NULL,
  `updated_admin_id` BIGINT NOT NULL,
  PRIMARY KEY (`admin_user_id`),
  UNIQUE INDEX `login_id_UNIQUE` (`login_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MST_MNT_USER_ADMIN_ROLE`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MST_MNT_USER_ADMIN_ROLE` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MST_MNT_USER_ADMIN_ROLE` (
  `role_id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `created_time` DATETIME NOT NULL,
  `created_admin_id` BIGINT NOT NULL,
  `updated_time` DATETIME NOT NULL,
  `updated_admin_id` BIGINT NOT NULL,
  PRIMARY KEY (`role_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MST_MNT_USER_ADMIN_PRIVILEGE`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MST_MNT_USER_ADMIN_PRIVILEGE` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MST_MNT_USER_ADMIN_PRIVILEGE` (
  `privilege_id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `created_time` DATETIME NOT NULL,
  `created_admin_id` BIGINT NOT NULL,
  `updated_time` DATETIME NOT NULL,
  `updated_admin_id` BIGINT NOT NULL,
  PRIMARY KEY (`privilege_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MNT_USER_ADMIN_ROLE`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MNT_USER_ADMIN_ROLE` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MNT_USER_ADMIN_ROLE` (
  `admin_user_id` BIGINT NOT NULL,
  `role_id` INT NOT NULL,
  `created_time` DATETIME NOT NULL,
  `created_admin_id` BIGINT NOT NULL,
  `updated_time` DATETIME NOT NULL,
  `updated_admin_id` BIGINT NOT NULL,
  PRIMARY KEY (`admin_user_id`, `role_id`),
  INDEX `fk_gaia_mnt_user_admin_role_admin_user_id_idx` (`admin_user_id` ASC),
  INDEX `fk_gaia_mnt_user_admin_role_role_id1_idx` (`role_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MNT_USER_ADMIN_ROLE_PLIVILEGE`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MNT_USER_ADMIN_ROLE_PLIVILEGE` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MNT_USER_ADMIN_ROLE_PLIVILEGE` (
  `role_id` INT NOT NULL,
  `privilege_id` INT NOT NULL,
  `created_time` DATETIME NOT NULL,
  `created_admin_id` BIGINT NOT NULL,
  `updated_time` DATETIME NOT NULL,
  `updated_admin_id` BIGINT NOT NULL,
  PRIMARY KEY (`role_id`, `privilege_id`),
  INDEX `fk_gaia_mnt_user_admin_role_plivilege_role_id_idx` (`role_id` ASC),
  INDEX `fk_gaia_mnt_user_admin_role_plivilege_privilege_id1_idx` (`privilege_id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `gaia`.`GAIA_MST_NOAH_OFFER_PRESENT`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `gaia`.`GAIA_MST_NOAH_OFFER_PRESENT` ;

CREATE TABLE IF NOT EXISTS `gaia`.`GAIA_MST_NOAH_OFFER_PRESENT` (
  `noah_offer_present_id` INT NOT NULL AUTO_INCREMENT,
  `asset_type_id` INT NOT NULL,
  `asset_id` INT NOT NULL,
  `effective_from` DATETIME NOT NULL,
  `effective_to` DATETIME NOT NULL,
  PRIMARY KEY (`noah_offer_present_id`))
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `gaia`.`GAIA_MST_ATOM_CAMPAIGN_TYPE`
-- -----------------------------------------------------
START TRANSACTION;
USE `gaia`;
INSERT INTO `gaia`.`GAIA_MST_ATOM_CAMPAIGN_TYPE` (`atom_campaign_type_id`, `campaign_type_name`) VALUES (1, 'preregister');
INSERT INTO `gaia`.`GAIA_MST_ATOM_CAMPAIGN_TYPE` (`atom_campaign_type_id`, `campaign_type_name`) VALUES (2, 'invite');
INSERT INTO `gaia`.`GAIA_MST_ATOM_CAMPAIGN_TYPE` (`atom_campaign_type_id`, `campaign_type_name`) VALUES (3, 'normal');

COMMIT;


-- -----------------------------------------------------
-- Data for table `gaia`.`GAIA_MST_OS_TYPE`
-- -----------------------------------------------------
START TRANSACTION;
USE `gaia`;
INSERT INTO `gaia`.`GAIA_MST_OS_TYPE` (`os_type_id`, `name`) VALUES (1, 'iOS');
INSERT INTO `gaia`.`GAIA_MST_OS_TYPE` (`os_type_id`, `name`) VALUES (2, 'Android');

COMMIT;

