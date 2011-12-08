<?php
/**
 * participant_tree.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package beartooth\ui
 * @filesource
 */

namespace beartooth\ui\pull;
use beartooth\log, beartooth\util;
use beartooth\business as bus;
use beartooth\database as db;
use beartooth\exception as exc;

/**
 * Consent form report data.
 * 
 * @abstract
 * @package beartooth\ui
 */
class participant_tree_report extends base_report
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
    parent::__construct( 'participant_tree', $args );
  }

  public function finish()
  {
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $db_qnaire = util::create( 'database\qnaire', $this->get_argument( 'restrict_qnaire_id' ) );
    
    $site_mod = util::create( 'database\modifier' );
    if( $restrict_site_id )
    {
      $db_restrict_site = util::create( 'database\site', $restrict_site_id );
      $site_mod->where( 'id', '=', $db_restrict_site );
    }
    $this->add_title( 'Generated for the '.$db_qnaire->name.' questionnaire' );

    $contents = array();

    // The following code is very similar to the participant_tree widget
    // We loop through every queue to get the number of participants waiting in it
    $queue_class_name = util::get_class_name( 'database\queue' );
    $site_class_name = util::get_class_name( 'database\site' );
    foreach( $queue_class_name::select() as $db_queue )
    {
      $row = array( $db_queue->title );

      foreach( $site_class_name::select( $site_mod ) as $db_site )
      {
        // restrict by site, if necessary
        $db_queue->set_site( $db_site );
        $db_queue->set_qnaire( $db_qnaire );
        $row[] = $db_queue->get_participant_count();
      }

      // add the grand total if we are not restricting by site
      if( !$restrict_site_id )
      {
        $db_queue->set_site( NULL );
        $row[] = $db_queue->get_participant_count();
      }

      $contents[] = $row;
    }
    
    if( $restrict_site_id )
    {
      $header = array( 'Queue', 'Total' );
    }
    else
    {
      $header = array( 'Queue' );
      foreach( $site_class_name::select( $site_mod ) as $db_site ) $header[] = $db_site->name;
      $header[] = 'Total';
    }

    $this->add_table( NULL, $header, $contents, NULL );

    return parent::finish();
  }
}
?>
