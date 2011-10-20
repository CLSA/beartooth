-- ----------------------------------------------------------------------------------------------------
-- This file has sample data for help with development.
-- It is highly recommended to not run this script for anything other than development purposes.
-- ----------------------------------------------------------------------------------------------------
SET AUTOCOMMIT=0;

INSERT INTO site( name, timezone ) VALUES
( "Hamilton", "Canada/Eastern" ),
( "McGill", "Canada/Eastern" ),
( "Simon Fraser", "Canada/Pacific" ),
( "Memorial", "Canada/Newfoundland" ),
( "Ottawa", "Canada/Eastern" ),
( "Sherbrooke", "Canada/Eastern" ),
( "Dalhousie", "Canada/Atlantic" ),
( "Calgary", "Canada/Central" ),
( "Victoria", "Canada/Pacific" ),
( "Manitoba", "Canada/Central" ),
( "British Columbia", "Canada/Pacific" );

UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Calgary" )
WHERE abbreviation = "AB";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "British Columbia" )
WHERE abbreviation = "BC";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Manitoba" )
WHERE abbreviation = "MB";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Dalhousie" )
WHERE abbreviation = "NB";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Memorial" )
WHERE abbreviation = "NL";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "British Columbia" )
WHERE abbreviation = "NT";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Dalhousie" )
WHERE abbreviation = "NS";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Manitoba" )
WHERE abbreviation = "NU";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "McMaster" )
WHERE abbreviation = "ON";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Dalhousie" )
WHERE abbreviation = "PE";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Sherbrooke" )
WHERE abbreviation = "QC";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "Manitoba" )
WHERE abbreviation = "SK";
UPDATE region SET site_id = ( SELECT id FROM site WHERE name = "British Columbia" )
WHERE abbreviation = "YT";

-- Creates default/sample users
INSERT INTO user( name, first_name, last_name ) VALUES
( 'patrick', 'P.', 'Emond' ),
( 'dean', 'D.', 'Inglis' ),
( 'dipietv', 'V.', 'DiPietro' );

-- Grants all roles to all sites to all users
INSERT INTO access ( user_id, role_id, site_id )
SELECT user.id AS user_id, role.id AS role_id, site.id AS site_id
FROM user, role, site;

LOAD DATA LOCAL INFILE "./participants.csv"
INTO TABLE participant
FIELDS TERMINATED BY ',' ENCLOSED BY '"';

LOAD DATA LOCAL INFILE "./addresses.csv"
INTO TABLE address
FIELDS TERMINATED BY ',' ENCLOSED BY '"';

LOAD DATA LOCAL INFILE "./phone_numbers.csv"
INTO TABLE phone
FIELDS TERMINATED BY ',' ENCLOSED BY '"';

INSERT INTO jurisdiction ( postcode, site_id, longitude, latitude, distance )
SELECT postcode, 1, 0.0, 0.0, 25 * RAND() FROM address ORDER BY postcode LIMIT 0, 90;
INSERT INTO jurisdiction ( postcode, site_id, longitude, latitude, distance )
SELECT postcode, 2, 0.0, 0.0, 25 * RAND() FROM address ORDER BY postcode LIMIT 90, 90;
INSERT INTO jurisdiction ( postcode, site_id, longitude, latitude, distance )
SELECT postcode, 3, 0.0, 0.0, 25 * RAND() FROM address ORDER BY postcode LIMIT 180, 90;
INSERT INTO jurisdiction ( postcode, site_id, longitude, latitude, distance )
SELECT postcode, 4, 0.0, 0.0, 25 * RAND() FROM address ORDER BY postcode LIMIT 270, 90;
INSERT INTO jurisdiction ( postcode, site_id, longitude, latitude, distance )
SELECT postcode, 5, 0.0, 0.0, 25 * RAND() FROM address ORDER BY postcode LIMIT 360, 90;
INSERT INTO jurisdiction ( postcode, site_id, longitude, latitude, distance )
SELECT postcode, 6, 0.0, 0.0, 25 * RAND() FROM address ORDER BY postcode LIMIT 450, 90;
INSERT INTO jurisdiction ( postcode, site_id, longitude, latitude, distance )
SELECT postcode, 7, 0.0, 0.0, 25 * RAND() FROM address ORDER BY postcode LIMIT 540, 90;
INSERT INTO jurisdiction ( postcode, site_id, longitude, latitude, distance )
SELECT postcode, 8, 0.0, 0.0, 25 * RAND() FROM address ORDER BY postcode LIMIT 630, 90;
INSERT INTO jurisdiction ( postcode, site_id, longitude, latitude, distance )
SELECT postcode, 9, 0.0, 0.0, 25 * RAND() FROM address ORDER BY postcode LIMIT 720, 90;
INSERT INTO jurisdiction ( postcode, site_id, longitude, latitude, distance )
SELECT postcode, 10, 0.0, 0.0, 25 * RAND() FROM address ORDER BY postcode LIMIT 810, 90;
INSERT INTO jurisdiction ( postcode, site_id, longitude, latitude, distance )
SELECT postcode, 11, 0.0, 0.0, 25 * RAND() FROM address ORDER BY postcode LIMIT 900, 100;

INSERT INTO qnaire ( name, rank, type, prev_qnaire_id, delay ) VALUES
( 'Baseline Home', 1, 'home', NULL, 0 ),
( 'Baseline Site', 2, 'site', 1, 0 ),
( 'Follow Up Home', 3, 'home', 2, 156 ),
( 'Follow Up Site', 4, 'site', 3, 0 );

COMMIT;
