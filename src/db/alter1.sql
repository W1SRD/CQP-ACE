SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

ALTER TABLE `CQPACE`.`LOG` DROP FOREIGN KEY `FK_LOG_CLUB_ALIAS` ;

ALTER TABLE `CQPACE`.`LOG` 
  ADD CONSTRAINT `FK_LOG_CLUB`
  FOREIGN KEY (`CLUB` )
  REFERENCES `CQPACE`.`CLUB` (`NAME` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `FK_LOG_CLUB_idx` (`CLUB` ASC) 
, DROP INDEX `FK_LOG_CLUB_ALIAS_idx` ;

ALTER TABLE `CQPACE`.`QSO` 
DROP INDEX `FK_QSO_LOG` ;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
