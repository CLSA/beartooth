CREATE TABLE qnaire_has_script (
  qnaire_id int(10) unsigned NOT NULL,
  script_id int(10) unsigned NOT NULL,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (qnaire_id,script_id),
  KEY fk_script_id (script_id),
  KEY fk_qnaire_id (qnaire_id),
  CONSTRAINT fk_qnaire_has_script_qnaire_id
    FOREIGN KEY (qnaire_id)
    REFERENCES qnaire (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_qnaire_has_script_script_id
    FOREIGN KEY (script_id)
    REFERENCES cenozo.script (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='aka: mandatory script';
