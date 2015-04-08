-- proceedure used by patch_queue
DROP PROCEDURE IF EXISTS set_queue_id;
DELIMITER //
CREATE PROCEDURE set_queue_id( old_id INT, new_id INT )
  BEGIN
    UPDATE queue SET id = new_id WHERE id = old_id;
    UPDATE queue SET parent_queue_id = new_id WHERE parent_queue_id = old_id;
  END //
DELIMITER ;

DROP PROCEDURE IF EXISTS patch_queue;
DELIMITER //
CREATE PROCEDURE patch_queue()
  BEGIN
    SELECT "Merging 'available' and 'not available' queues into a single 'ready' queue" AS "";

    SET @test = ( SELECT COUNT(*) FROM queue WHERE name LIKE "% available" );
    IF @test = 16 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
      SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';

      -- delete all "not available" queues, and both "new participant (not) available" queues
      DELETE FROM queue
      WHERE name = "new participant available"
      OR name LIKE "% not available";

      -- rename all "available" queues to "ready"
      UPDATE queue
      SET name = CONCAT( SUBSTRING( name, 1, CHAR_LENGTH( name ) - 10 ), " ready" ),
          title = CONCAT( SUBSTRING( title, 1, CHAR_LENGTH( title ) - 10 ), "ready)" )
      WHERE title LIKE "%(available)";

      -- re-number queues
      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = ( SELECT id FROM queue WHERE name = "soft refusal" );
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;

      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = ( SELECT id FROM queue WHERE name = "hang up" );
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;

      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = ( SELECT id FROM queue WHERE name = "no answer" );
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;

      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = ( SELECT id FROM queue WHERE name = "not reached" );
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;

      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = ( SELECT id FROM queue WHERE name = "fax" );
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;

      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = ( SELECT id FROM queue WHERE name = "busy" );
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 1 );
        SET @id = @id + 1;
      END WHILE;

      SET @max_id = ( SELECT MAX( id ) FROM queue );
      SET @id = ( SELECT id FROM queue WHERE name = "old participant" );
      WHILE @id <= @max_id DO
        CALL set_queue_id( @id, @id - 2 );
        SET @id = @id + 1;
      END WHILE;

      -- re-rank queues
      UPDATE queue SET rank = 3 WHERE name = "busy ready";
      UPDATE queue SET rank = 4 WHERE name = "fax ready";
      UPDATE queue SET rank = 5 WHERE name = "not reached ready";
      UPDATE queue SET rank = 6 WHERE name = "no answer ready";
      UPDATE queue SET rank = 7 WHERE name = "hang up ready";
      UPDATE queue SET rank = 8 WHERE name = "soft refusal ready";
      UPDATE queue SET rank = 9 WHERE name = "new participant";

    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue();
DROP PROCEDURE IF EXISTS patch_queue;
DROP PROCEDURE IF EXISTS set_queue_id;
