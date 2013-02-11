-- proceedure used by patch_queue
DROP PROCEDURE IF EXISTS set_queue_id;
DELIMITER //
CREATE PROCEDURE set_queue_id( old_id INT, new_id INT )
  BEGIN
    UPDATE queue SET id = new_id WHERE id = old_id;
    UPDATE queue SET parent_queue_id = new_id WHERE parent_queue_id = old_id;
    UPDATE assignment SET queue_id = new_id WHERE queue_id = old_id;
  END //
DELIMITER ;

-- remove "qnaire ready", "no appointment" and "deferred" queues
-- add "quota disabled" and "callback" queues
-- rearrange "assigned" queue
DROP PROCEDURE IF EXISTS patch_queue;
DELIMITER //
CREATE PROCEDURE patch_queue()
  BEGIN
    SET @test = ( SELECT COUNT(*) FROM queue );
    IF @test = 60 THEN
      SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
      SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
      SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';
      
      -- remove the queues and reparent the children
      DELETE FROM queue WHERE name IN ( "qnaire ready", "no appointment", "deferred" );
      UPDATE queue SET parent_queue_id = 20 WHERE parent_queue_id IN ( 22, 27 );

      -- to avoid conflics we temporarily add 100000 to the queue id
      CALL set_queue_id( 23, 100022 );
      CALL set_queue_id( 28, 100023 );
      CALL set_queue_id( 25, 100024 );
      CALL set_queue_id( 26, 100029 );
      CALL set_queue_id( 29, 100030 );
      CALL set_queue_id( 30, 100031 );
      CALL set_queue_id( 31, 100032 );
      CALL set_queue_id( 32, 100033 );
      CALL set_queue_id( 33, 100034 );
      CALL set_queue_id( 34, 100035 );
      CALL set_queue_id( 35, 100036 );
      CALL set_queue_id( 36, 100037 );
      CALL set_queue_id( 37, 100038 );
      CALL set_queue_id( 38, 100039 );
      CALL set_queue_id( 39, 100040 );
      CALL set_queue_id( 40, 100041 );
      CALL set_queue_id( 41, 100042 );
      CALL set_queue_id( 42, 100043 );
      CALL set_queue_id( 43, 100044 );
      CALL set_queue_id( 44, 100045 );
      CALL set_queue_id( 45, 100046 );
      CALL set_queue_id( 46, 100047 );
      CALL set_queue_id( 47, 100048 );
      CALL set_queue_id( 48, 100049 );
      CALL set_queue_id( 49, 100050 );
      CALL set_queue_id( 50, 100051 );
      CALL set_queue_id( 51, 100052 );
      CALL set_queue_id( 52, 100053 );
      CALL set_queue_id( 53, 100054 );
      CALL set_queue_id( 54, 100055 );
      CALL set_queue_id( 55, 100056 );
      CALL set_queue_id( 56, 100057 );
      CALL set_queue_id( 57, 100058 );
      CALL set_queue_id( 58, 100059 );
      CALL set_queue_id( 59, 100060 );
      CALL set_queue_id( 60, 100061 );      

      -- subtract 100000 from the queue ids changed above
      UPDATE queue SET id = id - 100000 WHERE id > 100000;
      UPDATE queue SET parent_queue_id = parent_queue_id - 100000 WHERE parent_queue_id > 100000;
      UPDATE assignment SET queue_id = queue_id - 100000 WHERE queue_id > 100000;

      -- push all ranks up by one
      UPDATE queue SET rank = rank + 1 WHERE rank IS NOT NULL ORDER BY rank DESC;

      -- insert new queues
      INSERT INTO queue SET
      id = 25,
      name = "quota disabled",
      title = "Participant's quota is disabled",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "qnaire" ) AS tmp ),
      description = "Participants who belong to a quota which has been disabled";

      INSERT INTO queue SET
      id = 26,
      name = "callback",
      title = "Participants with callbacks",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "qnaire" ) AS tmp ),
      description = "Participants who have an (unassigned) callback.";

      INSERT INTO queue SET
      id = 27,
      name = "upcoming callback",
      title = "Callback upcoming",
      rank = NULL,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "callback" ) AS tmp ),
      description = "Participants who have an callback in the future.";

      INSERT INTO queue SET
      id = 28,
      name = "assignable callback",
      title = "Callback assignable",
      rank = 1,
      qnaire_specific = true,
      parent_queue_id = (
        SELECT id FROM(
          SELECT id
          FROM queue
          WHERE name = "callback" ) AS tmp ),
      description = "Participants who have an immediate callback which is ready to be assigned.";

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_queue();
DROP PROCEDURE IF EXISTS patch_queue;
DROP PROCEDURE IF EXISTS set_queue_id;
