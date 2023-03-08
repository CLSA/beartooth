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
$SETTINGS['general']['version'] = '2.8';
$SETTINGS['general']['build'] = '5ffc1a6c';

// determines whether users other than administrators can see next-of-kin data
$SETTINGS['general']['next_of_kin'] = false;

// the location of beartooth internal path
$SETTINGS['path']['APPLICATION'] = str_replace( '/settings.ini.php', '', __FILE__ );

// default CANTAB settings
$SETTINGS['cantab']['enabled'] = false;
$SETTINGS['cantab']['url'] = '';
$SETTINGS['cantab']['username'] = '';
$SETTINGS['cantab']['password'] = '';
$SETTINGS['cantab']['organisation'] = '';
$SETTINGS['cantab']['study_name'] = '';
$SETTINGS['cantab']['study_phase_name'] = '';
$SETTINGS['cantab']['consent_type_name'] = '';

// add modules used by the application
$SETTINGS['module']['equipment'] = true;
$SETTINGS['module']['interview'] = true;
$SETTINGS['module']['script'] = true;
$SETTINGS['module']['voip'] = true;
