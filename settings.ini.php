<?php
/**
 * settings.ini.php
 * 
 * Defines initialization settings for beartooth.
 * DO NOT edit this file, to override these settings use settings.local.ini.php instead.
 * Any changes in the local ini file will override the settings found here.
 */

global $SETTINGS;

// tagged version
$SETTINGS['general']['application_name'] = 'beartooth';
$SETTINGS['general']['instance_name'] = $SETTINGS['general']['application_name'];
$SETTINGS['general']['version'] = '2.7';
$SETTINGS['general']['build'] = '15195c0f';

// determines whether users other than administrators can see next-of-kin data
$SETTINGS['general']['next_of_kin'] = false;

// the location of beartooth internal path
$SETTINGS['path']['APPLICATION'] = str_replace( '/settings.ini.php', '', __FILE__ );

// add modules used by the application
$SETTINGS['module']['interview'] = true;
$SETTINGS['module']['script'] = true;
$SETTINGS['module']['voip'] = true;
