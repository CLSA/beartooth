<?php
/**
 * site_assignment_select.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\widget;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * widget site assignment select
 */
class site_assignment_select extends \cenozo\ui\widget
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
    parent::__construct( 'site_assignment', 'select', $args );
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
    $this->set_heading( 'Select a site assignment:' );
    
    // create the participant sub-list widget
    $this->participant_list = lib::create( 'ui\widget\participant_list', $this->arguments );
    $this->participant_list->set_parent( $this );
    $this->participant_list->set_viewable( false );
    $this->participant_list->set_addable( false );
    $this->participant_list->set_removable( false );
    $this->participant_list->set_heading( 'Available participants' );
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
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $queue_class_name = lib::get_class_name( 'database\queue' );

    // replace participant. with participant_ in the where and order columns of the modifier
    // (see queue record's participant_for_queue for details)
    if( !is_null( $modifier ) ) 
      foreach( $modifier->get_where_columns() as $column )
        $modifier->change_where_column(
          $column, preg_replace( '/^participant\./', 'participant_', $column ) );

    $db_site = lib::create( 'business\session' )->get_site();
    $qnaire_mod = lib::create( 'database\modifier' );
    $qnaire_mod->where( 'type', '=', 'site' );
    $qnaire_mod->order( 'rank' );

    $count = 0;
    foreach( $qnaire_class_name::select( $qnaire_mod ) as $db_qnaire )
    {
      $count = $queue_class_name::get_ranked_participant_count( $db_qnaire, $db_site, $modifier );
      if( 0 < $count ) break;
    }

    return $count;
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
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $queue_class_name = lib::get_class_name( 'database\queue' );

    // replace participant. with participant_ in the where and order columns of the modifier
    // (see queue record's participant_for_queue for details)
    if( !is_null( $modifier ) ) 
    {   
      foreach( $modifier->get_where_columns() as $column )
        $modifier->change_where_column(
          $column, preg_replace( '/^participant\./', 'participant_', $column ) );
      foreach( $modifier->get_order_columns() as $column )
        $modifier->change_order_column(
          $column, preg_replace( '/^participant\./', 'participant_', $column ) );
    }

    $db_site = lib::create( 'business\session' )->get_site();
    $qnaire_mod = lib::create( 'database\modifier' );
    $qnaire_mod->where( 'type', '=', 'site' );
    $qnaire_mod->order( 'rank' );

    $list = array();
    foreach( $qnaire_class_name::select( $qnaire_mod ) as $db_qnaire )
    {
      $list = $queue_class_name::get_ranked_participant_list( $db_qnaire, $db_site, $modifier );
      if( 0 < count( $list ) ) break;
    }

    return $list;
  }

  /**
   * The participant list widget.
   * @var participant_list
   * @access protected
   */
  protected $participant_list = NULL;
}
?>
