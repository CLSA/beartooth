CREATE TABLE interviewing_instance (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  site_id int(10) unsigned NOT NULL,
  user_id int(10) unsigned NOT NULL,
  interviewer_user_id int(10) unsigned DEFAULT NULL,
  type enum('onyx','pine') NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_id (user_id),
  KEY fk_site_id (site_id),
  KEY fk_user_id (user_id),
  KEY fk_interviewer_user_id (interviewer_user_id),
  CONSTRAINT fk_interviewing_instance_interviewer_user_id
    FOREIGN KEY (interviewer_user_id)
    REFERENCES cenozo.user (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_interviewing_instance_site_id
    FOREIGN KEY (site_id)
    REFERENCES cenozo.site (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_interviewing_instance_user_id
    FOREIGN KEY (user_id)
    REFERENCES cenozo.user (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;