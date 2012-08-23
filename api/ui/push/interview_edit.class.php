<?php
/**
 * interview_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: interview edit
 *
 * Edit a interview.
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
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $columns = $this->get_argument( 'columns', array() );

    /*
     * Forcing an interview to be complete is currently disabled in the UI, so commenting out
     * this code for now

    if( array_key_exists( 'completed', $columns ) && 1 == $columns['completed'] )
    {
      $interview_type = $this->get_record()->get_qnaire()->type;
      $appointment_mod = lib::create( 'database\modifier' );
      $appointment_mod->where( 'completed', '=', false );
      if( 'home' == $interview_type ) $appointment_mod->where( 'address_id', '!=', NULL );
      else if ( 'site' == $interview_type ) $appointment_mod->where( 'address_id', '=', NULL );
      $appointment_list =
        $this->get_record()->get_participant()->get_appointment_list( $appointment_mod );
      foreach( $appointment_list as $db_appointment )
      {
        $db_appointment->completed = true;
        $db_appointment->save();
      }
    }

    * Replacing with an exception to make sure setting the completed column isn't allowed
    */
    if( array_key_exists( 'completed', $columns ) )
      throw lib::create( 'exception\notice',
        'Manually setting the completed state of an interview has been disabled.', __METHOD__ );
  }
}
?>
