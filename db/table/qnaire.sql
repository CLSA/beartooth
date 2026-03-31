CREATE TABLE qnaire (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  name VARCHAR(255) NOT NULL,
  rank INT(11) NOT NULL,
  completed_event_type_id INT(10) UNSIGNED NOT NULL,
  prev_event_type_id INT(10) UNSIGNED NULL DEFAULT NULL,
  allow_missing_consent TINYINT(1) NOT NULL DEFAULT 1,
  delay_offset INT(11) NOT NULL DEFAULT 0,
  delay_unit ENUM('day', 'week', 'month') NOT NULL DEFAULT 'week',
  type ENUM('home', 'site') NOT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX uq_name (name ASC),
  UNIQUE INDEX uq_rank (rank ASC),
  INDEX fk_completed_event_type_id (completed_event_type_id ASC),
  INDEX fk_prev_event_type_id (prev_event_type_id ASC),
  CONSTRAINT fk_qnaire_completed_event_type_id
    FOREIGN KEY (completed_event_type_id)
    REFERENCES cenozo.event_type (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_qnaire_prev_event_type_id
    FOREIGN KEY (prev_event_type_id)
    REFERENCES cenozo.event_type (id)
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;
