SELECT 'Adding new services' AS '';

INSERT IGNORE INTO service ( subject, method, resource, restricted ) VALUES
( 'appointment_type_reason', 'DELETE', 1, 1 ),
( 'appointment_type_reason', 'GET', 0, 0 ),
( 'appointment_type_reason', 'GET', 1, 0 ),
( 'appointment_type_reason', 'PATCH', 1, 1 ),
( 'appointment_type_reason', 'POST', 0, 1 ),
( 'notation', 'GET', 0, 1 ),
( 'notation', 'GET', 1, 1 ),
( 'user_ip_address', 'GET', 0, 0 ),
( 'user_ip_address', 'GET', 1, 0 );
