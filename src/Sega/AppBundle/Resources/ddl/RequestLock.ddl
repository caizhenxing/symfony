CREATE TABLE `gaia`.`DCS_RESPONSE` (
  `key` VARCHAR(255) NOT NULL,
  `response` TEXT NULL,
  `create_date` DATETIME NULL,
  PRIMARY KEY (`key`),
  INDEX `CREATEDATE` (`create_date` ASC));
