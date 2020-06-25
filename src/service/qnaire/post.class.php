<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\qnaire;
use cenozo\lib, cenozo\log, beartooth\util;

class post extends \cenozo\service\post
{
  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    // repopulate the queues immediately
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::delayed_repopulate();

    // create a completed event type for the new qnaire
    $event_type_class_name = lib::get_class_name( 'database\event_type' );
    $db_qnaire = $this->get_leaf_record();

    $name = sprintf( 'completed (%s)', $db_qnaire->name );
    $db_completed_event_type = $event_type_class_name::get_unique_record( 'name', $name );
    if( is_null( $db_completed_event_type ) )
    {
      $db_completed_event_type = lib::create( 'database\event_type' );
      $db_completed_event_type->name = $name;
      $db_completed_event_type->description = sprintf( 'Interview completed (for the %s interview).', $name );
      $db_completed_event_type->save();
    }
    $db_qnaire->completed_event_type_id = $db_completed_event_type->id;
  }
}
