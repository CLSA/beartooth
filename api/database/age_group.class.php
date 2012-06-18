<?php
/**
 * age_group.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\database
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * age_group: record
 *
 * @package beartooth\database
 */
class age_group extends \cenozo\database\record {}

// define the lower as the primary unique key
age_group::set_primary_unique_key( 'uq_lower' );
?>
