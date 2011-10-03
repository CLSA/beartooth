<?php
/**
 * relationship.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\database
 * @filesource
 */

namespace beartooth\database;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\exception as exc;

/**
 * This is an enum class which defines all types of database table relationships.
 * 
 * @package beartooth\database
 */
class relationship
{
  const NONE = 0;
  const ONE_TO_ONE = 1;
  const ONE_TO_MANY = 2;
  const MANY_TO_MANY = 3;
}
?>
