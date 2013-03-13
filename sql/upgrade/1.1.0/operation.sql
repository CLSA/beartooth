-- only patch the operation table if the database hasn't yet been converted
DROP PROCEDURE IF EXISTS patch_operation;
DELIMITER //
CREATE PROCEDURE patch_operation()
  BEGIN
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.tables
      WHERE table_schema = ( SELECT DATABASE() )
      AND table_name = "user" );
    IF @test = 1 THEN

      -- alternate
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "alternate", "add", true, "View a form for creating a new alternate contact person." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "alternate", "add_address", true, "A form to create a new address entry to add to an alternate contact person." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "alternate", "add_phone", true, "A form to create a new phone entry to add to an alternate contact person." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "alternate", "delete", true, "Removes an alternate contact person from the system." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "alternate", "delete_address", true, "Remove an alternate contact person's address entry." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "alternate", "delete_phone", true, "Remove an alternate contact person's phone entry." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "alternate", "edit", true, "Edits an alternate contact person's details." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "alternate", "list", true, "List alternate contact persons in the system." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "alternate", "new", true, "Add a new alternate contact person to the system." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "alternate", "view", true, "View an alternate contact person's details." );

      -- callback
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "callback", "add", true, "View a form for creating new callbacks for a participant." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "callback", "calendar", true, "Shows callbacks in a calendar format." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "callback", "delete", true, "Removes a participant's callback from the system." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "callback", "edit", true, "Edits the details of a participant's callback." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "pull", "callback", "feed", true, "Retrieves a list of callbacks for a given time-span." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "callback", "list", true, "Lists a participant's callbacks." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "callback", "new", true, "Creates new callback entry for a participant." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "callback", "view", true, "View the details of a participant's particular callback." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "pull", "home_appointment", "report", true, "Download a home appointment report." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "home_appointment", "report", true, "Set up a home appointment report." );

      -- cohort
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "cohort", "add", true, "View a form for creating a new cohort." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "cohort", "delete", true, "Removes a cohort from the system." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "cohort", "edit", true, "Edits a cohort's details." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "cohort", "list", true, "List cohorts in the system." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "cohort", "new", true, "Add a new cohort to the system." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "cohort", "view", true, "View a cohort's details." );

      -- event
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "event", "add", true, "View a form for creating new event entry for a participant." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "event", "delete", true, "Removes a participant's event entry from the system." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "event", "edit", true, "Edits the details of a participant's event entry." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "event", "list", true, "Lists a participant's event entries." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "event", "new", true, "Creates new event entry for a participant." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "event", "view", true, "View the details of a participant's particular event entry." );

      -- event_type
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "event_type", "list", true, "Lists event types." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "event_type", "view", true, "View the details of an event type." );

      -- participant
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "participant", "add_alternate", true, "A form to create a new alternate contact to add to a participant." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "participant", "add_callback", true, "A form to create a new callback to add to a participant." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "participant", "add_event", true, "A form to create a new event entry to add to a participant." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "participant", "delete_alternate", true, "Remove a participant's alternate contact." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "participant", "delete_callback", true, "Remove a participant's callback." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "participant", "delete_event", true, "Remove a participant's event entry." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "participant", "multinote", true, "Adds a note to a group of participants." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "participant", "multinote", true, "A form to add a note to multiple participants at once." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "pull", "participant", "report", true, "Download a participant report." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "participant", "report", true, "Set up a participant report." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "participant", "site_reassign", true, "A form to mass reassign the preferred site of multiple participants at once." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "participant", "site_reassign", true, "Updates the preferred site of a group of participants." );

      -- quota
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "quota", "chart", true, "Displays a chart describing the progress of participant quotas." );

      -- service
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "service", "add", true, "View a form for creating a new service." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "service", "add_cohort", true, "A form to add a cohort to a service." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "service", "add_role", true, "A form to add a role to a service." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "service", "delete", true, "Removes a service from the system." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "service", "delete_cohort", true, "Remove a service's cohort." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "service", "delete_role", true, "Remove a service's role." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "service", "edit", true, "Edits a service's details." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "service", "list", true, "List services in the system." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "service", "new", true, "Add a new service to the system." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "service", "new_cohort", true, "Add a cohort to a service." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "push", "service", "new_role", true, "Add a role to a service." );
      INSERT IGNORE INTO operation( type, subject, name, restricted, description )
      VALUES( "widget", "service", "view", true, "View a service's details." );

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_operation();
DROP PROCEDURE IF EXISTS patch_operation;
