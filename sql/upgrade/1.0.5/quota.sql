DROP PROCEDURE IF EXISTS patch_quota;
DELIMITER //
CREATE PROCEDURE patch_quota()
  BEGIN
    DECLARE test INT;
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "quota"
      AND COLUMN_NAME = "site_id" );
    IF @test = 0 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

      ALTER TABLE quota
      ADD COLUMN site_id INT UNSIGNED NOT NULL
      AFTER region_id;
      ALTER TABLE quota
      ADD INDEX fk_site_id (site_id ASC);
      ALTER TABLE quota
      ADD CONSTRAINT fk_quota_site_id
      FOREIGN KEY (site_id)
      REFERENCES site (id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION;
      ALTER TABLE quota
      DROP INDEX uq_region_id_gender_age_group_id;
      ALTER TABLE quota
      ADD UNIQUE INDEX uq_region_id_site_id_gender_age_group_id
      (region_id ASC, site_id ASC, gender ASC, age_group_id ASC);

      UPDATE site SET region_id = ( SELECT id FROM region WHERE abbreviation = "BC")
      WHERE name = "British Columbia";
      DELETE FROM quota;
      ALTER TABLE quota AUTO_INCREMENT = 1;
      INSERT INTO quota ( region_id, site_id, gender, age_group_id, population )
      SELECT r.id, s.id, g.gender, a.id, 300
      FROM region r join site s on r.id = s.region_id,
           ( select distinct gender from participant ) g,
           age_group a
      ORDER BY r.id, s.id, a.id, g.gender;
      UPDATE quota SET population = 450 WHERE age_group_id IN (
        SELECT id FROM age_group WHERE lower < 60
      );
      UPDATE quota SET population = population / 2 WHERE site_id IN (
        SELECT id FROM site WHERE name IN ( "Simon Fraser", "British Columbia" )
      );

      SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
      SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_quota();
DROP PROCEDURE IF EXISTS patch_quota;
