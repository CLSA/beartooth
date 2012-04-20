<?php
/**
 * appointment_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: appointment new
 *
 * Create a new appointment.
 * @package beartooth\ui
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
   * Overrides the parent method to make sure the datetime isn't blank and that check for
   * appointment slot availability.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function finish()
  {
    // make sure the datetime column isn't blank
    $columns = $this->get_argument( 'columns' );
    if( !array_key_exists( 'datetime', $columns ) || 0 == strlen( $columns['datetime'] ) )
      throw lib::create( 'exception\notice', 'The date/time cannot be left blank.', __METHOD__ );
    
    foreach( $columns as $column => $value ) $this->get_record()->$column = $value;
    
    // do not include the user_id if this is a site appointment
    if( 0 < $this->get_record()->address_id ) $this->get_record()->user_id = NULL;
    
    if( !$this->get_record()->validate_date() )
    {
      $db_participant = lib::create( 'database\participant', $this->get_record()->participant_id );
      $db_site = $db_participant->get_primary_site();

      $setting_manager = lib::create( 'business\setting_manager' );
      $duration = $setting_manager->get_setting( 'appointment', 'full duration', $db_site );

      $start_datetime_obj = util::get_datetime_object( $this->get_record()->datetime );
      $end_datetime_obj = clone $start_datetime_obj;
      $end_datetime_obj->add( new \DateInterval( sprintf( 'PT%dM', $duration ) ) );
      throw lib::create( 'exception\notice',
        sprintf(
          'Unable to create an appointment (%d minutes) since there is not '.
          'at least 1 slot available from %s and %s.',
          $duration,
          $start_datetime_obj->format( 'H:i' ),
          $end_datetime_obj->format( 'H:i' ) ),
        __METHOD__ );
      
      throw lib::create( 'exception\notice',
        sprintf(
          'The participant is not ready for a %s appointment.',
          0 < $this->get_record()->address_id ? 'home' : 'site' ), __METHOD__ );
    }
    
    // no errors, go ahead and make the change
    parent::finish();
  }
}
?>
