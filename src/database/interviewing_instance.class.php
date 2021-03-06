<?php
/**
 * interviewing_instance.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * interviewing_instance: record
 */
class interviewing_instance extends \cenozo\database\record
{
  /**
   * Returns the user record associated with the interviewer_user_id
   * @return user
   * @access public
   */
  public function get_interviewer_user()
  {
    return $this->interviewer_user_id ?
      lib::create( 'database\user', $this->interviewer_user_id ) : NULL;
  }
}
