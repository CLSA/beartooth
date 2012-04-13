<?php
/**
 * participant.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\database
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * participant: record
 *
 * @package beartooth\database
 */
class participant extends \cenozo\database\has_note
{
  /**
   * Extend the select() method by adding a custom join to the jursidiction table.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record ) | int
   * @static
   * @access public
   */
  public static function select( $modifier = NULL, $count = false )
  {
    $jurisdiction_mod = lib::create( 'database\modifier' );
    $jurisdiction_mod->where( 'participant.id', '=', 'participant_primary_address.participant_id', false );
    $jurisdiction_mod->where( 'participant_primary_address.address_id', '=', 'address.id', false );
    $jurisdiction_mod->where( 'address.postcode', '=', 'jurisdiction.postcode', false );
    static::customize_join( 'jurisdiction', $jurisdiction_mod );

    return parent::select( $modifier, $count );
  }

  /**
   * Get the participant's most recent assignment.
   * This will return the participant's current assignment, or the most recently closed assignment
   * if the participant is not currently assigned.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return assignment
   * @access public
   */
  public function get_last_assignment()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $class_name = lib::get_class_name( 'database\database' );
    $assignment_id = static::db()->get_one(
      sprintf( 'SELECT assignment_id '.
               'FROM participant_last_assignment '.
               'WHERE participant_id = %s',
               $class_name::format_string( $this->id ) ) );
    return $assignment_id ? lib::create( 'database\assignment', $assignment_id ) : NULL;
  }

  /**
   * Get the participant's most recent, closed assignment.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return assignment
   * @access public
   */
  public function get_last_finished_assignment()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview.participant_id', '=', $this->id );
    $modifier->where( 'end_datetime', '!=', NULL );
    $modifier->order_desc( 'start_datetime' );
    $modifier->limit( 1 );
    $class_name = lib::get_class_name( 'database\assignment' );
    $assignment_list = $class_name::select( $modifier );

    return 0 == count( $assignment_list ) ? NULL : current( $assignment_list );
  }

  /**
   * Get the participant's last consent
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return consent
   * @access public
   */
  public function get_last_consent()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $class_name = lib::get_class_name( 'database\database' );
    $consent_id = static::db()->get_one(
      sprintf( 'SELECT consent_id '.
               'FROM participant_last_consent '.
               'WHERE participant_id = %s',
               $class_name::format_string( $this->id ) ) );
    return $consent_id ? lib::create( 'database\consent', $consent_id ) : NULL;
  }

  /**
   * Get the participant's "primary" address.  This is the highest ranking canadian address.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return address
   * @access public
   */
  public function get_primary_address()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $class_name = lib::get_class_name( 'database\database' );
    $address_id = static::db()->get_one(
      sprintf( 'SELECT address_id FROM participant_primary_address WHERE participant_id = %s',
               $class_name::format_string( $this->id ) ) );
    return $address_id ? lib::create( 'database\address', $address_id ) : NULL;
  }

  /**
   * Get the participant's "first" address.  This is the highest ranking, active, available
   * address.
   * Note: this address may be in the United States
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return address
   * @access public
   */
  public function get_first_address()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $class_name = lib::get_class_name( 'database\database' );
    $address_id = static::db()->get_one(
      sprintf( 'SELECT address_id FROM participant_first_address WHERE participant_id = %s',
               $class_name::format_string( $this->id ) ) );
    return $address_id ? lib::create( 'database\address', $address_id ) : NULL;
  }

  /**
   * Get the site that the participant belongs to.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return site
   * @access public
   */
  public function get_primary_site()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    $db_site = NULL;

    $db_address = $this->get_primary_address();
    if( !is_null( $db_address ) )
    { // there is a primary address
      $class_name = lib::get_class_name( 'database\jurisdiction' );
      $db_jurisdiction = $class_name::get_unique_record( 'postcode', $db_address->postcode );
      if( !is_null( $db_address ) ) $db_site = $db_jurisdiction->get_site();
    }

    return $db_site;
  }
  
  /**
   * Get the last phone call which reached the participant
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return phone_call
   * @access public
   */
  public function get_last_contacted_phone_call()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $class_name = lib::get_class_name( 'database\database' );
    $phone_call_id = static::db()->get_one(
      sprintf( 'SELECT phone_call_id '.
               'FROM participant_last_contacted_phone_call '.
               'WHERE participant_id = %s',
               $class_name::format_string( $this->id ) ) );
    return $phone_call_id ? lib::create( 'database\phone_call', $phone_call_id ) : NULL;
  }

  /**
   * Override parent's magic get method so that supplementary data can be retrieved
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name The name of the column or table being fetched from the database
   * @return mixed
   * @access public
   */
  public function __get( $column_name )
  {
    if( 'current_qnaire_id' == $column_name ||
        'current_qnaire_type' == $column_name ||
        'start_qnaire_date' == $column_name )
    {
      $this->get_queue_data();
      return $this->$column_name;
    }

    return parent::__get( $column_name );
  }

  /**
   * Fills in the current qnaire id and start qnaire date
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access private
   */
  private function get_queue_data()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    if( is_null( $this->current_qnaire_id ) &&
        is_null( $this->current_qnaire_type ) &&
        is_null( $this->start_qnaire_date ) )
    {
      $class_name = lib::get_class_name( 'database\database' );
      $sql = sprintf( 'SELECT current_qnaire_id, current_qnaire_type, start_qnaire_date '.
                      'FROM participant_for_queue '.
                      'WHERE id = %s',
                      $class_name::format_string( $this->id ) );
      $row = static::db()->get_row( $sql );
      $this->current_qnaire_id = $row['current_qnaire_id'];
      $this->current_qnaire_type = $row['current_qnaire_type'];
      $this->start_qnaire_date = $row['start_qnaire_date'];
    }
  }

  /**
   * The participant's current questionnaire id (from participant_for_queue)
   * @var int
   * @access private
   */
  private $current_qnaire_id = NULL;

  /**
   * The participant's current questionnaire type (from participant_for_queue)
   * @var string
   * @access private
   */
  private $current_qnaire_type = NULL;

  /**
   * The date that the current questionnaire is to begin (from participant_for_queue)
   * @var int
   * @access private
   */
  private $start_qnaire_date = NULL;
}
?>
