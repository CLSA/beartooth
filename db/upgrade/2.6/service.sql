SELECT "Adding new services" AS "";

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'identifier', 'GET', 0, 1 ),
( 'identifier', 'GET', 1, 1 ),
( 'participant_identifier', 'GET', 0, 1 ),
( 'participant_identifier', 'GET', 1, 1 ),
( 'pine', 'POST', 0, 1 ),
( 'stratum', 'GET', 0, 1 ),
( 'stratum', 'GET', 1, 1 ),
( 'study', 'GET', 0, 1 ),
( 'study', 'GET', 1, 1 ),
( 'study_phase', 'GET', 0, 1 ),
( 'study_phase', 'GET', 1, 1 );

SELECT "Renaming onyx_instance services to interviewing_instace" AS "";

UPDATE service SET subject = "interviewing_instance" WHERE subject = "onyx_instance";
