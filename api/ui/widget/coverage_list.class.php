<?php
/**
 * coverage_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget coverage list
 * 
 * @package beartooth\ui
 */
class coverage_list extends base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the coverage list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'coverage', $args );
    
    $session = lib::create( 'business\session' );

    $this->add_column( 'username', 'string', 'Interviewer', false );
    $this->add_column( 'postcode_mask', 'string', 'Postal Code', true );
    $this->add_column( 'nearest', 'number', 'Nearest (km)', false );
    $this->add_column( 'furthest', 'number', 'Furthest (km)', false );
    $this->add_column( 'jurisdiction_count', 'number', 'Jurisdictions', false );
    $this->add_column( 'participant_count', 'number', 'Participants', false );
    
    try
    {
      // create the interviewer sub-list widget
      $this->interviewer_list = lib::create( 'ui\widget\interviewer_list', $args );
    }
    catch( exc\permission $e )
    {
      $this->interviewer_list = NULL;
    }
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

    foreach( $this->get_record_list() as $record )
    {
      $nearest = $record->get_nearest_distance( $db_site );
      $nearest = is_null( $nearest ) ? 'N/A' : sprintf( '%0.1f', $nearest );
      $furthest = $record->get_furthest_distance( $db_site );
      $furthest = is_null( $furthest ) ? 'N/A' : sprintf( '%0.1f', $furthest );

      $jurisdiction_mod = lib::create( 'database\modifier' );
      $jurisdiction_mod->where( 'site_id', '=', $db_site->id );
      $jurisdiction_mod->where( 'jurisdiction.postcode', 'LIKE', $record->postcode_mask );

      $participant_mod = lib::create( 'database\modifier' );
      $participant_mod->where( 'jurisdiction.postcode', 'LIKE', $record->postcode_mask );

      // format the postcode LIKE-based statement
      $postcode_mask = str_replace( '%', '', $record->postcode_mask );
      $length = strlen( $postcode_mask );
      for( $index = $length; $index < 7; $index++ ) $postcode_mask .= 3 == $index ? ' ' : '?';

      // assemble the row for this record
      $jurisdiction_class_name = lib::get_class_name( 'database\jurisdiction' );
      $participant_class_name = lib::get_class_name( 'database\participant' );
      $this->add_row( $record->id,
        array( 'username' => $record->get_access()->get_user()->name,
               'postcode_mask' => $postcode_mask,
               'nearest' => $nearest,
               'furthest' => $furthest,
               'jurisdiction_count' => $jurisdiction_class_name::count( $jurisdiction_mod ),
               'participant_count' =>
                 $participant_class_name::count_for_site( $db_site, $participant_mod ) ) );
    }

    $this->finish_setting_rows();

    if( !is_null( $this->interviewer_list ) )
    {
      $this->interviewer_list->finish();
      $this->set_variable( 'interviewer_list', $this->interviewer_list->get_variables() );
    }
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
    if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'access.site_id', '=', lib::create( 'business\session' )->get_site()->id );
    return parent::determine_record_count( $modifier );
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
    if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'access.site_id', '=', lib::create( 'business\session' )->get_site()->id );
    return parent::determine_record_list( $modifier );
  }

  /**
   * The participant list widget.
   * @var interviewer_list
   * @access protected
   */
  protected $interviewer_list = NULL;
}
?>
