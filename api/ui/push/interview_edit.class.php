<?php
/**
 * interview_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: interview edit
 *
 * Edit a interview.
 * @package beartooth\ui
 */
class interview_edit extends \cenozo\ui\push\base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'interview', $args );
  }
  
  /**
   * Make sure to complete appointments when an interview is completed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\permission
   * @access public
   */
  public function finish()
  {
    parent::finish();

    $columns = $this->get_argument( 'columns', array() );
    if( array_key_exists( 'completed', $columns ) && 1 == $columns['completed'] )
    {
      $appointment_class_name = lib::create( 'database\appointment' );
      $appointment_mod = lib::create( 'database\modifier' );
      $appointment_mod->where( 'completed', '=', false );
      $test = 'home' == $this->get_record()->get_qnaire()->type ? '!=' : '=';
      $appointment_mod->where( 'address_id', $test, NULL );
      $appointment_mod->where( 'user_id', $test, NULL );
      foreach( $appointment_class_name::select( $appointment_mod ) as $db_appointment )
      {
        $db_appointment->completed = true;
        $db_appointment->save();
      }
    }
  }
}
?>
