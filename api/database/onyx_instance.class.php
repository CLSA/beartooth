<?php
/**
 * onyx_instance.class.php
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
 * onyx_instance: record
 *
 * @package beartooth\database
 */
class onyx_instance extends record
{
  /**
   * Returns the user record associated with the interviewer_user_id
   * @author Patrick Emond
   * @return user
   * @access public
   */
  public function get_interviewer_user()
  {
    return $this->interviewer_user_id ? util::create( 'database\user', $this->interviewer_user_id ) : NULL;
  }
}
?>
