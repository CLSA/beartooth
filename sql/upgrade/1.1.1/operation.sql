DROP PROCEDURE IF EXISTS patch_operation;
DELIMITER //
CREATE PROCEDURE patch_operation()
  BEGIN
    -- determine the @cenozo database name
    SET @cenozo = CONCAT( SUBSTRING( DATABASE(), 1, LOCATE( 'beartooth', DATABASE() ) - 1 ),
                          'cenozo' );

    SELECT "Adding new operations" AS "";

    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "pull", "interview", "report", true, "Download an interview report." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "widget", "interview", "report", true, "Set up an interview report." );
    INSERT IGNORE INTO operation( type, subject, name, restricted, description )
    VALUES( "widget", "participant", "withdraw", true, "Pseudo-assignment to handle participant withdraws." );
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_operation();
DROP PROCEDURE IF EXISTS patch_operation;
