SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';

DROP SCHEMA IF EXISTS `beartooth` ;
CREATE SCHEMA IF NOT EXISTS `beartooth` ;
USE `beartooth` ;

-- -----------------------------------------------------
-- Table `beartooth`.`setting`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`setting` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`setting` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `category` VARCHAR(45) NOT NULL ,
  `name` VARCHAR(45) NOT NULL ,
  `type` ENUM('boolean', 'integer', 'float', 'string') NOT NULL ,
  `value` VARCHAR(45) NOT NULL ,
  `description` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `dk_category` (`category` ASC) ,
  INDEX `dk_name` (`name` ASC) ,
  UNIQUE INDEX `uq_category_name` (`category` ASC, `name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `beartooth`.`setting_value`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`setting_value` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`setting_value` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `setting_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  `value` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  UNIQUE INDEX `uq_setting_id_site_id` (`setting_id` ASC, `site_id` ASC) ,
  INDEX `fk_setting_id` (`setting_id` ASC) ,
  CONSTRAINT `fk_setting_value_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `cenozo`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_setting_value_setting_id`
    FOREIGN KEY (`setting_id` )
    REFERENCES `beartooth`.`setting` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Site-specific setting overriding the default.';


-- -----------------------------------------------------
-- Table `beartooth`.`operation`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`operation` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`operation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `type` ENUM('push','pull','widget') NOT NULL ,
  `subject` VARCHAR(45) NOT NULL ,
  `name` VARCHAR(45) NOT NULL ,
  `restricted` TINYINT(1) NOT NULL DEFAULT 1 ,
  `description` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_type_subject_name` (`type` ASC, `subject` ASC, `name` ASC) ,
  INDEX `dk_type` (`type` ASC) ,
  INDEX `dk_subject` (`subject` ASC) ,
  INDEX `dk_name` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `beartooth`.`activity`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`activity` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`activity` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  `role_id` INT UNSIGNED NOT NULL ,
  `operation_id` INT UNSIGNED NOT NULL ,
  `query` VARCHAR(511) NOT NULL ,
  `elapsed` FLOAT NOT NULL DEFAULT 0 COMMENT 'The total time to perform the operation in seconds.' ,
  `error_code` VARCHAR(20) NULL DEFAULT '(incomplete)' COMMENT 'NULL if no error occurred.' ,
  `datetime` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_role_id` (`role_id` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `fk_operation_id` (`operation_id` ASC) ,
  INDEX `dk_datetime` (`datetime` ASC) ,
  CONSTRAINT `fk_activity_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `cenozo`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_activity_role_id`
    FOREIGN KEY (`role_id` )
    REFERENCES `cenozo`.`role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_activity_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `cenozo`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_activity_operation_id`
    FOREIGN KEY (`operation_id` )
    REFERENCES `beartooth`.`operation` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `beartooth`.`role_has_operation`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`role_has_operation` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`role_has_operation` (
  `role_id` INT UNSIGNED NOT NULL ,
  `operation_id` INT UNSIGNED NOT NULL ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  PRIMARY KEY (`role_id`, `operation_id`) ,
  INDEX `fk_operation_id` (`operation_id` ASC) ,
  INDEX `fk_role_id` (`role_id` ASC) ,
  CONSTRAINT `fk_role_has_operation_role_id`
    FOREIGN KEY (`role_id` )
    REFERENCES `cenozo`.`role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_role_has_operation_operation_id`
    FOREIGN KEY (`operation_id` )
    REFERENCES `beartooth`.`operation` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `beartooth`.`qnaire`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`qnaire` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`qnaire` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `name` VARCHAR(255) NOT NULL ,
  `rank` INT NOT NULL ,
  `prev_qnaire_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'The qnaire which must be completed before this one begins.' ,
  `delay` INT NOT NULL DEFAULT 0 COMMENT 'How many weeks after then end of the previous qnaire before starting.' ,
  `type` ENUM('home','site') NOT NULL ,
  `withdraw_sid` INT NULL DEFAULT NULL ,
  `description` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_name` (`name` ASC) ,
  UNIQUE INDEX `uq_rank` (`rank` ASC) ,
  INDEX `fk_prev_qnaire_id` (`prev_qnaire_id` ASC) ,
  CONSTRAINT `fk_qnaire_prev_qnaire_id`
    FOREIGN KEY (`prev_qnaire_id` )
    REFERENCES `beartooth`.`qnaire` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `beartooth`.`phase`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`phase` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`phase` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `qnaire_id` INT UNSIGNED NOT NULL ,
  `sid` INT NOT NULL COMMENT 'limesurvey surveys.sid' ,
  `rank` SMALLINT UNSIGNED NOT NULL ,
  `repeated` TINYINT(1) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_qnaire_id` (`qnaire_id` ASC) ,
  UNIQUE INDEX `uq_qnaire_id_rank` (`qnaire_id` ASC, `rank` ASC) ,
  CONSTRAINT `fk_phase_qnaire_id`
    FOREIGN KEY (`qnaire_id` )
    REFERENCES `beartooth`.`qnaire` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'aka: qnaire_has_survey';


-- -----------------------------------------------------
-- Table `beartooth`.`interview`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`interview` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`interview` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `qnaire_id` INT UNSIGNED NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `require_supervisor` TINYINT(1) NOT NULL DEFAULT 0 ,
  `completed` TINYINT(1) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_qnaire_id` (`qnaire_id` ASC) ,
  INDEX `dk_completed` (`completed` ASC) ,
  UNIQUE INDEX `uq_participant_id_qnaire_id` (`participant_id` ASC, `qnaire_id` ASC) ,
  CONSTRAINT `fk_interview_participant_id`
    FOREIGN KEY (`participant_id` )
    REFERENCES `cenozo`.`participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_interview_qnaire_id`
    FOREIGN KEY (`qnaire_id` )
    REFERENCES `beartooth`.`qnaire` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'aka: qnaire_has_participant';


-- -----------------------------------------------------
-- Table `beartooth`.`queue`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`queue` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`queue` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `name` VARCHAR(45) NOT NULL ,
  `title` VARCHAR(255) NOT NULL ,
  `rank` INT UNSIGNED NULL DEFAULT NULL ,
  `qnaire_specific` TINYINT(1) NOT NULL ,
  `parent_queue_id` INT UNSIGNED NULL DEFAULT NULL ,
  `description` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_rank` (`rank` ASC) ,
  INDEX `fk_parent_queue_id` (`parent_queue_id` ASC) ,
  UNIQUE INDEX `uq_name` (`name` ASC) ,
  CONSTRAINT `fk_queue_parent_queue_id`
    FOREIGN KEY (`parent_queue_id` )
    REFERENCES `beartooth`.`queue` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `beartooth`.`assignment`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`assignment` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`assignment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL COMMENT 'The site from which the user was assigned.' ,
  `interview_id` INT UNSIGNED NOT NULL ,
  `queue_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'The queue that the assignment came from.' ,
  `start_datetime` DATETIME NOT NULL ,
  `end_datetime` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_interview_id` (`interview_id` ASC) ,
  INDEX `dk_start_datetime` (`start_datetime` ASC) ,
  INDEX `dk_end_datetime` (`end_datetime` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_queue_id` (`queue_id` ASC) ,
  CONSTRAINT `fk_assignment_interview_id`
    FOREIGN KEY (`interview_id` )
    REFERENCES `beartooth`.`interview` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `cenozo`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `cenozo`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_queue_id`
    FOREIGN KEY (`queue_id` )
    REFERENCES `beartooth`.`queue` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `beartooth`.`phone_call`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`phone_call` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`phone_call` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `assignment_id` INT UNSIGNED NOT NULL ,
  `phone_id` INT UNSIGNED NOT NULL ,
  `start_datetime` DATETIME NOT NULL COMMENT 'The time the call started.' ,
  `end_datetime` DATETIME NULL DEFAULT NULL COMMENT 'The time the call endede.' ,
  `status` ENUM('contacted','busy','no answer','machine message','machine no message','fax','disconnected','wrong number','not reached','hang up','soft refusal') NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_assignment_id` (`assignment_id` ASC) ,
  INDEX `dk_status` (`status` ASC) ,
  INDEX `fk_phone_id` (`phone_id` ASC) ,
  INDEX `dk_start_datetime` (`start_datetime` ASC) ,
  INDEX `dk_end_datetime` (`end_datetime` ASC) ,
  CONSTRAINT `fk_phone_call_assignment_id`
    FOREIGN KEY (`assignment_id` )
    REFERENCES `beartooth`.`assignment` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_phone_call_phone_id`
    FOREIGN KEY (`phone_id` )
    REFERENCES `cenozo`.`phone` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `beartooth`.`assignment_note`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`assignment_note` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`assignment_note` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `assignment_id` INT UNSIGNED NOT NULL ,
  `sticky` TINYINT(1) NOT NULL DEFAULT 0 ,
  `datetime` DATETIME NOT NULL ,
  `note` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_assignment_id` (`assignment_id` ASC) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `dk_sticky_datetime` (`sticky` ASC, `datetime` ASC) ,
  CONSTRAINT `fk_assignment_note_assignment_id`
    FOREIGN KEY (`assignment_id` )
    REFERENCES `beartooth`.`assignment` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_note_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `cenozo`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `beartooth`.`appointment`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`appointment` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`appointment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `user_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL for site appointments' ,
  `address_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL for site appointments' ,
  `datetime` DATETIME NOT NULL ,
  `completed` TINYINT(1) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`) ,
  INDEX `dk_reached` (`completed` ASC) ,
  INDEX `fk_address_id` (`address_id` ASC) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `dk_datetime` (`datetime` ASC) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  CONSTRAINT `fk_appointment_address_id`
    FOREIGN KEY (`address_id` )
    REFERENCES `cenozo`.`address` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_appointment_participant_id`
    FOREIGN KEY (`participant_id` )
    REFERENCES `cenozo`.`participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_appointment_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `cenozo`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `beartooth`.`queue_restriction`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`queue_restriction` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`queue_restriction` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `site_id` INT UNSIGNED NULL DEFAULT NULL ,
  `city` VARCHAR(100) NULL DEFAULT NULL ,
  `region_id` INT UNSIGNED NULL DEFAULT NULL ,
  `postcode` VARCHAR(10) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_region_id` (`region_id` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `dk_city` (`city` ASC) ,
  INDEX `dk_postcode` (`postcode` ASC) ,
  CONSTRAINT `fk_queue_restriction_region_id`
    FOREIGN KEY (`region_id` )
    REFERENCES `cenozo`.`region` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_queue_restriction_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `cenozo`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `beartooth`.`callback`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`callback` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`callback` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `phone_id` INT UNSIGNED NULL DEFAULT NULL ,
  `assignment_id` INT UNSIGNED NULL DEFAULT NULL ,
  `datetime` DATETIME NOT NULL ,
  `reached` TINYINT(1) NULL DEFAULT NULL COMMENT 'If the callback was met, whether the participant was reached.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  INDEX `fk_assignment_id` (`assignment_id` ASC) ,
  INDEX `dk_reached` (`reached` ASC) ,
  INDEX `fk_phone_id` (`phone_id` ASC) ,
  INDEX `dk_datetime` (`datetime` ASC) ,
  CONSTRAINT `fk_callback_participant_id`
    FOREIGN KEY (`participant_id` )
    REFERENCES `cenozo`.`participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_callback_assignment_id`
    FOREIGN KEY (`assignment_id` )
    REFERENCES `beartooth`.`assignment` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_callback_phone_id`
    FOREIGN KEY (`phone_id` )
    REFERENCES `cenozo`.`phone` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `beartooth`.`onyx_instance`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`onyx_instance` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`onyx_instance` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `interviewer_user_id` INT UNSIGNED NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_interviewer_user_id` (`interviewer_user_id` ASC) ,
  UNIQUE INDEX `uq_user_id` (`user_id` ASC) ,
  CONSTRAINT `fk_onyx_instance_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `cenozo`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_onyx_instance_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `cenozo`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_onyx_instance_interviewer_user_id`
    FOREIGN KEY (`interviewer_user_id` )
    REFERENCES `cenozo`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `beartooth`.`site_voip`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`site_voip` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`site_voip` (
  `site_id` INT UNSIGNED NOT NULL ,
  `host` VARCHAR(45) NULL DEFAULT NULL ,
  `xor_key` VARCHAR(45) NULL DEFAULT NULL ,
  PRIMARY KEY (`site_id`) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  CONSTRAINT `fk_site_voip_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `cenozo`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `beartooth`.`next_of_kin`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `beartooth`.`next_of_kin` ;

