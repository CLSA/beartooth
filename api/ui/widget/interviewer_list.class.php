<?php
/**
 * interviewer_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * widget interviewer list
 * 
 * @package beartooth\ui
 */
class interviewer_list extends base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the interviewer list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'interviewer', $args );
    
    $session = lib::create( 'business\session' );

    $this->add_column( 'username', 'string', 'Interviewer', false );
    $this->add_column( 'coverages', 'number', 'Coverages', false );
    $this->add_column( 'jurisdiction_count', 'number', 'Jurisdictions', false );
    $this->add_column( 'participant_count', 'number', 'Participants', false );
  }

  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    $db_site = lib::create( 'business\session' )->get_site();
    $class_name = lib::get_class_name( 'database\role' );
    $db_role = $class_name::get_unique_record( 'name', 'interviewer' );

    $access_class_name = lib::get_class_name( 'database\access' );
    $coverage_class_name = lib::get_class_name( 'database\coverage' );
    $jurisdiction_class_name = lib::get_class_name( 'database\jurisdiction' );
    $participant_class_name = lib::get_class_name( 'database\participant' );
    foreach( $this->get_record_list() as $record )
    {
      $db_access = $access_class_name::get_unique_record(
        array( 'user_id', 'site_id', 'role_id' ),
        array( $record->id, $db_site->id, $db_role->id ) );
      
      if( $db_access )
      {
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'access_id', '=', $db_access->id );
  
        // assemble the row for this record
        $this->add_row( $record->id,
          array( 'username' => $record->name,
                 'coverages' => $coverage_class_name::count( $modifier ),
                 'jurisdiction_count' =>
                   $jurisdiction_class_name::count_for_access( $db_access ),
                 'participant_count' =>
                   $participant_class_name::count_for_access( $db_access ) ) );
      }
    }

    $this->finish_setting_rows();
  }
  
  /**
   * Overrides the parent class method since the record count depends on the user's site
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    $class_name = lib::get_class_name( 'database\role' );
    $db_role = $class_name::get_unique_record( 'name', 'interviewer' );
    if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'site_id', '=', lib::create( 'business\session' )->get_site()->id );
    $modifier->where( 'role_id', '=', $db_role->id );
    $class_name = lib::get_class_name( 'database\user' );
    return $class_name::count( $modifier );
  }
  
  /**
   * Overrides the parent class method since the record count depends on the user's site
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    $class_name = lib::get_class_name( 'database\role' );
    $db_role = $class_name::get_unique_record( 'name', 'interviewer' );
    if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'site_id', '=', lib::create( 'business\session' )->get_site()->id );
    $modifier->where( 'role_id', '=', $db_role->id );
    $class_name = lib::get_class_name( 'database\user' );
    return $class_name::select( $modifier );
  }
}
?>
