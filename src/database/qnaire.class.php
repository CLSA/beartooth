<?php
/**
 * qnaire.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * qnaire: record
 */
class qnaire extends \cenozo\database\has_rank
{
  /**
   * Extend parent method
   */
  public function add_quota( $ids )
  {
    parent::add_quota( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }

  /**
   * Extend parent method
   */
  public function remove_quota( $ids )
  {
    parent::remove_quota( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }

  /**
   * Extend parent method
   */
  public function replace_quota( $ids )
  {
    parent::replace_quota( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }
  /**
   * Extend parent method
   */
  public function add_site( $ids )
  {
    parent::add_site( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }

  /**
   * Extend parent method
   */
  public function remove_site( $ids )
  {
    parent::remove_site( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }

  /**
   * Extend parent method
   */
  public function replace_site( $ids )
  {
    parent::replace_site( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }

  /**
   * Returns the previous qnaire
   * 
   * Returns the qnaire done previously to the current qnaire.  If there is no previous qnaire
   * then this method will return NULL.
   * @return database\qnaire
   * @access public
   */
  public function get_prev_qnaire()
  {
    return is_null( $this->prev_qnaire_id ) ?  NULL : new static( $this->prev_qnaire_id );
  }

  /**
   * Returns a special event-type associated with this qnaire
   * 
   * Returns the event-type associated with when this qnaire is completed.  If no event-type exists
   * this method will return NULL.
   * @return database\event_type
   * @access public
   */
  public function get_completed_event_type()
  {
    return is_null( $this->completed_event_type_id ) ?
      NULL : lib::create( 'database\event_type', $this->completed_event_type_id );
  }

  /**
   * Returns a special event-type associated with this qnaire
   * 
   * Returns the event-type associated with when the previous qnaire was completed.
   * If no event-type exists this method will return NULL.
   * @return database\event_type
   * @access public
   */
  public function get_prev_event_type()
  {
    return is_null( $this->prev_event_type_id ) ?
      NULL : lib::create( 'database\event_type', $this->prev_event_type_id );
  }
}
