CREATE TABLE interviewing_instance (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  update_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  create_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  site_id INT(10) UNSIGNED NOT NULL,
  user_id INT(10) UNSIGNED NOT NULL,
  interviewer_user_id INT(10) UNSIGNED NULL DEFAULT NULL,
  type ENUM('onyx', 'pine') NOT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX uq_user_id (user_id ASC),
  INDEX fk_site_id (site_id ASC),
  INDEX fk_user_id (user_id ASC),
  INDEX fk_interviewer_user_id (interviewer_user_id ASC),
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
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;
