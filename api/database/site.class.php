<?php
/**
 * site.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * site: record
 */
class site extends \cenozo\database\site {}

site::add_extending_table( 'voip' );
