<?php
/**
 * interviewer_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * widget interviewer list
 * 
 * @package beartooth\ui
 */
class interviewer_list extends base_list_widget
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
    
    $session = bus\session::self();

    $this->add_column( 'username', 'string', 'Interviewer', true );
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
    
    $db_site = bus\session::self()->get_site();
    $db_role = db\role::get_unique_record( 'name', 'interviewer' );

    foreach( $this->get_record_list() as $record )
    {
      $db_access = db\access::get_unique_record(
        array( 'user_id', 'site_id', 'role_id' ),
        array( $record->id, $db_site->id, $db_role->id ) );
      
      if( $db_access )
      {
        $modifier = new db\modifier();
        $modifier->where( 'access_id', '=', $db_access->id );
  
        // assemble the row for this record
        $this->add_row( $record->id,
          array( 'username' => $record->name,
                 'coverages' => db\coverage::count( $modifier ),
                 'jurisdiction_count' =>
                   db\jurisdiction::count_for_access( $db_access ),
                 'participant_count' =>
                   db\participant::count_for_access( $db_access ) ) );
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
    $db_role = db\role::get_unique_record( 'name', 'interviewer' );
    if( NULL == $modifier ) $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', bus\session::self()->get_site()->id );
    $modifier->where( 'role_id', '=', $db_role->id );
    return db\user::count( $modifier );
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
    $db_role = db\role::get_unique_record( 'name', 'interviewer' );
    if( NULL == $modifier ) $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', bus\session::self()->get_site()->id );
    $modifier->where( 'role_id', '=', $db_role->id );
    return db\user::select( $modifier );
  }
}
?>
