<?php
/**
 * appointment_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget appointment list
 */
class appointment_list extends site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the appointment list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'appointment', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $this->add_column( 'user.name', 'string', 'Interviewer', true );
    $this->add_column( 'uid', 'string', 'UID', false );
    $this->add_column( 'address', 'string', 'Address', false );
    $this->add_column( 'datetime', 'datetime', 'Date', true );
    $this->add_column( 'state', 'string', 'State', false );

    $this->extended_site_selection = true;

    // don't add appointments if this list isn't parented
    if( is_null( $this->parent ) ) $this->set_addable( false );
    else if( $this->get_addable() )
    {
      // don't add appointments if the parent already has an incomplete appointment in the future
      $appointment_class_name = lib::get_class_name( 'database\appointment' );
      $appointment_mod = lib::create( 'database\modifier' );
      $appointment_mod->where( 'participant_id', '=', $this->parent->get_record()->id );
      $appointment_mod->where( 'completed', '=', false );
      $appointment_mod->where(
        'datetime', '>', util::get_datetime_object()->format( 'Y-m-d H:i:s' ) );
      $this->set_addable( 0 == $appointment_class_name::count( $appointment_mod ) );

      // don't add appointments if the user isn't currently assigned to the participant
      $db_assignment = lib::create( 'business\session' )->get_current_assignment();
      if( is_null( $db_assignment ) ||
          $db_assignment->get_interview()->get_participant()->id !=
          $this->parent->get_record()->id )
        $this->addable = false;
    }
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

    foreach( $this->get_record_list() as $record )
    {
      $db_address = $record->get_address();
      $address =
        is_null( $db_address ) ? 'site' : sprintf(
          '%s, %s, %s, %s',
          $db_address->address2 ? $db_address->address1.', '.$db_address->address2
                                : $db_address->address1,
          $db_address->city,
          $db_address->get_region()->abbreviation,
          $db_address->postcode );

      $db_user = $record->get_user();
      $this->add_row( $record->id,
        array( 'user.name' => is_null( $db_user ) ? 'none' : $record->get_user()->name,
               'uid' => $record->get_participant()->uid,
               'address' => $address,
               'datetime' => $record->datetime,
               'state' => $record->get_state() ) );
    }
  }
}
?>
