SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

DROP SCHEMA IF EXISTS `mydb` ;
CREATE SCHEMA IF NOT EXISTS `mydb` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci ;
SHOW WARNINGS;
DROP SCHEMA IF EXISTS `CQPACE` ;
CREATE SCHEMA IF NOT EXISTS `CQPACE` DEFAULT CHARACTER SET utf8 ;
SHOW WARNINGS;
USE `mydb` ;
USE `CQPACE` ;

-- -----------------------------------------------------
-- Table `OPERATOR_CATEGORY`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `OPERATOR_CATEGORY` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `OPERATOR_CATEGORY` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `NAME` VARCHAR(16) NOT NULL ,
  PRIMARY KEY (`ID`) )
ENGINE = InnoDB
COMMENT = 'Possible values are:\n\nSINGLE-OP\nMULTI-SINGLE\nMULTI-MULTI\nCHE';

SHOW WARNINGS;
CREATE UNIQUE INDEX `NAME_UNIQUE` ON `OPERATOR_CATEGORY` (`NAME` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `POWER_CATEGORY`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `POWER_CATEGORY` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `POWER_CATEGORY` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `NAME` VARCHAR(16) NOT NULL ,
  PRIMARY KEY (`ID`) )
ENGINE = InnoDB
COMMENT = 'Possible Values are:\n\nQRP\nLOW\nHIGH\n';

SHOW WARNINGS;
CREATE UNIQUE INDEX `NAME_UNIQUE` ON `POWER_CATEGORY` (`NAME` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `STATION_CATEGORY`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `STATION_CATEGORY` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `STATION_CATEGORY` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `NAME` VARCHAR(16) NULL DEFAULT NULL ,
  PRIMARY KEY (`ID`) )
ENGINE = InnoDB
COMMENT = 'Possible values are:\n\nFIXED\nSCHOOL\nMOBILE\nCCE';

SHOW WARNINGS;
CREATE UNIQUE INDEX `NAME_UNIQUE` ON `STATION_CATEGORY` (`NAME` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `TRANSMITTER_CATEGORY`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `TRANSMITTER_CATEGORY` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `TRANSMITTER_CATEGORY` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `NAME` VARCHAR(16) NULL DEFAULT NULL ,
  PRIMARY KEY (`ID`) )
ENGINE = InnoDB
COMMENT = 'This table may be redudant but possible values would be:\n\nON' /* comment truncated */;

SHOW WARNINGS;
CREATE UNIQUE INDEX `NAME_UNIQUE` ON `TRANSMITTER_CATEGORY` (`NAME` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `MULTIPLIER`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `MULTIPLIER` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `MULTIPLIER` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `NAME` VARCHAR(4) NOT NULL ,
  `TYPE` VARCHAR(16) NOT NULL ,
  `VALUE` INT(11) NOT NULL ,
  `DESCRIPTION` VARCHAR(32) NULL DEFAULT NULL ,
  PRIMARY KEY (`ID`) )
ENGINE = InnoDB
COMMENT = 'Contains all the unique multipliers for QSO scoring.\n\n58 Cal' /* comment truncated */;

SHOW WARNINGS;
CREATE UNIQUE INDEX `NAME_UNIQUE` ON `MULTIPLIER` (`NAME` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `CLUB_LOCATION`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `CLUB_LOCATION` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `CLUB_LOCATION` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `LOCATION` VARCHAR(4) NOT NULL ,
  PRIMARY KEY (`ID`) )
ENGINE = InnoDB;