CREATE  TABLE IF NOT EXISTS `beartooth`.`next_of_kin` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` VARCHAR(45) NULL ,
  `create_timestamp` VARCHAR(45) NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `first_name` VARCHAR(45) NULL ,
  `last_name` VARCHAR(45) NULL ,
  `gender` VARCHAR(10) NULL ,
  `phone` VARCHAR(100) NULL ,
  `street` VARCHAR(255) NULL ,
  `city` VARCHAR(100) NULL ,
  `province` VARCHAR(45) NULL ,
  `postal_code` VARCHAR(45) NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  UNIQUE INDEX `uq_participant_id` (`participant_id` ASC) ,
  CONSTRAINT `fk_next_of_kin_participant_id`
    FOREIGN KEY (`participant_id` )
    REFERENCES `cenozo`.`participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Placeholder table for view `beartooth`.`assignment_last_phone_call`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `beartooth`.`assignment_last_phone_call` (`assignment_id` INT, `phone_call_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `beartooth`.`interview_phone_call_status_count`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `beartooth`.`interview_phone_call_status_count` (`interview_id` INT, `status` INT, `total` INT);

-- -----------------------------------------------------
-- Placeholder table for view `beartooth`.`participant_last_appointment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `beartooth`.`participant_last_appointment` (`participant_id` INT, `appointment_id` INT, `completed` INT);

-- -----------------------------------------------------
-- Placeholder table for view `beartooth`.`interview_last_assignment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `beartooth`.`interview_last_assignment` (`interview_id` INT, `assignment_id` INT);

-- -----------------------------------------------------
-- View `beartooth`.`assignment_last_phone_call`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `beartooth`.`assignment_last_phone_call` ;
DROP TABLE IF EXISTS `beartooth`.`assignment_last_phone_call`;
USE `beartooth`;
CREATE OR REPLACE VIEW `beartooth`.`assignment_last_phone_call` AS
SELECT assignment_1.id as assignment_id, phone_call_1.id as phone_call_id
FROM assignment AS assignment_1
LEFT JOIN phone_call AS phone_call_1
ON assignment_1.id = phone_call_1.assignment_id
AND phone_call_1.start_datetime = (
  SELECT MAX( phone_call_2.start_datetime )
  FROM phone_call AS phone_call_2, assignment AS assignment_2
  WHERE assignment_2.id = phone_call_2.assignment_id
  AND assignment_1.id = assignment_2.id
  AND phone_call_2.end_datetime IS NOT NULL
  GROUP BY assignment_2.id );

-- -----------------------------------------------------
-- View `beartooth`.`interview_phone_call_status_count`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `beartooth`.`interview_phone_call_status_count` ;
DROP TABLE IF EXISTS `beartooth`.`interview_phone_call_status_count`;
USE `beartooth`;
CREATE OR REPLACE VIEW `beartooth`.`interview_phone_call_status_count` AS
SELECT interview.id interview_id, phone_call.status status, COUNT( phone_call.id ) total
FROM interview
JOIN assignment ON interview.id = assignment.interview_id
JOIN phone_call ON assignment.id = phone_call.assignment_id
GROUP BY interview.id, phone_call.status;

-- -----------------------------------------------------
-- View `beartooth`.`participant_last_appointment`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `beartooth`.`participant_last_appointment` ;
DROP TABLE IF EXISTS `beartooth`.`participant_last_appointment`;
USE `beartooth`;
CREATE OR REPLACE VIEW `beartooth`.`participant_last_appointment` AS
SELECT participant.id AS participant_id, t1.id AS appointment_id, t1.completed
FROM cenozo.participant
LEFT JOIN appointment t1
ON participant.id = t1.participant_id
AND t1.datetime = (
  SELECT MAX( t2.datetime ) FROM appointment t2
  WHERE t1.participant_id = t2.participant_id )
GROUP BY participant.id;

-- -----------------------------------------------------
-- View `beartooth`.`interview_last_assignment`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `beartooth`.`interview_last_assignment` ;
DROP TABLE IF EXISTS `beartooth`.`interview_last_assignment`;
USE `beartooth`;
CREATE OR REPLACE VIEW `beartooth`.`interview_last_assignment` AS
SELECT interview_1.id AS interview_id,
       assignment_1.id AS assignment_id
FROM assignment assignment_1
JOIN interview interview_1
WHERE interview_1.id = assignment_1.interview_id
AND assignment_1.start_datetime = (
  SELECT MAX( assignment_2.start_datetime )
  FROM assignment assignment_2
  JOIN interview interview_2
  WHERE interview_2.id = assignment_2.interview_id
  AND interview_1.id = interview_2.id
  GROUP BY interview_2.id );
USE `cenozo`;

DELIMITER $$

DELIMITER ;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
