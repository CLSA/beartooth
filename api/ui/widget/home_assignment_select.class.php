<?php
/**
 * home_assignment_select.class.php
 *
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget home assignment select
 */
class home_assignment_select extends \cenozo\ui\widget
{
  /**
   * Constructor
   *
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'home_assignment', 'select', $args );
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
    $this->set_heading( 'Select a home assignment:' );

    $language_list = array();
    foreach( lib::create( 'business\session' )->get_user()->get_language_list() as $db_language )
      $language_list[] = $db_language->name;

    $heading = sprintf( 'Available participants (%s)',
                        0 < count( $language_list ) ?
                        sprintf( 'restricted to %s only', implode( ', ', $language_list ) ) :
                        'any language' );

    // create the participant sub-list widget
    $this->participant_list = lib::create( 'ui\widget\participant_list', $this->arguments );
    $this->participant_list->set_parent( $this );
    $this->participant_list->set_viewable( false );
    $this->participant_list->set_addable( false );
    $this->participant_list->set_removable( false );
    $this->participant_list->set_heading( $heading );
    $this->participant_list->set_allow_restrict_state( false );
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

    try
    {
      $this->participant_list->process();
      $this->set_variable( 'participant_list', $this->participant_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}
  }

  /**
   * Overrides the participant list widget's method.
   *
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @participant protected
   */
  public function determine_participant_count( $modifier = NULL )
  {
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $session = lib::create( 'business\session' );
    $db = $session->get_database();

    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'site.id', '=', $session->get_site()->id );
    $modifier->where( 'qnaire.type', '=', 'home' );
    $modifier->where( 'IFNULL( queue_state.enabled, true )', '=', true );

    $language_id_list = $session->get_user()->get_language_idlist();

    if( 0 < count( $language_id_list ) )
    {
      $column = sprintf(
        'IFNULL( participant.language_id, %s )',
        $db->format_string( $session->get_service()->language_id ) );
      $modifier->where( $column, 'IN', $language_id_list );
    }

    return $queue_class_name::get_ranked_participant_count( $modifier );
  }

  /**
   * Overrides the participant list widget's method.
   *
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @participant protected
   */
  public function determine_participant_list( $modifier = NULL )
  {
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $session = lib::create( 'business\session' );
    $db = $session->get_database();

    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'site.id', '=', $session->get_site()->id );
    $modifier->where( 'qnaire.type', '=', 'home' );
    $modifier->where( 'IFNULL( queue_state.enabled, true )', '=', true );

    $language_id_list = $session->get_user()->get_language_idlist();

    if( 0 < count( $language_id_list ) )
    {
      $column = sprintf(
        'IFNULL( participant.language_id, %s )',
        $db->format_string( $session->get_service()->language_id ) );
      $modifier->where( $column, 'IN', $language_id_list );
    }

    return $queue_class_name::get_ranked_participant_list( $modifier );
  }

  /**
   * The participant list widget.
   * @var participant_list
   * @access protected
   */
  protected $participant_list = NULL;
}
