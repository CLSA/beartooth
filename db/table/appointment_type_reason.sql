CREATE TABLE appointment_type_reason (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  update_timestamp timestamp NOT NULL DEFAULT current_timestamp()
    ON UPDATE current_timestamp(),
  create_timestamp timestamp NOT NULL DEFAULT current_timestamp(),
  appointment_type_id int(10) unsigned NOT NULL,
  rank int(10) unsigned NOT NULL,
  title varchar(255) NOT NULL,
  extra tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_appointment_type_id_title (appointment_type_id,title),
  UNIQUE KEY uq_appointment_type_id_rank (appointment_type_id,rank),
  KEY fk_appointment_type_reason_appointment_type_id (appointment_type_id),
  CONSTRAINT fk_appointment_type_reason_appointment_type_id
    FOREIGN KEY (appointment_type_id)
    REFERENCES appointment_type (id)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
