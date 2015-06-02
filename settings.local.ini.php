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

// the file path to the framework and application
$SETTINGS['path']['CENOZO'] = '/home/patrick/files/repositories/cenozo';
$SETTINGS['path']['APPLICATION'] = '/home/patrick/files/repositories/beartooth';

// the path to the log file
$SETTINGS['path']['LOG_FILE'] = $SETTINGS['path']['APPLICATION'].'/log';

// the url of Mastodon (cannot be relative)
$SETTINGS['url']['MASTODON'] = 'https://localhost/patrick/mastodon';

// database settings (the driver, server and prefixes are set in the framework's settings)
$SETTINGS['db']['username'] = 'patrick';
$SETTINGS['db']['password'] = '1qaz2wsx';
