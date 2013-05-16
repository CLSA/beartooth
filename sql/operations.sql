-- -----------------------------------------------------
-- Operations
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- appointment
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "add", true, "View a form for creating new appointments for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "appointment", "delete", true, "Removes a participant's appointment from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "appointment", "edit", true, "Edits the details of a participant's appointment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "appointment", "list", true, "Retrieves a list of appointments for an onyx instance." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "list", true, "Lists a participant's appointments." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "appointment", "new", true, "Creates new appointment entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "appointment", "view", true, "View the details of a participant's particular appointment." );

-- assignment
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "assignment", "begin", true, "Begins a new assignment with a particular participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "assignment", "end", true, "Ends the current assignment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "assignment", "list", true, "Lists assignments." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "assignment", "view", true, "View assignment details." );

-- callback
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "callback", "add", true, "View a form for creating new callbacks for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "callback", "calendar", true, "Shows callbacks in a calendar format." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "callback", "delete", true, "Removes a participant's callback from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "callback", "edit", true, "Edits the details of a participant's callback." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "callback", "feed", true, "Retrieves a list of callbacks for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "callback", "list", true, "Lists a participant's callbacks." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "callback", "new", true, "Creates new callback entry for a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "callback", "view", true, "View the details of a participant's particular callback." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "home_appointment", "report", true, "Download a home appointment report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "home_appointment", "report", true, "Set up a home appointment report." );

-- home_assignment
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "home_appointment", "calendar", true, "A calendar listing home appointments." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "home_appointment", "feed", true, "Retrieves a list of home appointment times for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "home_assignment", "select", true, "Provides a list of participants ready for a home appointment to begin an assignment with." );

-- interview
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "interview", "edit", true, "Edits the details of an interview." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview", "list", true, "Lists interviews." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "interview", "view", true, "View interview details." );

-- onyx
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "onyx", "consent", true, "Allows Onyx to update the consent details of one or more participants." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "onyx", "participants", true, "Allows Onyx to update the information of one or more participants." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "onyx", "proxy", true, "Allows Onyx to update the proxy details of one or more participants." );

-- onyx_instance
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "onyx_instance", "add", true, "View a form for creating a new onyx instance." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "onyx_instance", "delete", true, "Removes a onyx instance from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "onyx_instance", "edit", true, "Edits a onyx instance's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "onyx_instance", "list", true, "List onyx instances in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "onyx_instance", "new", true, "Add a new onyx instance to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "onyx_instance", "view", true, "View a onyx instance's details." );

-- mailout_required
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "mailout_required", "report", true, "Download a mailout required report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "mailout_required", "report", true, "Set up a mailout required report." );

-- participant
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_appointment", true, "A form to create a new appointment to add to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "add_callback", true, "A form to create a new callback to add to a participant." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "delete_appointment", true, "Remove a participant's appointment." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "delete_callback", true, "Remove a participant's callback." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "secondary", true, "Lists a participant's alternates for sourcing purposes." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "participant", "tree", true, "Returns the number of participants for every node of the participant tree." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "tree", true, "Displays participants in a tree format, revealing which queue the belong to." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "participant", "withdraw", true, "Withdraws the participant (or cancels the withdraw).  This is meant to be used during an interview if the participant suddenly wishes to withdraw." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant", "withdraw", true, "Pseudo-assignment to handle participant withdraws." );

-- participant_tree
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "participant_tree", "report", true, "Download a participant tree report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "participant_tree", "report", true, "Set up a participant tree report." );

-- phase
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phase", "add", true, "View a form for creating a new phase." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phase", "delete", true, "Removes a phase from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phase", "edit", true, "Edits a phase's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phase", "list", true, "Lists a questionnaire's phases." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phase", "new", true, "Creates a new questionnaire phase." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phase", "view", true, "View the details of a questionnaire's phases." );

-- phone call
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phone_call", "begin", true, "Starts a new phone call." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "phone_call", "end", true, "Ends the current phone call." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "phone_call", "list", true, "Lists phone calls." );

-- progress
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "progress", "report", true, "Download a progress report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "progress", "report", true, "Set up a progress report." );

-- qnaire
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "add", true, "View a form for creating a new questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "add_phase", true, "View surveys to add as a new phase to a questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "delete", true, "Removes a questionnaire from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "delete_phase", true, "Remove phases from a questionnaire." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "edit", true, "Edits a questionnaire's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "list", true, "List questionnaires in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "qnaire", "new", true, "Add a new questionnaire to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "qnaire", "view", true, "View a questionnaire's details." );

-- queue
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue", "list", true, "List queues in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue", "view", true, "View a queue's details and list of participants." );

-- queue_restriction
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue_restriction", "add", true, "View a form for creating a new queue restriction." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "queue_restriction", "delete", true, "Removes a queue restriction from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "queue_restriction", "edit", true, "Edits a queue restriction's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue_restriction", "list", true, "List queue restrictions in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "queue_restriction", "new", true, "Add a new queue restriction to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "queue_restriction", "view", true, "View a queue restriction's details." );

-- self
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "assignment", false, "Displays the assignment manager." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "self", "dialing_pad", false, "A telephone dialing pad widget." );

-- site_assignment
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site_appointment", "calendar", true, "A calendar listing site appointments." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "site_appointment", "feed", true, "Retrieves a list of site appointment times for a given time-span." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "site_assignment", "select", true, "Provides a list of participants ready for a site appointment to begin an assignment with." );

-- survey
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "survey", "list", true, "List surveys in the system." );

-- voip
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "voip", "begin_monitor", true, "Starts monitoring the active call." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "voip", "dtmf", true, "Sends a DTMF tone to the Asterisk server." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "voip", "end_monitor", true, "Stops monitoring the active call." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "voip", "play", true, "Plays a sound over the Asterisk server." );

COMMIT;
