CREATE TABLE appointment (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  interview_id INT(10) UNSIGNED NOT NULL,
  user_id INT(10) UNSIGNED NULL DEFAULT NULL COMMENT 'NULL for site appointments',
  address_id INT(10) UNSIGNED NULL DEFAULT NULL COMMENT 'NULL for site appointments',
  appointment_type_id INT(10) UNSIGNED NULL DEFAULT NULL,
  appointment_type_reason_id INT(10) UNSIGNED NULL DEFAULT NULL,
  reason_extra VARCHAR(511) NULL DEFAULT NULL,
  datetime DATETIME NOT NULL,
  outcome ENUM('completed', 'rescheduled', 'cancelled') NULL DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX fk_address_id (address_id ASC),
  INDEX dk_datetime (datetime ASC),
  INDEX fk_user_id (user_id ASC),
  INDEX fk_appointment_type_id (appointment_type_id ASC),
  INDEX fk_interview_id (interview_id ASC),
  INDEX dk_outcome (outcome ASC),
  INDEX fk_appointment_type_reason_id (appointment_type_reason_id ASC),
  CONSTRAINT fk_appointment_address_id
    FOREIGN KEY (address_id)
    REFERENCES cenozo.address (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_appointment_type_id
    FOREIGN KEY (appointment_type_id)
    REFERENCES beartooth.appointment_type (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_interview_id
    FOREIGN KEY (interview_id)
    REFERENCES beartooth.interview (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_user_id
    FOREIGN KEY (user_id)
    REFERENCES cenozo.user (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_appointment_type_reason_id
    FOREIGN KEY (appointment_type_reason_id)
    REFERENCES beartooth.appointment_type_reason (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_general_ci;
