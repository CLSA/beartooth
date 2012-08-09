<?php
/**
 * onyx_instance.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * onyx_instance: record
 */
class onyx_instance extends \cenozo\database\record
{
  /**
   * Returns the user record associated with the interviewer_user_id
   * @author Patrick Emond
   * @return user
   * @access public
   */
  public function get_interviewer_user()
  {
    return $this->interviewer_user_id ?
      lib::create( 'database\user', $this->interviewer_user_id ) : NULL;
  }
}
?>
