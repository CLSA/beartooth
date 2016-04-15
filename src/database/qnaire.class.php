<?php
/**
 * qnaire.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\database;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * qnaire: record
 */
class qnaire extends \cenozo\database\has_rank
{
  /**
   * Returns the previous qnaire
   * 
   * Returns the qnaire done previously to the current qnaire.  If there is no previous qnaire
   * then this method will return NULL.
   * @author Patrick Emond <emondpd@mcmaster.ca>
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
   * @author Patrick Emond <emondpd@mcmaster.ca>
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
   * Returns the event-type associated with when the previous phase's qnaire was completed.
   * If no event-type exists this method will return NULL.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\event_type
   * @access public
   */
  public function get_prev_event_type()
  {
    return is_null( $this->prev_event_type_id ) ?
      NULL : lib::create( 'database\event_type', $this->prev_event_type_id );
  }
}
