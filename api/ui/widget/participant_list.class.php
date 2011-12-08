<?php
/**
 * participant_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log;

/**
 * widget participant list
 * 
 * @package beartooth\ui
 */
class participant_list extends site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the participant list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', $args );
    
    $this->add_column( 'uid', 'string', 'Unique ID', true );
    $this->add_column( 'first_name', 'string', 'First Name', true );
    $this->add_column( 'last_name', 'string', 'Last Name', true );
    $this->add_column( 'status', 'string', 'Condition', true );
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
    
    foreach( $this->get_record_list() as $record )
    {
      $this->add_row( $record->id,
        array( 'uid' => $record->uid ? $record->uid : '(none)',
               'first_name' => $record->first_name,
               'last_name' => $record->last_name,
               'status' => $record->status ? $record->status : '(none)',
               // note count isn't a column, it's used for the note button
               'note_count' => $record->get_note_count() ) );
    }

    $this->finish_setting_rows();
  }

  /**
   * Overrides the parent class method to restrict participant list based on user's role
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    $db_role = lib::create( 'business\session' )->get_role();
    if( 'interviewer' == $db_role->name )
    {
      $db_user = lib::create( 'business\session' )->get_user();
      $db_site = lib::create( 'business\session' )->get_site();
      $class_name = lib::get_class_name( 'database\access' );
      $db_access = $class_name::get_unique_record(
        array( 'user_id', 'site_id', 'role_id' ),
        array( $db_user->id, $db_site->id, $db_role->id ) );
      $class_name = lib::get_class_name( 'database\participant' );
      return $class_name::count_for_access( $db_access, $modifier );
    }

    $class_name = lib::get_class_name( 'database\participant' );
    return is_null( $this->db_restrict_site )
         ? parent::determine_record_count( $modifier )
         : $class_name::count_for_site( $this->db_restrict_site, $modifier );
  }
  
  /**
   * Overrides the parent class method to restrict participant list based on user's role
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    $db_role = lib::create( 'business\session' )->get_role();
    if( 'interviewer' == $db_role->name )
    {
      $db_user = lib::create( 'business\session' )->get_user();
      $db_site = lib::create( 'business\session' )->get_site();
      $class_name = lib::get_class_name( 'database\access' );
      $db_access = $class_name::get_unique_record(
        array( 'user_id', 'site_id', 'role_id' ),
        array( $db_user->id, $db_site->id, $db_role->id ) );
      $class_name = lib::get_class_name( 'database\participant' );
      return $class_name::select_for_access( $db_access, $modifier );
    }

    $class_name = lib::get_class_name( 'database\participant' );
    return is_null( $this->db_restrict_site )
         ? parent::determine_record_list( $modifier )
         : $class_name::select_for_site( $this->db_restrict_site, $modifier );
  }
}
?>
