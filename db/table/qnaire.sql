CREATE TABLE qnaire (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  name varchar(255) NOT NULL,
  rank int(11) NOT NULL,
  completed_event_type_id int(10) unsigned NOT NULL,
  prev_event_type_id int(10) unsigned DEFAULT NULL,
  allow_missing_consent tinyint(1) NOT NULL DEFAULT 1,
  delay_offset int(11) NOT NULL DEFAULT 0,
  delay_unit enum('day','week','month') NOT NULL DEFAULT 'week',
  type enum('home','site') NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_name (name),
  UNIQUE KEY uq_rank (rank),
  KEY fk_completed_event_type_id (completed_event_type_id),
  KEY fk_prev_event_type_id (prev_event_type_id),
  CONSTRAINT fk_qnaire_completed_event_type_id
    FOREIGN KEY (completed_event_type_id)
    REFERENCES cenozo.event_type (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_qnaire_prev_event_type_id
    FOREIGN KEY (prev_event_type_id)
    REFERENCES cenozo.event_type (id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
