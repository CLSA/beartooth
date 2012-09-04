<?php
/**
 * home_appointment_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\pull;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Home appointment report.
 * 
 * @abstract
 */
class home_appointment_report extends \cenozo\ui\pull\base_report
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject to retrieve the primary information from.
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'home_appointment', $args );
  }

  /**
   * Builds the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $db_user = lib::create( 'business\session' )->get_user();
    $this->set_heading( sprintf(
      'Appointment list for %s %s',
      $db_user->first_name,
      $db_user->last_name ) );

    $contents = array();
    $appointment_mod = lib::create( 'database\modifier' );
    $appointment_mod->where( 'user_id', '=', $db_user->id );
    $appointment_mod->where( 'address_id', '!=', NULL );
    $appointment_mod->where( 'completed', '=', false );
    foreach( $db_user->get_appointment_list( $appointment_mod ) as $db_appointment )
    {
      $db_participant = $db_appointment->get_participant();
      $datetime_obj = util::get_datetime_object( $db_appointment->datetime );
      $db_address = $db_appointment->get_address();
      $address = sprintf(
        '%s, %s, %s, %s',
        $db_address->address1.( !is_null( $db_address->address2 ) ? $db_address->address2 : '' ),
        $db_address->city,
        $db_address->get_region()->abbreviation,
        $db_address->postcode );

      $phone_mod = lib::create( 'database\modifier' );
      $phone_mod->where( 'active', '=', true );
      if( 1 == $db_participant->get_phone_count( $phone_mod ) )
      {
        $db_phone = current( $db_participant->get_phone_list( $phone_mod ) );
        $phone = $db_phone->number;
      }
      else
      {
        $phone_mod->order( 'rank' );
        $first = true;
        $phone = '';
        foreach( $db_participant->get_phone_list( $phone_mod ) as $db_phone )
        {
          $phone .= sprintf( '%s%s: %s',
                             $first ? '' : '; ',
                             $db_phone->type,
                             $db_phone->number );
          $first = false;
        }
      }

      $contents[] = array(
        $db_participant->first_name.' '.$db_participant->last_name,
        $db_participant->uid,
        $datetime_obj->format( 'l, F jS' ),
        $datetime_obj->format( 'g:i A' ),
        $address,
        $phone );
    }

    $header = array(
      'Name',
      'UID',
      'Date',
      'Time',
      'Address',
      'Phone' );
    $this->add_table( NULL, $header, $contents );
  }
}
?>
