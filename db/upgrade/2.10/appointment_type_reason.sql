SELECT "Creating new appointment_type_reason table" AS "";

CREATE TABLE IF NOT EXISTS appointment_type_reason (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  appointment_type_id INT(10) UNSIGNED NOT NULL,
  rank INT(10) UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  extra TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  INDEX fk_appointment_type_reason_appointment_type_id (appointment_type_id ASC),
  UNIQUE INDEX uq_appointment_type_id_title (appointment_type_id ASC, title ASC),
  UNIQUE INDEX uq_appointment_type_id_rank (appointment_type_id ASC, rank ASC),
  CONSTRAINT fk_appointment_type_reason_appointment_type_id
    FOREIGN KEY (appointment_type_id)
    REFERENCES appointment_type (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
