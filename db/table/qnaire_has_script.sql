CREATE TABLE qnaire_has_script (
  qnaire_id INT(10) UNSIGNED NOT NULL,
  script_id INT(10) UNSIGNED NOT NULL,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (qnaire_id, script_id),
  INDEX fk_script_id (script_id ASC),
  INDEX fk_qnaire_id (qnaire_id ASC),
  CONSTRAINT fk_qnaire_has_script_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES beartooth.qnaire (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_qnaire_has_script_script_id
    FOREIGN KEY (script_id)
    REFERENCES cenozo.script (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COMMENT = 'aka: mandatory script';
