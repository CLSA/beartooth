<?php
/**
 * appointment_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log;

/**
 * widget appointment list
 * 
 * @package beartooth\ui
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
    $this->add_column( 'uid', 'string', 'First name', false );
    $this->add_column( 'address', 'string', 'Address', false );
    $this->add_column( 'datetime', 'datetime', 'Date', true );
    $this->add_column( 'state', 'string', 'State', false );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    // don't add appointments if this list isn't parented
    if( is_null( $this->parent ) ) $this->addable = false;

    parent::finish();

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

      $this->add_row( $record->id,
        array( 'uid' => $record->get_participant()->uid,
               'address' => $address,
               'datetime' => $record->datetime,
               'state' => $record->get_state() ) );
    }

    $this->finish_setting_rows();
  }

  /**
   * Overrides the parent class method to restrict appointment list based on user's role
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    $class_name = lib::get_class_name( 'database\appointment' );
    return is_null( $this->db_restrict_site )
         ? parent::determine_record_count( $modifier )
         : $class_name::count_for_site( $this->db_restrict_site, $modifier );
  }
  
  /**
   * Overrides the parent class method to restrict appointment list based on user's role
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    $class_name = lib::get_class_name( 'database\appointment' );
    return is_null( $this->db_restrict_site )
         ? parent::determine_record_list( $modifier )
         : $class_name::select_for_site( $this->db_restrict_site, $modifier );
  }
}
?>
