CREATE TABLE appointment (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  interview_id int(10) unsigned NOT NULL,
  user_id int(10) unsigned DEFAULT NULL COMMENT 'NULL for site appointments',
  address_id int(10) unsigned DEFAULT NULL COMMENT 'NULL for site appointments',
  appointment_type_id int(10) unsigned DEFAULT NULL,
  appointment_type_reason_id int(10) unsigned DEFAULT NULL,
  reason_extra varchar(511) DEFAULT NULL,
  datetime datetime NOT NULL,
  outcome enum('completed','rescheduled','cancelled') DEFAULT NULL,
  PRIMARY KEY (id),
  KEY fk_address_id (address_id),
  KEY dk_datetime (datetime),
  KEY fk_user_id (user_id),
  KEY fk_appointment_type_id (appointment_type_id),
  KEY fk_interview_id (interview_id),
  KEY dk_outcome (outcome),
  KEY fk_appointment_type_reason_id (appointment_type_reason_id),
  CONSTRAINT fk_appointment_address_id
    FOREIGN KEY (address_id)
    REFERENCES cenozo.address (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_appointment_type_id
    FOREIGN KEY (appointment_type_id)
    REFERENCES appointment_type (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_appointment_type_reason_id
    FOREIGN KEY (appointment_type_reason_id)
    REFERENCES appointment_type_reason (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_interview_id
    FOREIGN KEY (interview_id)
    REFERENCES interview (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_appointment_user_id
    FOREIGN KEY (user_id)
    REFERENCES cenozo.user (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;