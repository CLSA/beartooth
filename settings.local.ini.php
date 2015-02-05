<?php
/**
 * settings.local.ini.php
 * 
 * Defines local initialization settings for beartooth, overriding default settings found in
 * settings.ini.php
 */

global $SETTINGS;

// whether or not to run the application in development mode
$SETTINGS['general']['development_mode'] = true;

// defines the username and password used by mastodon when communicating as a machine
$SETTINGS['general']['machine_user'] = 'mastodon';
$SETTINGS['general']['machine_password'] = '1qaz2wsx';

// the survey ID of the alternate contact script (comment out if the script is unavailable)
$SETTINGS['general']['secondary_survey'] = 81569;

// the file path to the framework and application
$SETTINGS['path']['CENOZO'] = '/home/patrick/files/repositories/cenozo';
$SETTINGS['path']['APPLICATION'] = '/home/patrick/files/repositories/beartooth';

// the path to the log file
$SETTINGS['path']['LOG_FILE'] = $SETTINGS['path']['APPLICATION'].'/log';

// the url of Mastodon (cannot be relative)
$SETTINGS['url']['MASTODON'] = 'https://localhost/patrick/mastodon';

// the path and url of Limesurvey
$SETTINGS['path']['LIMESURVEY'] = '/home/patrick/public_html/limesurvey';
$SETTINGS['url']['LIMESURVEY'] = '../limesurvey';

// database settings (the driver, server and prefixes are set in the framework's settings)
$SETTINGS['db']['username'] = 'patrick';
$SETTINGS['db']['password'] = '1qaz2wsx';

// the Asterisk AJAM url, username and password
$SETTINGS['voip']['enabled'] = false;
$SETTINGS['voip']['url'] = 'http://localhost:8088/mxml';
$SETTINGS['voip']['username'] = 'beartooth';
$SETTINGS['voip']['password'] = '1qaz2wsx';
$SETTINGS['voip']['prefix'] = '00';
$SETTINGS['voip']['xor_key'] = 'fe4guj43wegh6d';
