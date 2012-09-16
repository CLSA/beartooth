<?php
/**
 * appointment_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: appointment new
 *
 * Create a new appointment.
 */
class appointment_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'appointment', $args );
  }

  /**
   * Validate the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    $columns = $this->get_argument( 'columns' );

    // make sure the datetime column isn't blank
    if( !array_key_exists( 'datetime', $columns ) || 0 == strlen( $columns['datetime'] ) )
      throw lib::create( 'exception\notice', 'The date/time cannot be left blank.', __METHOD__ );

    // validate the appointment time
    $this->get_record()->participant_id = $columns['participant_id'];
    $this->get_record()->address_id = array_key_exists( 'address_id', $columns )
                                    ? $columns['address_id'] : NULL;
    $this->get_record()->datetime = $columns['datetime'];
    
    $type = 0 < $this->get_record()->address_id ? 'home' : 'site';

    if( !$this->get_record()->validate_date() )
      throw lib::create( 'exception\notice',
        sprintf( 'The participant is not ready for a %s appointment.', $type ), __METHOD__ );
  }

  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $columns = $this->get_argument( 'columns' );

    // remove the user_id from the columns if this is a site appointment
    if( !array_key_exists( 'address_id', $columns ) && array_key_exists( 'user_id', $columns ) )
      unset( $this->arguments['columns']['user_id'] );
  }
}
?>
