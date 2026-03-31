CREATE TABLE appointment_type (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  name VARCHAR(45) NOT NULL,
  color CHAR(7) NOT NULL,
  qnaire_id INT(10) UNSIGNED NOT NULL,
  use_participant_timezone TINYINT(1) NOT NULL DEFAULT 1,
  description MEDIUMTEXT NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX uq_name (name ASC),
  INDEX fk_qnaire_id (qnaire_id ASC),
  CONSTRAINT fk_appointment_type_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES beartooth.qnaire (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;
