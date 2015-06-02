<?php
/**
 * Main web script which drives the application.
 */

namespace beartooth;
use cenozo\lib, cenozo\log, beartooth\util;

// load web-script common code
require_once '../settings.ini.php';
require_once '../settings.local.ini.php';
require_once $SETTINGS['path']['CENOZO'].'/api/bootstrap.class.php';
$bootstrap = new \cenozo\bootstrap();
$bootstrap->initialize( 'ui' );
