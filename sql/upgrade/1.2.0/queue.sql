-- proceedure used by patch_queue
DROP PROCEDURE IF EXISTS set_queue_id;
DELIMITER //
CREATE PROCEDURE set_queue_id( old_id INT, new_id INT )
  BEGIN
    UPDATE queue SET id = new_id WHERE id = old_id;
    UPDATE queue SET parent_queue_id = new_id WHERE parent_queue_id = old_id;
  END //
DELIMITER ;

-- remove "restricted" and "not available" queues
DROP PROCEDURE IF EXISTS patch_queue;
DELIMITER //
CREATE PROCEDURE patch_queue()
  BEGIN
    SELECT "Adding new time_specific column to queue table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "queue"
      AND COLUMN_NAME = "time_specific" );
    IF @test = 0 THEN
      ALTER TABLE queue
      ADD COLUMN time_specific TINYINT(1) NOT NULL
      AFTER qnaire_specific;

      UPDATE queue SET time_specific = true
      WHERE name LIKE "upcoming %"
      OR name LIKE "assignable %"
      OR ( name LIKE ( "% waiting" ) AND name != "qnaire waiting" )
      OR name LIKE ( "% ready" );
    END IF;
            
    SELECT "Removing defunct queues" AS "";

    SET @test = ( SELECT COUNT(*) FROM queue WHERE name = "restricted" );
    IF @test = 1 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
      SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';

      DELETE FROM queue WHERE name = "restricted";

      -- decrement all queue ids by 1 for id >= 16
      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = 13;
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;
    END IF;
  
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue();
DROP PROCEDURE IF EXISTS patch_queue;
DROP PROCEDURE IF EXISTS set_queue_id;