SHOW WARNINGS;
CREATE UNIQUE INDEX `LOCATION_UNIQUE` ON `CLUB_LOCATION` (`LOCATION` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `CLUB`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `CLUB` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `CLUB` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `NAME` VARCHAR(255) NOT NULL ,
  `LOCATION` VARCHAR(4) NOT NULL ,
  PRIMARY KEY (`ID`) ,
  CONSTRAINT `FK_CLUB_LOCATION`
    FOREIGN KEY (`LOCATION` )
    REFERENCES `CLUB_LOCATION` (`LOCATION` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Master table for Club names.\n\nLocation indicates whether the' /* comment truncated */;

SHOW WARNINGS;
CREATE UNIQUE INDEX `NAME_UNIQUE` ON `CLUB` (`NAME` ASC) ;

SHOW WARNINGS;
CREATE INDEX `FK_CLUB_LOCATION_idx` ON `CLUB` (`LOCATION` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `CLUB_ALIAS`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `CLUB_ALIAS` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `CLUB_ALIAS` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `CLUB_NAME` VARCHAR(255) NOT NULL ,
  `ALIAS` VARCHAR(255) NOT NULL ,
  `CLUB_ID` INT NOT NULL ,
  PRIMARY KEY (`ID`) ,
  CONSTRAINT `FK_CLUB_ALIAS_CLUB`
    FOREIGN KEY (`CLUB_NAME` )
    REFERENCES `CLUB` (`NAME` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Contains aliases we have seen for specific club names.\n\nUsed' /* comment truncated */;

SHOW WARNINGS;
CREATE UNIQUE INDEX `ALIAS_UNIQUE` ON `CLUB_ALIAS` (`ALIAS` ASC) ;

SHOW WARNINGS;
CREATE INDEX `FK_CLUB_ALIAS_CLUB_idx` ON `CLUB_ALIAS` (`CLUB_NAME` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `LOG`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `LOG` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `LOG` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `CALLSIGN` VARCHAR(16) NOT NULL ,
  `CONTEST_NAME` VARCHAR(32) NOT NULL DEFAULT 'CA-QSO-PARTY' ,
  `STATION_OWNER_CALLSIGN` VARCHAR(16) NULL DEFAULT NULL ,
  `CONTEST_YEAR` VARCHAR(4) NOT NULL DEFAULT '2012' ,
  `EMAIL_ADDRESS` VARCHAR(96) NULL DEFAULT NULL ,
  `STATION_LOCATION` VARCHAR(4) NOT NULL ,
  `OPERATOR_CATEGORY` VARCHAR(16) NOT NULL ,
  `POWER_CATEGORY` VARCHAR(16) NOT NULL ,
  `STATION_CATEGORY` VARCHAR(16) NOT NULL ,
  `TRANSMITTER_CATEGORY` VARCHAR(16) NOT NULL ,
  `CLUB` VARCHAR(255) NULL DEFAULT NULL ,
  `SUBMISSION_DATE` DATE NOT NULL ,
  `OVERLAY_YL` TINYINT(1) NULL DEFAULT NULL ,
  `OVERLAY_YOUTH` TINYINT(1) NULL DEFAULT NULL ,
  `OVERLAY_NEW_CONTESTER` TINYINT(1) NULL DEFAULT NULL ,
  `CLAIMED_SCORE` VARCHAR(32) NULL DEFAULT NULL ,
  `LOG_FILENAME` VARCHAR(64) NULL DEFAULT NULL ,
  `SOAPBOX` VARCHAR(2048) NULL DEFAULT NULL ,
  `CABRILLO_HEADER` TEXT NULL DEFAULT NULL ,
  `NUMBER_QSO_RECS` INT NULL DEFAULT 0 ,
  `QSO_RECS_PRESENT` TINYINT(1) NULL DEFAULT NULL ,
  `LAST_UPDATED` TIMESTAMP NULL DEFAULT NULL ,
  PRIMARY KEY (`ID`) ,
  CONSTRAINT `FK_LOG_OPERATOR_CATEGORY`
    FOREIGN KEY (`OPERATOR_CATEGORY` )
    REFERENCES `OPERATOR_CATEGORY` (`NAME` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_LOG_POWER_CATEGORY`
    FOREIGN KEY (`POWER_CATEGORY` )
    REFERENCES `POWER_CATEGORY` (`NAME` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_LOG_STATION_CATEGORY`
    FOREIGN KEY (`STATION_CATEGORY` )
    REFERENCES `STATION_CATEGORY` (`NAME` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_LOG_TRANSMITTER_CATEGORY`
    FOREIGN KEY (`TRANSMITTER_CATEGORY` )
    REFERENCES `TRANSMITTER_CATEGORY` (`NAME` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_LOG_MULTIPLIER`
    FOREIGN KEY (`STATION_LOCATION` )
    REFERENCES `MULTIPLIER` (`NAME` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_LOG_CLUB_ALIAS`
    FOREIGN KEY (`CLUB` )
    REFERENCES `CLUB_ALIAS` (`ALIAS` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Master table for an entry.\n\nHolds all the information ncesar' /* comment truncated */;

SHOW WARNINGS;
CREATE INDEX `FK_LOG_OPERATOR_CATEGORY_idx` ON `LOG` (`OPERATOR_CATEGORY` ASC) ;

SHOW WARNINGS;
CREATE INDEX `FK_LOG_POWER_CATEGORY_idx` ON `LOG` (`POWER_CATEGORY` ASC) ;

SHOW WARNINGS;
CREATE INDEX `FK_LOG_STATION_CATEGORY_idx` ON `LOG` (`STATION_CATEGORY` ASC) ;

SHOW WARNINGS;
CREATE INDEX `FK_LOG_TRANSMITTER_CATEGORY_idx` ON `LOG` (`TRANSMITTER_CATEGORY` ASC) ;

SHOW WARNINGS;
CREATE INDEX `FK_LOG_MULTIPLIER_idx` ON `LOG` (`STATION_LOCATION` ASC) ;

SHOW WARNINGS;
CREATE UNIQUE INDEX `CALLSIGN_UNIQUE` ON `LOG` (`CALLSIGN` ASC) ;

SHOW WARNINGS;
CREATE INDEX `FK_LOG_CLUB_ALIAS_idx` ON `LOG` (`CLUB` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `OPERATOR`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `OPERATOR` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `OPERATOR` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `LOG_ID` INT NOT NULL ,
  `CALLSIGN` VARCHAR(16) NOT NULL ,
  `CLUB_ID` INT NULL DEFAULT NULL ,
  `CLUB_ALLOCATION` INT NULL DEFAULT NULL ,
  PRIMARY KEY (`ID`) ,
  CONSTRAINT `FK_OPERATOR_LOG`
    FOREIGN KEY (`LOG_ID` )
    REFERENCES `LOG` (`ID` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_OPERATOR_CLUB`
    FOREIGN KEY (`CLUB_ID` )
    REFERENCES `CLUB` (`ID` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Allows for partitioning of a score across multiple clubs.\n\nF' /* comment truncated */;

SHOW WARNINGS;
CREATE INDEX `FK_OPERATOR_LOG_idx` ON `OPERATOR` (`LOG_ID` ASC) ;

SHOW WARNINGS;
CREATE INDEX `FK_OPERATOR_CLUB_idx` ON `OPERATOR` (`CLUB_ID` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `QSO_STATUS`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `QSO_STATUS` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `QSO_STATUS` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `NAME` VARCHAR(8) NOT NULL ,
  PRIMARY KEY (`ID`) )
ENGINE = InnoDB
COMMENT = 'Holds the scored category of each QSO record.\n\nPossible valu' /* comment truncated */;

SHOW WARNINGS;
CREATE UNIQUE INDEX `NAME_UNIQUE` ON `QSO_STATUS` (`NAME` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `MODE`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `MODE` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `MODE` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `MODE` VARCHAR(4) NULL ,
  PRIMARY KEY (`ID`) )
ENGINE = InnoDB;

SHOW WARNINGS;
CREATE UNIQUE INDEX `MODE_UNIQUE` ON `MODE` (`MODE` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `QSO`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `QSO` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `QSO` (
  `ID` INT NOT NULL DEFAULT NULL AUTO_INCREMENT ,
  `QSO_DATE` DATETIME NOT NULL ,
  `CALLSIGN_SENT` VARCHAR(32) NOT NULL ,
  `CALLSIGN_RECEIVED` VARCHAR(32) NOT NULL ,
  `FREQUENCY` VARCHAR(8) NOT NULL ,
  `BAND` VARCHAR(4) NULL DEFAULT NULL ,
  `MODE` VARCHAR(4) NOT NULL ,
  `SERIAL_SENT` VARCHAR(6) NOT NULL ,
  `SERIAL_RECEIVED` VARCHAR(6) NOT NULL ,
  `QTH_SENT` VARCHAR(4) NOT NULL ,
  `QTH_RECEIVED` VARCHAR(4) NOT NULL ,
  `LOG_ID` INT NOT NULL ,
  `QSO_STATUS` VARCHAR(8) NULL DEFAULT NULL ,
  `BUST_CALL` VARCHAR(32) NULL ,
  `BUST_NR` INT NULL ,
  `BUST_MULT` VARCHAR(4) NULL ,
  `CREATE_DATE` TIMESTAMP NULL DEFAULT NULL ,
  `LAST_UPDATED` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ,
  PRIMARY KEY (`ID`) ,
  CONSTRAINT `FK_QSO_LOG`
    FOREIGN KEY (`LOG_ID` )
    REFERENCES `LOG` (`ID` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_QSO_QSO_STATUS`
    FOREIGN KEY (`QSO_STATUS` )
    REFERENCES `QSO_STATUS` (`NAME` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_QSO_QTH_RCVD_MULTIPLIER`
    FOREIGN KEY (`QTH_RECEIVED` )
    REFERENCES `MULTIPLIER` (`NAME` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_QSO_QTH_SENT_MULTIPLIER`
    FOREIGN KEY (`QTH_SENT` )
    REFERENCES `MULTIPLIER` (`NAME` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_QSO_MODE`
    FOREIGN KEY (`MODE` )
    REFERENCES `MODE` (`MODE` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Holds normalized QSO records.  \n\nUltimately will be used to ' /* comment truncated */;

SHOW WARNINGS;
CREATE INDEX `FK_QSO_LOG_idx` ON `QSO` (`ID` ASC) ;

SHOW WARNINGS;
CREATE INDEX `FK_QSO_QSO_STATUS_idx` ON `QSO` (`QSO_STATUS` ASC) ;

SHOW WARNINGS;
CREATE INDEX `FK_QSO_QTH_RCVD_MULTIPLIER_idx` ON `QSO` (`QTH_RECEIVED` ASC) ;

SHOW WARNINGS;
CREATE INDEX `FK_QSO_QTH_SENT_MULTIPLIER_idx` ON `QSO` (`QTH_SENT` ASC) ;

SHOW WARNINGS;
CREATE INDEX `CALLSIGN_SENT` ON `QSO` (`CALLSIGN_SENT` ASC) ;

SHOW WARNINGS;
CREATE INDEX `CALLSIGN_RECEIVED` ON `QSO` (`CALLSIGN_RECEIVED` ASC) ;

SHOW WARNINGS;
CREATE INDEX `FK_QSO_MODE_idx` ON `QSO` (`MODE` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `SCORE_TYPE`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `SCORE_TYPE` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `SCORE_TYPE` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `NAME` VARCHAR(45) NULL DEFAULT NULL ,
  `VALUE` VARCHAR(45) NULL DEFAULT NULL ,
  PRIMARY KEY (`ID`) )
ENGINE = InnoDB
COMMENT = 'Not sure what this is for...\n\nAsk W1SRD\n';

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `SCORE`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `SCORE` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `SCORE` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `LOG_ID` INT NOT NULL ,
  `TYPE_ID` INT NOT NULL ,
  `VALUE` VARCHAR(45) NULL DEFAULT NULL ,
  PRIMARY KEY (`ID`) ,
  CONSTRAINT `FK_SCORE_LOG`
    FOREIGN KEY (`ID` )
    REFERENCES `LOG` (`ID` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_SCORE_TYPE`
    FOREIGN KEY (`TYPE_ID` )
    REFERENCES `SCORE_TYPE` (`ID` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Holds the final calculared score for the corresponding conte' /* comment truncated */;

SHOW WARNINGS;
CREATE INDEX `FK_SCORE_LOG_idx` ON `SCORE` (`ID` ASC) ;

SHOW WARNINGS;
CREATE INDEX `FK_SCORE_TYPE_idx` ON `SCORE` (`TYPE_ID` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `AWARD_TYPE`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `AWARD_TYPE` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `AWARD_TYPE` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `NAME` VARCHAR(45) NULL DEFAULT NULL ,
  `VALUE` VARCHAR(45) NULL DEFAULT NULL ,
  PRIMARY KEY (`ID`) )
ENGINE = InnoDB
COMMENT = 'Determines the award type.  Possible options are:\n\nPLAQUE\nWI';

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `AWARD`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `AWARD` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `AWARD` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `LOG_ID` INT NOT NULL ,
  `TYPE_ID` INT NOT NULL ,
  `NAME` VARCHAR(64) NULL DEFAULT NULL ,
  PRIMARY KEY (`ID`) ,
  CONSTRAINT `FK_AWARD_LOG`
    FOREIGN KEY (`LOG_ID` )
    REFERENCES `LOG` (`ID` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_AWARD_TYPE`
    FOREIGN KEY (`TYPE_ID` )
    REFERENCES `AWARD_TYPE` (`ID` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Ties an award type to a contest entrant.';

SHOW WARNINGS;
CREATE INDEX `FK_AWARD_LOG_idx` ON `AWARD` (`LOG_ID` ASC) ;

SHOW WARNINGS;
CREATE INDEX `FK_AWARD_TYPE_idx` ON `AWARD` (`TYPE_ID` ASC) ;

SHOW WARNINGS;

-- -----------------------------------------------------
-- Table `MULTIPLIER_ALIAS`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `MULTIPLIER_ALIAS` ;

SHOW WARNINGS;
CREATE  TABLE IF NOT EXISTS `MULTIPLIER_ALIAS` (
  `ID` INT NOT NULL AUTO_INCREMENT ,
  `MULTIPLIER_NAME` VARCHAR(32) NOT NULL ,
  `ALIAS` VARCHAR(32) NOT NULL ,
  `MULTIPLIER_ID` INT NULL ,
  PRIMARY KEY (`ID`) ,
  CONSTRAINT `FK_MULTIPLIER_ID`
    FOREIGN KEY (`MULTIPLIER_ID` )
    REFERENCES `MULTIPLIER` (`ID` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Used for nformalization of multiplier names.  Containes an a' /* comment truncated */;

SHOW WARNINGS;
CREATE INDEX `FK_MULTIPLIER_ID_idx` ON `MULTIPLIER_ALIAS` (`MULTIPLIER_ID` ASC) ;

SHOW WARNINGS;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `OPERATOR_CATEGORY`
-- -----------------------------------------------------
START TRANSACTION;
USE `CQPACE`;
INSERT INTO `OPERATOR_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'SINGLE-OP');
INSERT INTO `OPERATOR_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'MULTI-SINGLE');
INSERT INTO `OPERATOR_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'MULTI-MULTI');
INSERT INTO `OPERATOR_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'CHECK');

COMMIT;

-- -----------------------------------------------------
-- Data for table `POWER_CATEGORY`
-- -----------------------------------------------------
START TRANSACTION;
USE `CQPACE`;
INSERT INTO `POWER_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'LOW');
INSERT INTO `POWER_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'HIGH');
INSERT INTO `POWER_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'QRP');
INSERT INTO `POWER_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'CHECK');

COMMIT;

-- -----------------------------------------------------
-- Data for table `STATION_CATEGORY`
-- -----------------------------------------------------
START TRANSACTION;
USE `CQPACE`;
INSERT INTO `STATION_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'FIXED');
INSERT INTO `STATION_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'SCHOOL');
INSERT INTO `STATION_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'MOBILE');
INSERT INTO `STATION_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'CCE');

COMMIT;

-- -----------------------------------------------------
-- Data for table `TRANSMITTER_CATEGORY`
-- -----------------------------------------------------
START TRANSACTION;
USE `CQPACE`;
INSERT INTO `TRANSMITTER_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'ONE');
INSERT INTO `TRANSMITTER_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'TWO');
INSERT INTO `TRANSMITTER_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'MULTI');
INSERT INTO `TRANSMITTER_CATEGORY` (`ID`, `NAME`) VALUES (NULL, 'UNLIMITED');

COMMIT;

-- -----------------------------------------------------
-- Data for table `CLUB_LOCATION`
-- -----------------------------------------------------
START TRANSACTION;
USE `CQPACE`;
INSERT INTO `CLUB_LOCATION` (`ID`, `LOCATION`) VALUES (NULL, 'CA');
INSERT INTO `CLUB_LOCATION` (`ID`, `LOCATION`) VALUES (NULL, 'OCA');
INSERT INTO `CLUB_LOCATION` (`ID`, `LOCATION`) VALUES (NULL, 'NONE');

COMMIT;

-- -----------------------------------------------------
-- Data for table `MODE`
-- -----------------------------------------------------
START TRANSACTION;
USE `CQPACE`;
INSERT INTO `MODE` (`ID`, `MODE`) VALUES (NULL, 'PH');
INSERT INTO `MODE` (`ID`, `MODE`) VALUES (NULL, 'CW');

COMMIT;